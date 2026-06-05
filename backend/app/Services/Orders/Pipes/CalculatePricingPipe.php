<?php

namespace App\Services\Orders\Pipes;

use App\Contracts\Orders\OrderPipeInterface;
use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Closure;
use Illuminate\Support\Str;

/**
 * CalculatePricingPipe [BASIC]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Pipe cuối cùng trong chuỗi xử lý PRE-save:
 * 1. Tạo Order record trong DB.
 * 2. Tạo OrderItems với unit_price = snapshot giá hiện tại.
 * 3. Tính subtotal, tax, discount → cập nhật tổng tiền.
 *
 * ── Tại sao là Pipe cuối? ────────────────────────────────────────────────────
 * Pipe này thực sự "commit" dữ liệu vào DB. Các Pipe trước chỉ validate.
 * Nếu bất kỳ Pipe nào trước throw Exception → DB chưa bị chạm đến.
 * Điều này tương đương với cơ chế "Fail Fast" trước khi DB transaction.
 */
class CalculatePricingPipe implements OrderPipeInterface
{
    public function handle(PlaceOrderDTO $dto, Closure $next): mixed
    {
        // Lấy lại items map đã được query bởi ValidateActiveItemsPipe (tái dùng, không query lại)
        /** @var \Illuminate\Support\Collection $itemsMap */
        $itemsMap = request()->attributes->get('_validated_items_map')
            ?? Item::query()
                ->whereIn('id', array_map(fn($i) => $i->itemId, $dto->items))
                ->where('restaurant_id', $dto->restaurantId)
                ->get()
                ->keyBy('id');

        // ── Tạo Order ────────────────────────────────────────────────────────
        $order = Order::create([
            'restaurant_id'      => $dto->restaurantId,
            'table_id'           => $dto->tableId,
            'order_code'         => $this->generateOrderCode($dto->restaurantId),
            'source_channel'     => $dto->sourceChannel,
            'service_type'       => $dto->tableId ? 'dine_in' : 'takeaway',
            'status'             => OrderStatus::Open,
            'customer_name'      => $dto->customerName,
            'customer_phone'     => $dto->customerPhone,
            'customer_reference' => $dto->customerReference,
            'guest_count'        => $dto->guestCount,
            'note'               => $dto->note,
            'created_by'         => $dto->createdBy,
            'subtotal_amount'    => 0,
            'discount_amount'    => $dto->discountAmount,
            'tax_amount'         => 0,
            'final_amount'       => 0,
        ]);

        // ── Tạo OrderItems với giá snapshot ──────────────────────────────────
        $orderItemsData = [];
        $subtotal = 0.0;

        foreach ($dto->items as $itemDTO) {
            /** @var Item $item */
            $item      = $itemsMap->get($itemDTO->itemId);
            $unitPrice = (float) $item->sale_price;
            $lineTotal = $unitPrice * $itemDTO->quantity;
            $subtotal += $lineTotal;

            $orderItemsData[] = [
                'id'         => Str::uuid()->toString(),
                'order_id'   => $order->id,
                'item_id'    => $item->id,
                'quantity'   => $itemDTO->quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'note'       => $itemDTO->note,
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        OrderItem::insert($orderItemsData); // 1 query thay vì N queries

        // ── Cập nhật tổng tiền ────────────────────────────────────────────────
        $taxAmount   = $subtotal * $dto->taxRate;
        $finalAmount = $subtotal + $taxAmount - $dto->discountAmount;

        $order->update([
            'subtotal_amount' => $subtotal,
            'tax_amount'      => $taxAmount,
            'final_amount'    => max(0, $finalAmount),
        ]);

        // Gắn order vào túi request để PlaceOrderAction lấy ra trả về
        request()->attributes->set('_created_order', $order->load(['items.item', 'payments']));

        return $next($dto);
    }

    private function generateOrderCode(string $restaurantId): string
    {
        $today    = now()->format('Ymd');
        $count    = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        return "KT-{$today}-{$sequence}";
    }
}

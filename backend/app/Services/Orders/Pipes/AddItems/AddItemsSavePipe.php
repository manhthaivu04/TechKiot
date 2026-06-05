<?php

namespace App\Services\Orders\Pipes\AddItems;

use App\Contracts\Orders\AddItemsPipeInterface;
use App\DTOs\AddItemsDTO;
use App\Models\OrderItem;
use Closure;
use Illuminate\Support\Str;

class AddItemsSavePipe implements AddItemsPipeInterface
{
    public function handle(AddItemsDTO $dto, Closure $next): mixed
    {
        if (empty($dto->newItems)) {
            return $next($dto);
        }

        $itemsMap = request()->attributes->get('_validated_add_items_map');
        $order = $dto->order;

        $orderItemsData = [];
        $additionalSubtotal = 0.0;

        foreach ($dto->newItems as $itemDTO) {
            $item = $itemsMap->get($itemDTO->itemId);
            $unitPrice = (float) $item->sale_price;
            $lineTotal = $unitPrice * $itemDTO->quantity;
            $additionalSubtotal += $lineTotal;

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

        OrderItem::insert($orderItemsData);

        $order->subtotal_amount += $additionalSubtotal;
        $order->final_amount = $order->subtotal_amount + $order->tax_amount - $order->discount_amount;
        $order->save();

        return $next($dto);
    }
}

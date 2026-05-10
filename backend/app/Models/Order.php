<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Enums\OrderSourceChannel;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'orders';

    protected $fillable = [
        'restaurant_id',
        'table_id',
        'order_code',
        'service_type',
        'source_channel',
        'status',
        'customer_name',
        'customer_phone',
        'customer_reference',
        'guest_count',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'final_amount',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'         => OrderStatus::class,
            'source_channel' => OrderSourceChannel::class,
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'final_amount'    => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Quan hệ table chỉ có nghĩa khi gói Pro (TABLE_MANAGEMENT) được kích hoạt.
     * Giữ relationship ở đây để ORM sẵn sàng, tránh migration khi nâng gói.
     */
    public function table(): BelongsTo
    {
        // Model Table sẽ được tạo khi implement Pro module
        return $this->belongsTo(\App\Models\Table::class, 'table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Lấy payment mới nhất (tiện hiển thị trong receipt).
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Domain Helpers ───────────────────────────────────────────────────────

    /**
     * Chuyển trạng thái đơn hàng an toàn.
     * Tập trung kiểm tra business rule tại Model thay vì rải ở Controllers,
     * đảm bảo không bao giờ có transition không hợp lệ dù gọi từ đâu.
     *
     * @throws \DomainException khi transition không hợp lệ
     */
    public function transitionTo(OrderStatus $newStatus): void
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Không thể chuyển đơn hàng từ [{$this->status->label()}] sang [{$newStatus->label()}]."
            );
        }

        $this->update(['status' => $newStatus]);

        Log::info("Order [{$this->order_code}] transitioned: {$this->status->value} → {$newStatus->value}");
    }

    /**
     * Kiểm tra đơn đã được thanh toán đủ chưa.
     * Hữu ích cho split payment (Premium) mà không phải thay đổi logic cốt lõi.
     */
    public function isPaidInFull(): bool
    {
        $totalPaid = $this->payments()->sum('amount');
        return $totalPaid >= $this->final_amount;
    }
}

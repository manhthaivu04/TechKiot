<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * RestaurantPaymentMethod — Cấu hình phương thức thanh toán của từng nhà hàng.
 *
 * Mỗi restaurant có 4 bản ghi tương ứng 4 PaymentMethod enum.
 * OWNER/MANAGER có thể bật/tắt từng phương thức bằng is_active.
 *
 * @property string        $id
 * @property string        $restaurant_id
 * @property PaymentMethod $payment_method
 * @property bool          $is_active
 * @property string|null   $display_name
 * @property string|null   $qr_code_path  Đường dẫn tương đối trong disk 'public' (chỉ dùng cho transfer)
 */
class RestaurantPaymentMethod extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'restaurant_payment_methods';

    protected $fillable = [
        'restaurant_id',
        'payment_method',
        'is_active',
        'display_name',
        'qr_code_path',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'is_active'      => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Nhãn hiển thị: ưu tiên display_name tuỳ chỉnh, fallback về label enum.
     */
    public function label(): string
    {
        return $this->display_name ?? $this->payment_method->label();
    }

    /**
     * URL công khai của ảnh QR (chỉ có ý nghĩa với payment_method = transfer).
     * Trả về null nếu chưa upload.
     */
    public function qrCodeUrl(): ?string
    {
        if (!$this->qr_code_path) {
            return null;
        }

        // Nếu đã là link Cloudinary (hoặc http) thì trả về luôn
        if (str_starts_with($this->qr_code_path, 'http')) {
            return $this->qr_code_path;
        }

        return Storage::disk('public')->url($this->qr_code_path);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}

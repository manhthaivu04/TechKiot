<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\Restaurant;
use App\Models\RestaurantPaymentMethod;
use Illuminate\Database\Seeder;

/**
 * RestaurantPaymentMethodSeeder — Tạo 4 bản ghi mặc định cho một Restaurant.
 *
 * Sử dụng khi:
 * 1. Onboard restaurant mới (gọi trong RestaurantService::onboard).
 * 2. Chạy độc lập: php artisan db:seed --class=RestaurantPaymentMethodSeeder
 *
 * Tất cả phương thức mặc định là is_active = true (bật hết).
 * OWNER/MANAGER có thể tắt từng cái sau khi đăng nhập.
 */
class RestaurantPaymentMethodSeeder extends Seeder
{
    /**
     * Seed 4 phương thức cho tất cả restaurants hiện có.
     * Dùng updateOrCreate để an toàn khi chạy lại nhiều lần.
     */
    public function run(): void
    {
        Restaurant::all()->each(fn ($restaurant) => $this->seedForRestaurant($restaurant->id));
    }

    /**
     * Seed các phương thức mặc định cho 1 restaurant cụ thể.
     * Có thể gọi từ RestaurantService::onboard().
     *
     * Mặc định bật: cash, transfer
     * Mặc định tắt: card, ewallet (bật khi cần)
     */
    public static function seedForRestaurant(string $restaurantId): void
    {
        $defaults = [
            PaymentMethod::Cash->value     => true,   // Tiền mặt — luôn bật
            PaymentMethod::Transfer->value => true,   // Chuyển khoản — bật (có QR upload)
            PaymentMethod::Card->value     => false,  // Thẻ POS — tắt mặc định
            PaymentMethod::Ewallet->value  => false,  // Ví điện tử — tắt mặc định
        ];

        foreach ($defaults as $method => $isActive) {
            RestaurantPaymentMethod::updateOrCreate(
                [
                    'restaurant_id'  => $restaurantId,
                    'payment_method' => $method,
                ],
                [
                    'is_active'    => $isActive,
                    'display_name' => null,
                    'qr_code_path' => null,
                ]
            );
        }
    }
}

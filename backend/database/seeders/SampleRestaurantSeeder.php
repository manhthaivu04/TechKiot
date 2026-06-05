<?php

namespace Database\Seeders;


use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SampleRestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo nhà hàng
        $restaurant = Restaurant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Nhà hàng KiotTay của Nam',
            'phone' => '0987654321',
            'address' => 'Hà Nội, Việt Nam',
            'status' => 'active',
        ]);

        // 2. Tạo gói Subscription (BASIC)
        $basicPackage = \App\Models\Package::where('code', 'BASIC')->first();
        if ($basicPackage) {
            $restaurant->subscriptions()->create([
                'package_id' => $basicPackage->id,
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'activated_at' => now(),
            ]);
        }

        // 3. Tạo tài khoản Chủ nhà hàng (Owner)
        User::create([
            'id' => Str::uuid()->toString(),
            'restaurant_id' => $restaurant->id,
            'name' => 'Nam Owner',
            'email' => 'nam@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        // 4. Tạo các Nhóm món ăn
        $groupMain = ItemGroup::create([
            'id' => Str::uuid()->toString(),
            'restaurant_id' => $restaurant->id,
            'name' => 'Món Chính',
        ]);

        $groupDrink = ItemGroup::create([
            'id' => Str::uuid()->toString(),
            'restaurant_id' => $restaurant->id,
            'name' => 'Đồ Uống',
        ]);

        // 5. Tạo các Món ăn
        $items = [
            [
                'item_group_id' => $groupMain->id,
                'name' => 'Bò Tảng Nướng Đá',
                'description' => 'Bò Mỹ nhập khẩu nướng trên đá muối Himalaya',
                'cost_price' => 180000,
                'sale_price' => 150000,
                'image_url' => 'https://res.cloudinary.com/dygg9cgrw/image/upload/v1717551234/sample-food.jpg',
            ],
            [
                'item_group_id' => $groupMain->id,
                'name' => 'Lẩu Thái Tomyum',
                'description' => 'Lẩu thái chua cay đặc biệt',
                'cost_price' => 250000,
                'sale_price' => 250000,
                'image_url' => 'https://res.cloudinary.com/dygg9cgrw/image/upload/v1717551234/sample-food.jpg',
            ],
            [
                'item_group_id' => $groupDrink->id,
                'name' => 'Bia Heineken',
                'description' => 'Bia chai ướp lạnh',
                'cost_price' => 25000,
                'sale_price' => 25000,
                'image_url' => null,
            ],
            [
                'item_group_id' => $groupDrink->id,
                'name' => 'Trà Đào Cam Sả',
                'description' => 'Trái cây nhiệt đới tươi mát',
                'cost_price' => 35000,
                'sale_price' => 30000,
                'image_url' => null,
            ]
        ];

        foreach ($items as $itemData) {
            Item::create(array_merge($itemData, [
                'id' => Str::uuid()->toString(),
                'restaurant_id' => $restaurant->id,
                'item_type' => 'MENU_ITEM',
                'is_active' => true,
                'availability_status' => 'IN_STOCK',
            ]));
        }
    }
}

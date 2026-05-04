<?php

namespace Database\Seeders;

use App\Enums\FeatureCode;
use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin();
        $this->seedFeatures();
        $this->seedPackages();
    }

    // ─── Super Admin ─────────────────────────────────────────────────────────

    private function seedSuperAdmin(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'id'            => Str::uuid(),
                'name'          => 'Super Admin',
                'password'      => Hash::make('admin123'),
                'role'          => UserRole::SUPER_ADMIN->value,
                'is_active'     => true,
                'restaurant_id' => null,
            ],
        );

        $this->command->info('✓ Super Admin seeded  (admin@gmail.com/ admin123)');
    }

    // ─── Features ────────────────────────────────────────────────────────────

    private function seedFeatures(): void
    {
        $features = [
            // Basic
            [FeatureCode::MENU_MANAGEMENT, 'Quản lý thực đơn',            'Thêm/sửa/xóa món ăn và nhóm món.'],
            [FeatureCode::POS_QUICK_ORDER,  'Bán nhanh (POS)',              'Tạo đơn hàng tại quầy không cần chọn bàn.'],
            [FeatureCode::QR_STATIC_ORDER,  'Gọi món qua QR tĩnh',         'Khách quét QR chung, tự nhập bàn.'],
            [FeatureCode::DAILY_REVENUE,    'Doanh thu ngày',               'Xem tổng tiền bán được trong ngày.'],

            // Pro
            [FeatureCode::TABLE_MANAGEMENT, 'Quản lý bàn & khu vực',       'Sơ đồ quán, tạo QR động cho từng bàn.'],
            [FeatureCode::STAFF_MANAGEMENT, 'Quản lý nhân viên',            'Tạo tài khoản Phục vụ, Bếp, Thu ngân.'],
            [FeatureCode::QR_TABLE_ORDER,   'Gọi món qua QR bàn',           'Hệ thống tự nhận diện bàn qua QR động.'],
            [FeatureCode::DETAIL_REPORT,    'Báo cáo chi tiết',             'Món bán chạy, hiệu suất theo giờ.'],

            // Premium
            [FeatureCode::INVENTORY_MANAGEMENT, 'Quản lý kho',             'Nhập/xuất kho, tồn kho nguyên liệu.'],
            [FeatureCode::RECIPE_MANAGEMENT,    'Quản lý công thức',        'Gán nguyên liệu vào từng món ăn.'],
            [FeatureCode::STOCK_AUDIT,           'Kiểm kê kho',             'Chứng từ điều chỉnh tồn kho.'],
        ];

        foreach ($features as [$code, $name, $description]) {
            Feature::firstOrCreate(
                ['code' => $code->value],
                [
                    'id'          => Str::uuid(),
                    'name'        => $name,
                    'description' => $description,
                    'is_active'   => true,
                ],
            );
        }

        $this->command->info('✓ Features seeded (' . count($features) . ')');
    }

    // ─── Packages ────────────────────────────────────────────────────────────

    private function seedPackages(): void
    {
        $basicFeatures   = [
            FeatureCode::MENU_MANAGEMENT,
            FeatureCode::POS_QUICK_ORDER,
            FeatureCode::QR_STATIC_ORDER,
            FeatureCode::DAILY_REVENUE,
        ];

        $proFeatures     = array_merge($basicFeatures, [
            FeatureCode::TABLE_MANAGEMENT,
            FeatureCode::STAFF_MANAGEMENT,
            FeatureCode::QR_TABLE_ORDER,
            FeatureCode::DETAIL_REPORT,
        ]);

        $premiumFeatures = array_merge($proFeatures, [
            FeatureCode::INVENTORY_MANAGEMENT,
            FeatureCode::RECIPE_MANAGEMENT,
            FeatureCode::STOCK_AUDIT,
        ]);

        $packages = [
            [
                'code'          => 'BASIC',
                'name'          => 'Gói Basic',
                'description'   => 'Phù hợp quán vỉa hè, bán nhanh, takeaway — 1 tài khoản.',
                'price'         => 199000,
                'duration_days' => 30,
                'features'      => $basicFeatures,
            ],
            [
                'code'          => 'PRO',
                'name'          => 'Gói Pro',
                'description'   => 'Quản lý bàn, nhân viên và báo cáo nâng cao.',
                'price'         => 499000,
                'duration_days' => 30,
                'features'      => $proFeatures,
            ],
            [
                'code'          => 'PREMIUM',
                'name'          => 'Gói Premium',
                'description'   => 'Đầy đủ tính năng, thêm quản lý kho và công thức.',
                'price'         => 899000,
                'duration_days' => 30,
                'features'      => $premiumFeatures,
            ],
        ];

        foreach ($packages as $data) {
            $featureCodes = $data['features'];
            unset($data['features']);

            $package = Package::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['id' => Str::uuid(), 'is_active' => true]),
            );

            // Sync features
            $featureIds = Feature::whereIn(
                'code',
                array_map(fn (FeatureCode $c) => $c->value, $featureCodes),
            )->pluck('id')->toArray();

            $package->features()->sync($featureIds);
        }

        $this->command->info('✓ Packages seeded (BASIC, PRO, PREMIUM)');
    }
}

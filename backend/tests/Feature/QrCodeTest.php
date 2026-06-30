<?php

namespace Tests\Feature;

use App\Enums\FeatureCode;
use App\Enums\RestaurantStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\RestaurantSubscription;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Restaurant $restaurant;
    private Package $package;
    private $mockCloudinary;
    private $mockUploadApi;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Cloudinary to avoid real network requests
        $this->mockCloudinary = \Mockery::mock(\Cloudinary\Cloudinary::class);
        $this->mockUploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);
        
        $apiResponse = new \Cloudinary\Api\ApiResponse(
            ['secure_url' => 'https://res.cloudinary.com/test-cloud/image/upload/static_menu_qr.png'],
            []
        );

        $this->mockCloudinary->shouldReceive('uploadApi')->andReturn($this->mockUploadApi);
        $this->mockUploadApi->shouldReceive('upload')->andReturn($apiResponse);

        $this->app->instance(\Cloudinary\Cloudinary::class, $this->mockCloudinary);

        // 1. Create a restaurant
        $this->restaurant = Restaurant::create([
            'name'               => 'Test Restaurant',
            'address'            => '123 Test St',
            'phone'              => '0123456789',
            'status'             => RestaurantStatus::ACTIVE->value,
            'public_order_token' => 'test_static_token_123',
        ]);

        // 2. Create features
        $featureStatic = Feature::create([
            'code'      => FeatureCode::QR_STATIC_ORDER->value,
            'name'      => 'QR Static Order',
            'is_active' => true,
        ]);

        $featureTable = Feature::create([
            'code'      => FeatureCode::TABLE_MANAGEMENT->value,
            'name'      => 'Table Management',
            'is_active' => true,
        ]);

        // 3. Create package and attach features
        $this->package = Package::create([
            'code'          => 'BASIC',
            'name'          => 'Basic Package',
            'price'         => 0,
            'duration_days' => 30,
            'is_active'     => true,
        ]);
        $this->package->features()->attach([$featureStatic->id, $featureTable->id]);

        // 4. Create subscription
        RestaurantSubscription::create([
            'restaurant_id' => $this->restaurant->id,
            'package_id'    => $this->package->id,
            'start_date'    => now(),
            'end_date'      => now()->addDays(30),
            'status'        => SubscriptionStatus::ACTIVE->value,
        ]);

        // 5. Create user
        $this->user = User::create([
            'restaurant_id' => $this->restaurant->id,
            'name'          => 'Owner User',
            'email'         => 'owner@test.com',
            'password'      => bcrypt('password'),
            'role'          => UserRole::OWNER->value,
            'is_active'     => true,
        ]);
    }

    /**
     * Test generating static QR code (JSON format) and uploading to Cloudinary.
     */
    public function test_can_generate_static_qr_code_json(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/tenant/restaurant/qr-code');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'url',
                    'qr_code_url',
                    'qr_code',
                ],
            ]);

        $data = $response->json('data');
        $this->assertStringContainsString('type=qr_static', $data['url']);
        $this->assertStringContainsString('public_token=' . $this->restaurant->public_order_token, $data['url']);
        $this->assertEquals('https://res.cloudinary.com/test-cloud/image/upload/static_menu_qr.png', $data['qr_code_url']);
        $this->assertStringStartsWith('data:image/png;base64,', $data['qr_code']);

        // Check if DB was updated with Cloudinary URL
        $this->restaurant->refresh();
        $this->assertEquals('https://res.cloudinary.com/test-cloud/image/upload/static_menu_qr.png', $this->restaurant->qr_code_url);
    }

    /**
     * Test generating static QR code (Stream format).
     */
    public function test_can_generate_static_qr_code_stream(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get('/api/tenant/restaurant/qr-code?stream=1');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
        
        $this->assertNotEmpty($response->getContent());
    }

    /**
     * Test generating static QR code (Download format).
     */
    public function test_can_generate_static_qr_code_download(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->get('/api/tenant/restaurant/qr-code?download=1');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'attachment; filename="qr-static-menu.png"');
    }

    /**
     * Test generating table QR code (JSON format) and uploading to Cloudinary.
     */
    public function test_can_generate_table_qr_code_json(): void
    {
        Sanctum::actingAs($this->user);

        $table = RestaurantTable::create([
            'restaurant_id' => $this->restaurant->id,
            'name'          => 'Bàn 01',
            'uid'           => 'B-001',
            'capacity'      => 4,
            'qr_token'      => 'tbl_test_token_456',
        ]);

        $response = $this->getJson("/api/tenant/restaurant-tables/{$table->id}/qr-code");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'url',
                    'qr_code_url',
                    'qr_code',
                ],
            ]);

        $data = $response->json('data');
        $this->assertStringContainsString('type=qr_table', $data['url']);
        $this->assertStringContainsString('public_token=' . $table->qr_token, $data['url']);
        $this->assertEquals('https://res.cloudinary.com/test-cloud/image/upload/static_menu_qr.png', $data['qr_code_url']);
        $this->assertStringStartsWith('data:image/png;base64,', $data['qr_code']);

        // Check if DB was updated with Cloudinary URL
        $table->refresh();
        $this->assertEquals('https://res.cloudinary.com/test-cloud/image/upload/static_menu_qr.png', $table->qr_code_url);
    }

    /**
     * Test generating table QR code fails if table belongs to another restaurant.
     */
    public function test_cannot_generate_table_qr_code_of_another_restaurant(): void
    {
        Sanctum::actingAs($this->user);

        $otherRestaurant = Restaurant::create([
            'name'               => 'Other Restaurant',
            'address'            => '456 Other St',
            'phone'              => '0987654321',
            'status'             => RestaurantStatus::ACTIVE->value,
            'public_order_token' => 'other_token',
        ]);

        $table = RestaurantTable::create([
            'restaurant_id' => $otherRestaurant->id,
            'name'          => 'Bàn Khác',
            'uid'           => 'B-999',
            'capacity'      => 4,
            'qr_token'      => 'other_tbl_token',
        ]);

        $response = $this->getJson("/api/tenant/restaurant-tables/{$table->id}/qr-code");

        $response->assertStatus(404);
    }
}

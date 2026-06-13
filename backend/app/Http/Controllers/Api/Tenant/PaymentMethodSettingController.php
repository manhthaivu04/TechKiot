<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodSettingResource;
use App\Models\RestaurantPaymentMethod;
use Database\Seeders\RestaurantPaymentMethodSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * PaymentMethodSettingController — Quản lý cấu hình phương thức thanh toán.
 *
 * ── Ai được dùng? ─────────────────────────────────────────────────────────────
 * Chỉ OWNER và MANAGER. Middleware 'role:OWNER,MANAGER' áp dụng trong routes.
 *
 * ── Endpoints ────────────────────────────────────────────────────────────────
 * GET    /tenant/payment-method-settings                    → Danh sách 4 phương thức + trạng thái
 * PATCH  /tenant/payment-method-settings/{method}/toggle   → Bật/tắt một phương thức
 * PATCH  /tenant/payment-method-settings/{method}          → Cập nhật display_name / is_active
 * POST   /tenant/payment-method-settings/transfer/qr       → Upload ảnh QR chuyển khoản
 * DELETE /tenant/payment-method-settings/transfer/qr       → Xóa ảnh QR chuyển khoản
 */
class PaymentMethodSettingController extends Controller
{
    /**
     * GET /api/tenant/payment-method-settings
     * Trả về danh sách toàn bộ phương thức thanh toán + trạng thái bật/tắt.
     * Tự động seed nếu chưa có bản ghi (an toàn khi migrate).
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Auto-seed nếu restaurant này chưa có config (migration mới chạy)
        $count = RestaurantPaymentMethod::where('restaurant_id', $user->restaurant_id)->count();
        if ($count === 0) {
            RestaurantPaymentMethodSeeder::seedForRestaurant($user->restaurant_id);
        }

        $settings = RestaurantPaymentMethod::where('restaurant_id', $user->restaurant_id)
            ->orderBy('payment_method')
            ->get();

        return $this->successResponse(
            data: PaymentMethodSettingResource::collection($settings),
        );
    }

    /**
     * PATCH /api/tenant/payment-method-settings/{method}/toggle
     * Bật nếu đang tắt, tắt nếu đang bật.
     *
     * @param string $method — Giá trị PaymentMethod enum: cash | card | transfer | ewallet
     */
    public function toggle(string $method): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Validate method hợp lệ
        $paymentMethod = PaymentMethod::tryFrom($method);
        if ($paymentMethod === null) {
            return $this->errorResponse(
                message: "Phương thức thanh toán [{$method}] không hợp lệ. Các giá trị hợp lệ: cash, card, transfer, ewallet.",
                httpStatus: 422,
            );
        }

        $setting = RestaurantPaymentMethod::firstOrCreate(
            [
                'restaurant_id'  => $user->restaurant_id,
                'payment_method' => $paymentMethod->value,
            ],
            ['is_active' => true] // Nếu chưa có → tạo mới với is_active = true trước khi toggle
        );

        // Toggle
        $setting->update(['is_active' => ! $setting->is_active]);

        $status = $setting->is_active ? 'bật' : 'tắt';

        return $this->successResponse(
            data:    new PaymentMethodSettingResource($setting),
            message: "Đã {$status} phương thức thanh toán [{$setting->label()}].",
        );
    }

    /**
     * PATCH /api/tenant/payment-method-settings/{method}
     * Cập nhật tên hiển thị hoặc trạng thái một cách tường minh.
     *
     * Body: { "is_active": true/false, "display_name": "Ví MoMo" }
     */
    public function update(string $method): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $paymentMethod = PaymentMethod::tryFrom($method);
        if ($paymentMethod === null) {
            return $this->errorResponse(
                message: "Phương thức thanh toán [{$method}] không hợp lệ.",
                httpStatus: 422,
            );
        }

        $validated = request()->validate([
            'is_active'    => ['sometimes', 'boolean'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $setting = RestaurantPaymentMethod::firstOrCreate(
            [
                'restaurant_id'  => $user->restaurant_id,
                'payment_method' => $paymentMethod->value,
            ],
            ['is_active' => true]
        );

        $setting->update($validated);

        return $this->successResponse(
            data:    new PaymentMethodSettingResource($setting->fresh()),
            message: 'Cập nhật cấu hình thanh toán thành công.',
        );
    }

    /**
     * POST /api/tenant/payment-method-settings/transfer/qr
     * Upload ảnh QR chuyển khoản ngân hàng của nhà hàng.
     *
     * Body: multipart/form-data
     *   qr_image: file (jpeg|png|webp|gif, max 2MB)
     *
     * Ảnh cũ sẽ bị xóa tự động khi upload ảnh mới.
     */
    public function uploadQr(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request = request();
        $request->validate([
            'qr_image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
        ], [
            'qr_image.required' => 'Vui lòng chọn ảnh QR để upload.',
            'qr_image.image'    => 'File phải là ảnh (jpeg, png, webp, gif).',
            'qr_image.max'      => 'Ảnh QR không được vượt quá 2MB.',
        ]);

        $setting = RestaurantPaymentMethod::firstOrCreate(
            [
                'restaurant_id'  => $user->restaurant_id,
                'payment_method' => PaymentMethod::Transfer->value,
            ],
            ['is_active' => true]
        );

        $image = $request->file('qr_image');

        // Upload lên Cloudinary
        // Đặt public_id cố định để tự động ghi đè ảnh cũ, tránh tạo file rác
        $uploadedFileUrl = cloudinary()->uploadApi()->upload($image->getRealPath(), [
            'folder'    => "kiottay/{$user->restaurant_id}/qrpayment",
            'public_id' => 'transfer_qr',
            'overwrite' => true,
        ])['secure_url'];

        $setting->update(['qr_code_path' => $uploadedFileUrl]);

        return $this->successResponse(
            data:    new PaymentMethodSettingResource($setting->fresh()),
            message: 'Ảnh QR chuyển khoản đã được cập nhật thành công.',
            code:    \App\Enums\ApiCode::CREATED,
            httpStatus: 201,
        );
    }

    /**
     * DELETE /api/tenant/payment-method-settings/transfer/qr
     * Xóa ảnh QR chuyển khoản ngân hàng.
     */
    public function deleteQr(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $setting = RestaurantPaymentMethod::where('restaurant_id', $user->restaurant_id)
            ->where('payment_method', PaymentMethod::Transfer->value)
            ->first();

        if (! $setting || ! $setting->qr_code_path) {
            return $this->errorResponse(
                message: 'Chưa có ảnh QR chuyển khoản để xóa.',
                httpStatus: 404,
            );
        }

        // Xóa file khỏi Cloudinary
        try {
            cloudinary()->uploadApi()->destroy("kiottay/{$user->restaurant_id}/qrpayment/transfer_qr");
        } catch (\Exception $e) {
            // Log::error("Lỗi xóa ảnh QR Cloudinary: " . $e->getMessage());
        }
        
        $setting->update(['qr_code_path' => null]);

        return $this->successResponse(
            data:    new PaymentMethodSettingResource($setting->fresh()),
            message: 'Đã xóa ảnh QR chuyển khoản.',
        );
    }
}

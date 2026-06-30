<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Enums\ApiCode;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrController extends Controller
{
    /**
     * GET /api/tenant/restaurant/qr-code
     *
     * Generate QR Code for the static menu of the restaurant and save to Cloudinary.
     * Supports:
     * - stream=1: returns raw PNG image stream
     * - download=1: triggers file download
     * - force_regenerate=1: forces regeneration and re-upload to Cloudinary
     * - default: returns JSON with the URL and base64 QR code image
     */
    public function staticQrCode(Request $request): mixed
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        if (!$restaurant) {
            return $this->errorResponse(
                'Không tìm thấy thông tin nhà hàng.',
                ApiCode::NOT_FOUND,
                404
            );
        }

        // Đảm bảo nhà hàng có token QR tĩnh
        if (empty($restaurant->public_order_token)) {
            $restaurant->update([
                'public_order_token' => Str::random(32),
            ]);
        }

        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $qrUrl = "{$frontendUrl}/menu?public_token={$restaurant->public_order_token}&type=qr_static";

        $forceRegenerate = $request->query('force_regenerate') == '1';
        $qrCodeUrl = $restaurant->qr_code_url;

        // Cấu hình QR Code
        $qrDataUri = null;
        if (empty($qrCodeUrl) || $forceRegenerate || $request->query('stream') == '1' || $request->query('download') == '1') {
            $options = new QROptions([
                'version'         => Version::AUTO,
                'outputInterface' => QRGdImagePNG::class,
                'scale'           => 6,
                'eccLevel'        => EccLevel::M,
            ]);

            $qrcode = new QRCode($options);

            try {
                $qrDataUri = $qrcode->render($qrUrl);

                // Chỉ upload lên Cloudinary nếu chưa có hoặc yêu cầu force_regenerate
                if (empty($qrCodeUrl) || $forceRegenerate) {
                    $uploadedFileUrl = cloudinary()->uploadApi()->upload($qrDataUri, [
                        'folder'    => "kiottay/{$restaurant->id}/menu_qr",
                        'public_id' => 'static_menu_qr',
                        'overwrite' => true,
                    ])['secure_url'];

                    $restaurant->update(['qr_code_url' => $uploadedFileUrl]);
                    $qrCodeUrl = $uploadedFileUrl;
                }
            } catch (\Throwable $e) {
                return $this->errorResponse('Không thể tạo hoặc tải mã QR: ' . $e->getMessage());
            }
        }

        // Xử lý các options stream và download từ binary của ảnh vừa tạo
        if ($request->query('stream') == '1' || $request->query('download') == '1') {
            $base64Data = substr($qrDataUri, strpos($qrDataUri, ',') + 1);
            $binaryData = base64_decode($base64Data);

            $headers = [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=86400',
            ];

            if ($request->query('download') == '1') {
                $headers['Content-Disposition'] = 'attachment; filename="qr-static-menu.png"';
            }

            return response($binaryData, 200, $headers);
        }

        return $this->successResponse([
            'url'         => $qrUrl,
            'qr_code_url' => $qrCodeUrl,
            'qr_code'     => $qrDataUri,
        ], 'Tạo QR Menu tĩnh thành công.');
    }

    /**
     * GET /api/tenant/restaurant-tables/{id}/qr-code
     *
     * Generate QR Code for a specific restaurant table and save to Cloudinary.
     * Supports:
     * - stream=1: returns raw PNG image stream
     * - download=1: triggers file download
     * - force_regenerate=1: forces regeneration and re-upload to Cloudinary
     * - default: returns JSON with the URL and base64 QR code image
     */
    public function tableQrCode(Request $request, string $id): mixed
    {
        $user = $request->user();
        $table = RestaurantTable::where('id', $id)
            ->where('restaurant_id', $user->restaurant_id)
            ->first();

        if (!$table) {
            return $this->errorResponse(
                'Không tìm thấy bàn ăn.',
                ApiCode::NOT_FOUND,
                404
            );
        }

        // Đảm bảo bàn có token QR
        if (empty($table->qr_token)) {
            $table->update([
                'qr_token' => 'tbl_' . Str::uuid()->toString(),
            ]);
        }

        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $qrUrl = "{$frontendUrl}/menu?public_token={$table->qr_token}&type=qr_table";

        $forceRegenerate = $request->query('force_regenerate') == '1';
        $qrCodeUrl = $table->qr_code_url;

        $qrDataUri = null;
        if (empty($qrCodeUrl) || $forceRegenerate || $request->query('stream') == '1' || $request->query('download') == '1') {
            $options = new QROptions([
                'version'         => Version::AUTO,
                'outputInterface' => QRGdImagePNG::class,
                'scale'           => 6,
                'eccLevel'        => EccLevel::M,
            ]);

            $qrcode = new QRCode($options);

            try {
                $qrDataUri = $qrcode->render($qrUrl);

                // Chỉ upload lên Cloudinary nếu chưa có hoặc yêu cầu force_regenerate
                if (empty($qrCodeUrl) || $forceRegenerate) {
                    $uploadedFileUrl = cloudinary()->uploadApi()->upload($qrDataUri, [
                        'folder'    => "kiottay/{$user->restaurant_id}/tables_qr",
                        'public_id' => "table_{$table->uid}_qr",
                        'overwrite' => true,
                    ])['secure_url'];

                    $table->update(['qr_code_url' => $uploadedFileUrl]);
                    $qrCodeUrl = $uploadedFileUrl;
                }
            } catch (\Throwable $e) {
                return $this->errorResponse('Không thể tạo hoặc tải mã QR: ' . $e->getMessage());
            }
        }

        if ($request->query('stream') == '1' || $request->query('download') == '1') {
            $base64Data = substr($qrDataUri, strpos($qrDataUri, ',') + 1);
            $binaryData = base64_decode($base64Data);

            $headers = [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=86400',
            ];

            if ($request->query('download') == '1') {
                $headers['Content-Disposition'] = 'attachment; filename="qr-table-' . $table->uid . '.png"';
            }

            return response($binaryData, 200, $headers);
        }

        return $this->successResponse([
            'url'         => $qrUrl,
            'qr_code_url' => $qrCodeUrl,
            'qr_code'     => $qrDataUri,
        ], 'Tạo QR Bàn thành công.');
    }
}

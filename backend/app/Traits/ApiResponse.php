<?php

namespace App\Traits;

use App\Enums\ApiCode;
use Illuminate\Http\JsonResponse;

/**
 * Trait dùng trong Controller để trả về JSON response thống nhất.
 */
trait ApiResponse
{
    /**
     * Trả về Response thành công.
     *
     * @param mixed $data Dữ liệu trả về (Resource, Array, Object)
     * @param string $message Thông báo thành công
     * @param ApiCode $code Mã định danh kết quả (Mặc định SUCCESS)
     * @param int $httpStatus HTTP Status Code (Mặc định 200)
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Thao tác thành công.',
        ApiCode $code = ApiCode::SUCCESS,
        int $httpStatus = 200
    ): JsonResponse {
        return response()->json([
            'code'    => $code->value,
            'message' => $message,
            'data'    => $data,
        ], $httpStatus);
    }

    /**
     * Trả về Response thất bại.
     *
     * @param string $message Thông báo lỗi
     * @param ApiCode $code Mã định danh lỗi (Mặc định BAD_REQUEST)
     * @param int $httpStatus HTTP Status Code (Mặc định 400)
     * @param mixed $errors Chi tiết lỗi (VD: validation errors)
     */
    protected function errorResponse(
        string $message = 'Đã xảy ra lỗi.',
        ApiCode $code = ApiCode::BAD_REQUEST,
        int $httpStatus = 400,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'code'    => $code->value,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $httpStatus);
    }
}

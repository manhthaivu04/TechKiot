<?php

namespace App\Enums;

/**
 * ApiCode — Bộ mã lỗi/thành công chuẩn hóa cho toàn bộ API dự án.
 * Dùng string để Frontend dễ đọc, dễ debug thay vì số nguyên vô hồn.
 */
enum ApiCode: string
{
    case SUCCESS              = 'SUCCESS';
    case CREATED              = 'CREATED';
    
    // Client Errors
    case BAD_REQUEST          = 'BAD_REQUEST';
    case VALIDATION_ERROR     = 'VALIDATION_ERROR';
    case UNAUTHORIZED         = 'UNAUTHORIZED';
    case FORBIDDEN            = 'FORBIDDEN';
    case NOT_FOUND            = 'NOT_FOUND';
    
    // Domain/Business Errors
    case DOMAIN_ERROR         = 'DOMAIN_ERROR';
    case INVALID_TRANSITION   = 'INVALID_TRANSITION';
    
    // Server Errors
    case INTERNAL_ERROR       = 'INTERNAL_ERROR';
}

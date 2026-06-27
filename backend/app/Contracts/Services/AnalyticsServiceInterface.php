<?php

namespace App\Contracts\Services;

/**
 * Contract cho AnalyticsService.
 * Đăng ký trong RepositoryServiceProvider để DI container biết bind.
 */
interface AnalyticsServiceInterface
{
    /**
     * Lấy toàn bộ dữ liệu analytics cho Dashboard.
     *
     * @param  string  $restaurantId  UUID của nhà hàng (lấy từ auth user)
     * @param  array{period: string, start_date?: string, end_date?: string}  $params
     * @return array
     */
    public function getDashboard(string $restaurantId, array $params): array;
}

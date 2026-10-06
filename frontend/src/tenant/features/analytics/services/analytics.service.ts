import apiClient from '@/api/http';
import type { DashboardAnalytics, AnalyticsParams } from '../types/analytics.types';

interface DashboardApiResponse {
    data: DashboardAnalytics;
}

/**
 * Gọi API GET /tenant/analytics/dashboard
 * Theo đúng pattern của order.service.ts trong dự án.
 */
export const analyticsService = {
    getDashboard: (params: AnalyticsParams): Promise<DashboardAnalytics> =>
        apiClient
            .get<DashboardApiResponse>('/tenant/analytics/dashboard', { params })
            .then((res) => res.data.data),
};

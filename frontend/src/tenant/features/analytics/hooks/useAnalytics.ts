import { useQuery } from '@tanstack/react-query';
import { analyticsService } from '../services/analytics.service';
import type { AnalyticsParams } from '../types/analytics.types';

export const ANALYTICS_QUERY_KEY = 'analytics-dashboard';

/**
 * Custom hook để fetch dữ liệu analytics dashboard.
 * Dùng React Query với staleTime 60s để tránh gọi API quá nhiều.
 */
export const useAnalytics = (params: AnalyticsParams) => {
    return useQuery({
        queryKey: [ANALYTICS_QUERY_KEY, params],
        queryFn: () => analyticsService.getDashboard(params),
        staleTime: 60 * 1000, // 60 giây
        refetchOnWindowFocus: false,
    });
};

// ─── Analytics Types ────────────────────────────────────────────────────────

export interface OverviewStats {
    total_revenue: number;
    total_orders: number;
    cancelled_orders: number;
    avg_order_value: number;
    revenue_change_pct: number | null;
    orders_change_pct: number | null;
}

export interface ChartDataPoint {
    label: string;
    revenue: number;
    orders: number;
}

export interface PaymentMethodBreakdown {
    method: string;
    label: string;
    revenue: number;
    count: number;
}

export interface SourceChannelBreakdown {
    channel: string;
    label: string;
    revenue: number;
    count: number;
}

export interface TopItem {
    item_id: string;
    name: string;
    image_url: string | null;
    total_sold: number;
    total_revenue: number;
}

export interface DashboardAnalytics {
    period: string;
    start_date: string;
    end_date: string;
    overview: OverviewStats;
    chart_data: ChartDataPoint[];
    by_payment_method: PaymentMethodBreakdown[];
    by_source_channel: SourceChannelBreakdown[];
    top_items: TopItem[];
}

export type AnalyticsPeriod = 'today' | 'week' | 'month' | 'custom';

export interface AnalyticsParams {
    period: AnalyticsPeriod;
    start_date?: string;
    end_date?: string;
}

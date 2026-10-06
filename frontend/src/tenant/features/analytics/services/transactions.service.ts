import apiClient from '@/api/http';
import type { Order, PaginationMeta } from '@/types';

// ─── Request params ───────────────────────────────────────────────────────────

export interface TransactionParams {
    search?: string;
    date_from?: string;
    date_to?: string;
    payment_method?: 'cash' | 'card' | 'transfer' | 'ewallet' | '';
    page?: number;
    per_page?: number;
}

// ─── Response shapes ──────────────────────────────────────────────────────────

export interface TransactionSummary {
    total_revenue: number;
    total_orders: number;
}

export interface TransactionsResponse {
    items: Order[];
    meta: PaginationMeta;
    summary: TransactionSummary;
}

// ─── Service ──────────────────────────────────────────────────────────────────

export const transactionsService = {
    /**
     * GET /api/tenant/analytics/transactions
     * Tra cứu hóa đơn đã thanh toán.
     */
    getTransactions: (params?: TransactionParams): Promise<TransactionsResponse> =>
        apiClient
            .get<{ data: TransactionsResponse }>('/tenant/analytics/transactions', { params })
            .then((res) => res.data.data),
};

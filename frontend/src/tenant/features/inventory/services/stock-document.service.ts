import apiClient from '@/api/http';
import type {
    StockDocument,
    CreateStockDocumentRequest,
} from '@/types';
import type { ApiResponse } from '@/types';

export const stockDocumentService = {
    getAll: () =>
        apiClient
            .get<ApiResponse<StockDocument[]>>('/tenant/stock-documents')
            .then((res) => res.data),

    create: (data: CreateStockDocumentRequest) =>
        apiClient
            .post<ApiResponse<StockDocument>>('/tenant/stock-documents', data)
            .then((res) => res.data),

    confirm: (id: string) =>
        apiClient
            .patch<ApiResponse<StockDocument>>(`/tenant/stock-documents/${id}/confirm`)
            .then((res) => res.data),

    cancel: (id: string) =>
        apiClient
            .patch<ApiResponse<StockDocument>>(`/tenant/stock-documents/${id}/cancel`)
            .then((res) => res.data),
};

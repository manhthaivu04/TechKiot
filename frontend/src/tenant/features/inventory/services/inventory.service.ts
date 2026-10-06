import apiClient from '@/api/http';
import type {
    InventoryItem,
    InventoryTransaction,
    InventoryListParams,
    TransactionListParams,
    ApiResponse,
    PaginatedResponse,
} from '@/types';

// TODO: Backend needs to implement these endpoints
// GET /api/tenant/inventory
// GET /api/tenant/inventory-transactions

export const inventoryService = {
    getInventory: (params?: InventoryListParams) =>
        apiClient
            .get<ApiResponse<InventoryItem[]>>('/tenant/inventory', { params })
            .then((res) => res.data),

    getTransactions: (params?: TransactionListParams) =>
        apiClient
            .get<PaginatedResponse<InventoryTransaction>>('/tenant/inventory-transactions', { params })
            .then((res) => res.data),
};

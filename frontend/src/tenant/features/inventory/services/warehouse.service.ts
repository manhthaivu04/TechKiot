import apiClient from '@/api/http';
import type {
    Warehouse,
    CreateWarehouseRequest,
    UpdateWarehouseRequest,
} from '@/types';
import type { ApiResponse } from '@/types';

export const warehouseService = {
    getAll: () =>
        apiClient
            .get<ApiResponse<Warehouse[]>>('/tenant/warehouses')
            .then((res) => res.data),

    create: (data: CreateWarehouseRequest) =>
        apiClient
            .post<ApiResponse<Warehouse>>('/tenant/warehouses', data)
            .then((res) => res.data),

    update: (id: string, data: UpdateWarehouseRequest) =>
        apiClient
            .put<ApiResponse<Warehouse>>(`/tenant/warehouses/${id}`, data)
            .then((res) => res.data),

    remove: (id: string) =>
        apiClient
            .delete<ApiResponse<null>>(`/tenant/warehouses/${id}`)
            .then((res) => res.data),
};

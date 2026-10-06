import { useMutation, useQuery } from '@tanstack/react-query';
import { warehouseService } from './warehouse.service';
import type {
    CreateWarehouseRequest,
    UpdateWarehouseRequest,
} from '@/types';
import { queryClient } from '@/api/query-client';

const QUERY_KEYS = {
    warehouses: 'tenant_warehouses',
};

export const useWarehouses = () => {
    return useQuery({
        queryKey: [QUERY_KEYS.warehouses],
        queryFn: warehouseService.getAll,
    });
};

export const useCreateWarehouse = () => {
    return useMutation({
        mutationFn: (data: CreateWarehouseRequest) => warehouseService.create(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.warehouses] });
        },
    });
};

export const useUpdateWarehouse = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateWarehouseRequest) => warehouseService.update(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.warehouses] });
        },
    });
};

export const useDeleteWarehouse = () => {
    return useMutation({
        mutationFn: (id: string) => warehouseService.remove(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.warehouses] });
        },
    });
};

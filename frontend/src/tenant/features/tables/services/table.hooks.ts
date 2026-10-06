import { useMutation, useQuery, keepPreviousData } from '@tanstack/react-query';
import { tableService } from './table.service';
import type {
    CreateTableAreaRequest,
    UpdateTableAreaRequest,
    CreateRestaurantTableRequest,
    UpdateRestaurantTableRequest,
    RestaurantTableListParams,
} from '@/types';
import { queryClient } from '@/api/query-client';

const QUERY_KEYS = {
    tableAreas: 'tenant_table_areas',
    restaurantTables: 'tenant_restaurant_tables',
};

// --- Table Areas Hooks ---

export const useTableAreas = () => {
    return useQuery({
        queryKey: [QUERY_KEYS.tableAreas],
        queryFn: tableService.getTableAreas,
    });
};

export const useCreateTableArea = () => {
    return useMutation({
        mutationFn: (data: CreateTableAreaRequest) => tableService.createTableArea(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.tableAreas] });
        },
    });
};

export const useUpdateTableArea = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateTableAreaRequest) => tableService.updateTableArea(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.tableAreas] });
        },
    });
};

export const useDeleteTableArea = () => {
    return useMutation({
        mutationFn: (id: string) => tableService.deleteTableArea(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.tableAreas] });
            // Tables may have been unlinked from deleted area
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.restaurantTables] });
        },
    });
};

// --- Restaurant Tables Hooks ---

export const useRestaurantTables = (params?: RestaurantTableListParams) => {
    return useQuery({
        queryKey: [QUERY_KEYS.restaurantTables, params],
        queryFn: () => tableService.getRestaurantTables(params),
        placeholderData: keepPreviousData, // Keep showing old data while new page loads
    });
};

export const useCreateRestaurantTable = () => {
    return useMutation({
        mutationFn: (data: CreateRestaurantTableRequest) => tableService.createRestaurantTable(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.restaurantTables] });
        },
    });
};

export const useUpdateRestaurantTable = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateRestaurantTableRequest) => tableService.updateRestaurantTable(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.restaurantTables] });
        },
    });
};

export const useDeleteRestaurantTable = () => {
    return useMutation({
        mutationFn: (id: string) => tableService.deleteRestaurantTable(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.restaurantTables] });
        },
    });
};

export const useTableQrCode = (tableId: string | null) => {
    return useQuery({
        queryKey: ['tableQrCode', tableId],
        queryFn: () => tableService.getTableQrCode(tableId!),
        enabled: !!tableId,
    });
};

export const useRegenerateTableQrCode = (tableId: string | null) => {
    return useMutation({
        mutationFn: () => tableService.getTableQrCode(tableId!, true),
        onSuccess: (data) => {
            queryClient.setQueryData(['tableQrCode', tableId], data);
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.restaurantTables] });
        },
    });
};

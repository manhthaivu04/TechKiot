import { useQuery, keepPreviousData } from '@tanstack/react-query';
import { inventoryService } from './inventory.service';
import type { InventoryListParams, TransactionListParams } from '@/types';

const QUERY_KEYS = {
    inventory: 'tenant_inventory',
    inventoryTransactions: 'tenant_inventory_transactions',
};

export const useInventory = (params?: InventoryListParams) => {
    return useQuery({
        queryKey: [QUERY_KEYS.inventory, params],
        queryFn: () => inventoryService.getInventory(params),
    });
};

export const useInventoryTransactions = (params?: TransactionListParams) => {
    return useQuery({
        queryKey: [QUERY_KEYS.inventoryTransactions, params],
        queryFn: () => inventoryService.getTransactions(params),
        placeholderData: keepPreviousData,
    });
};

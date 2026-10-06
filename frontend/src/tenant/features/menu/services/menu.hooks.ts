import { useMutation, useQuery } from '@tanstack/react-query';
import { menuService } from './menu.service';
import type {
    CreateItemGroupRequest,
    UpdateItemGroupRequest,
    CreateItemRequest,
    UpdateItemRequest,
    SyncRecipeRequest,
} from '@/types';
import { queryClient } from '@/api/query-client';

const QUERY_KEYS = {
    itemGroups: 'tenant_item_groups',
    items: 'tenant_items',
    ingredients: 'tenant_ingredients',
    itemDetail: 'tenant_item_detail',
};

// --- Item Groups Hooks ---

export const useItemGroups = () => {
    return useQuery({
        queryKey: [QUERY_KEYS.itemGroups],
        queryFn: menuService.getItemGroups,
    });
};

export const useCreateItemGroup = () => {
    return useMutation({
        mutationFn: (data: CreateItemGroupRequest) => menuService.createItemGroup(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.itemGroups] });
        },
    });
};

export const useUpdateItemGroup = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateItemGroupRequest) => menuService.updateItemGroup(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.itemGroups] });
        },
    });
};

export const useDeleteItemGroup = () => {
    return useMutation({
        mutationFn: (id: string) => menuService.deleteItemGroup(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.itemGroups] });
            // Might want to invalidate items too since some items belonged to this group
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.items] });
        },
    });
};

// --- Items Hooks ---

export const useItems = () => {
    return useQuery({
        queryKey: [QUERY_KEYS.items],
        queryFn: menuService.getItems,
    });
};

export const useCreateItem = () => {
    return useMutation({
        mutationFn: (data: CreateItemRequest) => menuService.createItem(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.items] });
        },
    });
};

export const useUpdateItem = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateItemRequest) => menuService.updateItem(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.items] });
        },
    });
};

export const useDeleteItem = () => {
    return useMutation({
        mutationFn: (id: string) => menuService.deleteItem(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.items] });
        },
    });
};

// --- Ingredients / Recipe (BOM) Hooks ---

export const useIngredients = () => {
    return useQuery({
        queryKey: [QUERY_KEYS.ingredients],
        queryFn: menuService.getIngredients,
    });
};

export const useItemDetail = (id: string) => {
    return useQuery({
        queryKey: [QUERY_KEYS.itemDetail, id],
        queryFn: () => menuService.getItem(id),
        enabled: !!id,
    });
};

export const useSyncRecipe = () => {
    return useMutation({
        mutationFn: ({ itemId, data }: { itemId: string; data: SyncRecipeRequest }) =>
            menuService.syncRecipe(itemId, data),
        onSuccess: (_data, variables) => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.items] });
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.itemDetail, variables.itemId] });
        },
    });
};

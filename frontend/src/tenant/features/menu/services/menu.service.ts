import apiClient from '@/api/http';
import type {
    ItemGroup,
    CreateItemGroupRequest,
    UpdateItemGroupRequest,
    Item,
    CreateItemRequest,
    UpdateItemRequest,
    SyncRecipeRequest,
} from '@/types';

// API Response type commonly used
export interface ApiResponse<T> {
    message: string;
    data: T;
}

export const menuService = {
    // --- Item Groups ---
    
    getItemGroups: () =>
        apiClient
            .get<ApiResponse<ItemGroup[]>>('/tenant/item-groups')
            .then((res) => res.data),

    getItemGroup: (id: string) =>
        apiClient
            .get<ApiResponse<ItemGroup>>(`/tenant/item-groups/${id}`)
            .then((res) => res.data),

    createItemGroup: (data: CreateItemGroupRequest) =>
        apiClient
            .post<ApiResponse<ItemGroup>>('/tenant/item-groups', data)
            .then((res) => res.data),

    updateItemGroup: (id: string, data: UpdateItemGroupRequest) =>
        apiClient
            .put<ApiResponse<ItemGroup>>(`/tenant/item-groups/${id}`, data)
            .then((res) => res.data),

    deleteItemGroup: (id: string) =>
        apiClient
            .delete<ApiResponse<null>>(`/tenant/item-groups/${id}`)
            .then((res) => res.data),

    // --- Items ---

    getItems: () =>
        apiClient
            .get<ApiResponse<Item[]>>('/tenant/items')
            .then((res) => res.data),

    getItem: (id: string) =>
        apiClient
            .get<ApiResponse<Item>>(`/tenant/items/${id}`)
            .then((res) => res.data),

    createItem: (data: CreateItemRequest) => {
        const formData = new FormData();
        
        if (data.item_group_id) formData.append('item_group_id', data.item_group_id);
        
        formData.append('name', data.name);
        formData.append('item_type', data.item_type || 'MENU_ITEM');
        formData.append('unit', data.unit);
        
        if (data.sale_price !== null && data.sale_price !== undefined) {
            formData.append('sale_price', data.sale_price.toString());
        }

        formData.append('availability_status', data.availability_status);
        
        if (data.image) formData.append('image', data.image);
        if (data.description) formData.append('description', data.description);
        if (data.cost_price) formData.append('cost_price', data.cost_price.toString());
        if (data.is_active !== undefined) formData.append('is_active', data.is_active ? '1' : '0');

        return apiClient
            .post<ApiResponse<Item>>('/tenant/items', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((res) => res.data);
    },

    updateItem: (id: string, data: UpdateItemRequest) => {
        const formData = new FormData();
        
        // Laravel requires _method=PUT for multipart form data updates
        formData.append('_method', 'PUT');

        if (data.item_group_id) formData.append('item_group_id', data.item_group_id);
        if (data.name) formData.append('name', data.name);
        if (data.item_type) formData.append('item_type', data.item_type);
        if (data.unit) formData.append('unit', data.unit);
        if (data.sale_price !== null && data.sale_price !== undefined) {
            formData.append('sale_price', data.sale_price.toString());
        }
        if (data.availability_status) formData.append('availability_status', data.availability_status);
        
        // If image is explicitly set to null, we might need a way to clear it, but usually sending empty is enough
        // If new image provided:
        if (data.image) formData.append('image', data.image);
        
        if (data.description !== undefined) formData.append('description', data.description || '');
        if (data.cost_price !== undefined) formData.append('cost_price', data.cost_price?.toString() || '');
        if (data.is_active !== undefined) formData.append('is_active', data.is_active ? '1' : '0');

        return apiClient
            .post<ApiResponse<Item>>(`/tenant/items/${id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            .then((res) => res.data);
    },

    deleteItem: (id: string) =>
        apiClient
            .delete<ApiResponse<null>>(`/tenant/items/${id}`)
            .then((res) => res.data),

    // --- Ingredients / Recipe (BOM) ---

    getIngredients: () =>
        apiClient
            .get<ApiResponse<Item[]>>('/tenant/items', {
                params: { item_type: 'INGREDIENT' },
            })
            .then((res) => res.data),

    syncRecipe: (itemId: string, data: SyncRecipeRequest) =>
        apiClient
            .post<ApiResponse<Item>>(`/tenant/items/${itemId}/recipe`, data)
            .then((res) => res.data),
};

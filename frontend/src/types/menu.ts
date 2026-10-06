export interface ItemGroup {
    id: string;
    restaurant_id: string;
    name: string;
    display_order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface CreateItemGroupRequest {
    name: string;
    display_order?: number;
    is_active?: boolean;
}

export interface UpdateItemGroupRequest {
    name?: string;
    display_order?: number;
    is_active?: boolean;
}

export type AvailabilityStatus = 'IN_STOCK' | 'OUT_OF_STOCK' | 'SUSPENDED';

export interface ItemIngredient {
    id: string;
    name: string;
    unit: string;
    cost_price: string;
    pivot: { quantity: string };
}

export interface SyncRecipeRequest {
    ingredients: { ingredient_id: string; quantity: number }[];
}

export interface Item {
    id: string;
    restaurant_id: string;
    item_group_id: string | null;
    name: string;
    description: string | null;
    image_url: string | null;
    item_type: string; // usually MENU_ITEM
    unit: string;
    sale_price: string; // String because decimals from backend usually come as strings
    cost_price?: string | null;
    availability_status: AvailabilityStatus;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    
    // Virtual relations
    item_group?: ItemGroup;
    ingredients?: ItemIngredient[];
}

export interface CreateItemRequest {
    item_group_id: string | null;
    name: string;
    item_type?: string;
    unit: string;
    sale_price: number | string | null;
    availability_status: AvailabilityStatus;
    image?: File | null;
    description?: string;
    cost_price?: number | string | null;
    is_active?: boolean;
}

export interface UpdateItemRequest extends Partial<CreateItemRequest> {
    _method?: 'PUT';
}

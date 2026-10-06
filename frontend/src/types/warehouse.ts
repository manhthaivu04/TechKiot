export interface Warehouse {
    id: string;
    restaurant_id: string;
    name: string;
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

export interface CreateWarehouseRequest {
    name: string;
    is_default?: boolean;
}

export interface UpdateWarehouseRequest {
    name?: string;
    is_default?: boolean;
}

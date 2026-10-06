// --- Table Status ---

export type TableStatus = 'available' | 'occupied' | 'reserved' | 'inactive';

export const TABLE_STATUS_OPTIONS: {
    value: TableStatus;
    label: string;
    color: string;
    antColor: 'success' | 'error' | 'warning' | 'default';
}[] = [
    { value: 'available', label: 'Trống', color: '#22c55e', antColor: 'success' },
    { value: 'occupied', label: 'Đang sử dụng', color: '#ef4444', antColor: 'error' },
    { value: 'reserved', label: 'Đã đặt trước', color: '#f59e0b', antColor: 'warning' },
    { value: 'inactive', label: 'Ngưng hoạt động', color: '#6b7280', antColor: 'default' },
];

// --- Table Area ---

export interface TableArea {
    id: string;
    restaurant_id: string;
    name: string;
    description: string | null;
    display_order: number;
    created_at: string;
    updated_at: string;
}

export interface CreateTableAreaRequest {
    name: string;
    description?: string | null;
    display_order?: number;
}

export interface UpdateTableAreaRequest {
    name?: string;
    description?: string | null;
    display_order?: number;
}

// --- Restaurant Table ---

export interface RestaurantTable {
    id: string;
    restaurant_id: string;
    area_id: string | null;
    area: TableArea | null;
    uid: string;
    name: string;
    capacity: number;
    status: TableStatus;
    status_label: string;
    qr_token: string; // Internal only — NEVER display in UI
    qr_code_url?: string | null;
    created_at: string;
    updated_at: string;
}

export interface CreateRestaurantTableRequest {
    name: string;
    area_id?: string | null;
    uid?: string;
    capacity?: number;
    status?: TableStatus;
}

export interface UpdateRestaurantTableRequest {
    name?: string;
    area_id?: string | null;
    uid?: string;
    capacity?: number;
    status?: TableStatus;
}

export interface RestaurantTableListParams {
    area_id?: string;
    status?: TableStatus;
    search?: string;
    per_page?: number;
    page?: number;
}

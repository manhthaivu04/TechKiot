// ===== Public Menu API Types =====

export interface PublicRestaurant {
    id: string;
    name: string;
    slug: string;
    logo_url: string | null;
    banner_url: string | null;
    description: string | null;
    address: string | null;
    phone: string | null;
}

export interface PublicItem {
    id: string;
    name: string;
    description: string | null;
    image_url: string | null;
    sale_price: string | number;
    availability_status: 'IN_STOCK' | 'OUT_OF_STOCK' | 'SUSPENDED';
    is_active: boolean;
}

export interface PublicItemGroup {
    group_id: string;
    group_name: string;
    display_order: number;
    items: PublicItem[];
}


export interface PublicTableInfo {
    id: string;
    name: string;
    capacity: number;
    status: string;
    area?: { id: string; name: string } | null;
}

export interface PublicMenuResponse {
    restaurant?: PublicRestaurant;
    item_groups: PublicItemGroup[];
    /** Chỉ có khi type=qr_table */
    table?: PublicTableInfo | null;
    /** Đơn hàng đang mở của bàn (nếu có) */
    active_order?: Order | null;
}

// ===== Cart Types =====

export interface CartItem {
    item_id: string;
    name: string;
    image_url: string | null;
    sale_price: number;
    quantity: number;
    note: string;
}

// ===== Order Types =====

export type OrderSourceChannel = 'qr_static' | 'qr_table';

export interface PlaceOrderRequest {
    public_token: string;
    source_channel: OrderSourceChannel;
    customer_name?: string;
    customer_phone?: string;
    note?: string;
    items: {
        item_id: string;
        quantity: number;
        note: string;
    }[];
}

export type OrderStatus = 'open' | 'cooking' | 'served' | 'paid' | 'cancelled';

export interface OrderItem {
    id: string;
    item_id: string;
    item_name?: string; // Backend
    name?: string; // Legacy
    quantity: number;
    unit_price?: string | number; // Backend
    line_total?: string | number; // Backend
    price?: string | number;
    note?: string;
}

export interface Order {
    id: string;
    restaurant_id: string;
    status: OrderStatus;
    source_channel: OrderSourceChannel;
    items?: OrderItem[];
    total_amount?: string | number;
    final_amount?: string | number;
    created_at: string;
    updated_at: string;
}

// ===== QR Params =====

export type QrType = 'qr_static' | 'qr_table';

export interface QrMenuParams {
    public_token: string;
    type: QrType;
}

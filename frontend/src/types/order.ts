// ============================================================
// Order Types — KiotTay POS System
// ============================================================

import type { PaginationMeta } from './api';

export type OrderStatus =
    | 'open'
    | 'cooking'
    | 'served'
    | 'paid'
    | 'cancelled'
    // Fallback cho tương thích code cũ nếu có
    | 'PENDING'
    | 'CONFIRMED'
    | 'PREPARING'
    | 'READY'
    | 'COMPLETED'
    | 'CANCELLED';

export type ServiceType = 'takeaway' | 'dine_in' | 'delivery' | 'TAKEAWAY' | 'DINE_IN' | 'DELIVERY';

export type PaymentMethod = 'CASH' | 'BANK_TRANSFER' | 'CARD' | 'OTHER';

export type PaymentStatus = 'PENDING' | 'PAID' | 'REFUNDED';

// ---- Request payloads ----

export interface OrderItemPayload {
    item_id: string;
    quantity: number;
    unit_price: number;
    note?: string;
}

export interface CreateOrderRequest {
    source_channel: 'cashier' | 'qr_static' | 'qr_table';
    service_type: ServiceType;
    table_id?: string;
    customer_name?: string;
    customer_phone?: string;
    customer_reference?: string;
    guest_count?: number;
    items: OrderItemPayload[];
    note?: string;
}

export interface UpdateOrderStatusRequest {
    status: OrderStatus;
}

export interface CreatePaymentRequest {
    payment_method: 'cash' | 'card' | 'transfer' | 'ewallet';
    amount?: number;
    reference_no?: string;
}

export interface PaymentRecord {
    id: string;
    amount: number;
    payment_method: 'cash' | 'card' | 'transfer' | 'ewallet';
    method_label: string;
    reference_no: string | null;
    paid_at: string;
}

// ---- Resource shapes (from API) ----

export type OrderItemStatus = 'pending' | 'cooking' | 'ready' | 'served' | 'cancelled';

export interface OrderItem {
    id: string;
    item_id: string;
    item_name: string;
    quantity: number;
    unit_price: string;
    line_total: string;
    note?: string | null;
    status?: OrderItemStatus;
    created_at: string;
}

export interface Payment {
    id: string;
    method: PaymentMethod;
    method_label?: string;
    amount: string;
    status: PaymentStatus;
    note?: string | null;
    created_at: string;
}

export interface Order {
    id: string;
    order_code: string;
    source_channel: string;
    service_type: ServiceType;
    service_type_label?: string;
    status: OrderStatus;
    status_label: string;
    table_id?: string | null;
    customer_name: string | null;
    customer_phone: string | null;
    customer_reference: string | null;
    guest_count: number | null;
    note?: string | null;
    subtotal_amount: string;
    discount_amount: string;
    tax_amount: string;
    final_amount: string;
    items: OrderItem[];
    payments: Payment[];
    created_at: string;
    updated_at: string;
}

// ---- Pagination meta ----

export interface PaginatedOrdersResponse {
    data: Order[];
    meta: PaginationMeta;
}

// ---- Query params ----

export interface OrderListParams {
    page?: number;
    per_page?: number;
    status?: OrderStatus | '';
    service_type?: ServiceType | '';
    search?: string;
}

// ---- Status config (label + color) ----

export const ORDER_STATUS_CONFIG: Record<
    string,
    { label: string; color: string; bg: string }
> = {
    // Backend mới
    open:      { label: 'Chờ xác nhận', color: '#d97706', bg: '#fef3c7' },
    cooking:   { label: 'Đang làm',     color: '#7c3aed', bg: '#ede9fe' },
    served:    { label: 'Đã lên món',     color: '#059669', bg: '#d1fae5' },
    paid:      { label: 'Hoàn thành',   color: '#065f46', bg: '#a7f3d0' },
    cancelled: { label: 'Đã huỷ',       color: '#dc2626', bg: '#fee2e2' },
    
    // Legacy / Dự phòng
    PENDING:   { label: 'Chờ xác nhận', color: '#d97706', bg: '#fef3c7' },
    CONFIRMED: { label: 'Đã xác nhận',  color: '#2563eb', bg: '#dbeafe' },
    PREPARING: { label: 'Đang làm',     color: '#7c3aed', bg: '#ede9fe' },
    READY:     { label: 'Đã lên món',     color: '#059669', bg: '#d1fae5' },
    COMPLETED: { label: 'Hoàn thành',   color: '#065f46', bg: '#a7f3d0' },
    CANCELLED: { label: 'Đã huỷ',       color: '#dc2626', bg: '#fee2e2' },
};

export const SERVICE_TYPE_CONFIG: Record<
    string,
    { label: string; icon: string }
> = {
    takeaway: { label: 'Mang đi',   icon: '🛍️' },
    dine_in:  { label: 'Tại bàn',   icon: '🍽️' },
    delivery: { label: 'Giao hàng', icon: '🚀' },
    
    TAKEAWAY: { label: 'Mang đi',   icon: '🛍️' },
    DINE_IN:  { label: 'Tại bàn',   icon: '🍽️' },
    DELIVERY: { label: 'Giao hàng', icon: '🚀' },
};
export interface Table {
    id: string;
    name: string;
    status: 'available' | 'occupied' | 'reserved';
}

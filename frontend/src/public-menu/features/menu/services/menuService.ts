import axios from 'axios';
import type { PublicMenuResponse, PlaceOrderRequest, Order, QrType } from '@/types/public-menu';

// Public API client — không cần Bearer token
const publicApi = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    timeout: 30000,
});

/**
 * GET /public/menu?public_token={token}&type={type}
 *
 * Backend trả về:
 * {
 *   success: true,
 *   data: {
 *     restaurant: {...},
 *     item_groups: [...],
 *     table: {...},          // chỉ khi type=qr_table
 *     active_order: {...}    // đơn đang mở của bàn (nếu có)
 *   }
 * }
 */
export const fetchPublicMenu = async (
    public_token: string,
    type: QrType
): Promise<PublicMenuResponse> => {
    const { data } = await publicApi.get('/public/menu', {
        params: { public_token, type },
    });

    // Hỗ trợ 2 format backend:
    // 1. { data: { item_groups: [], restaurant, table, active_order } }
    // 2. { data: [] }  (legacy — chỉ item_groups trả về mảng trực tiếp)
    const payload = data.data;

    if (Array.isArray(payload)) {
        // Legacy format: data là mảng item_groups
        return { item_groups: payload };
    }

    return {
        restaurant: payload?.restaurant ?? undefined,
        item_groups: Array.isArray(payload?.item_groups) ? payload.item_groups : [],
        table: payload?.table ?? null,
        active_order: payload?.active_order ?? null,
    };
};

/**
 * POST /public/orders
 */
export const placeOrder = async (payload: PlaceOrderRequest): Promise<Order> => {
    const { data } = await publicApi.post('/public/orders', payload);
    return data.data ?? data;
};

/**
 * GET /public/orders/{id}
 */
export const fetchOrderStatus = async (orderId: string): Promise<Order> => {
    const { data } = await publicApi.get(`/public/orders/${orderId}`);
    return data.data ?? data;
};

/**
 * POST /public/orders/{id}/items
 */
export const addOrderItems = async (
    orderId: string,
    payload: { items: { item_id: string; quantity: number; note: string }[] }
): Promise<Order> => {
    const { data } = await publicApi.post(`/public/orders/${orderId}/items`, payload);
    return data.data ?? data;
};

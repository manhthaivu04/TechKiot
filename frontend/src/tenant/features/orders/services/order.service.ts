import apiClient from '@/api/http';
import type {
    Order,
    PaymentRecord,
    CreateOrderRequest,
    UpdateOrderStatusRequest,
    CreatePaymentRequest,
    PaginatedOrdersResponse,
    OrderListParams,
} from '@/types';

export interface AddOrderItemsRequest {
    items: { item_id: string; quantity: number; note?: string }[];
}

// ---- Response wrappers ----

export interface OrderApiResponse {
    message: string;
    data: Order;
}

export interface PaymentApiResponse {
    message: string;
    data: PaymentRecord;
}

export interface StatusApiResponse {
    message: string;
    data: Order;
}

// ---- Service ----

export const orderService = {
    /**
     * GET /api/tenant/orders
     * Lấy danh sách đơn hàng (có phân trang + filter)
     */
    getOrders: (params?: OrderListParams): Promise<PaginatedOrdersResponse> =>
        apiClient
            .get<any>('/tenant/orders', { params })
            .then((res) => ({
                data: res.data.data.items,
                meta: res.data.data.meta,
            })),

    /**
     * GET /api/tenant/orders/{id}
     * Lấy chi tiết đơn hàng (kèm items và payments)
     */
    getOrder: (id: string): Promise<{ data: Order }> =>
        apiClient
            .get<{ data: Order }>(`/tenant/orders/${id}`)
            .then((res) => res.data),

    /**
     * POST /api/tenant/orders
     * Tạo đơn hàng mới
     */
    createOrder: (data: CreateOrderRequest): Promise<OrderApiResponse> =>
        apiClient
            .post<OrderApiResponse>('/tenant/orders', data)
            .then((res) => res.data),

    /**
     * PATCH /api/tenant/orders/{id}/status
     * Cập nhật trạng thái đơn hàng
     */
    updateOrderStatus: (
        id: string,
        data: UpdateOrderStatusRequest
    ): Promise<StatusApiResponse> =>
        apiClient
            .patch<StatusApiResponse>(`/tenant/orders/${id}/status`, data)
            .then((res) => res.data),

    /**
     * POST /api/tenant/orders/{id}/payments
     * Ghi nhận thanh toán cho đơn hàng
     */
    createPayment: (
        orderId: string,
        data: CreatePaymentRequest
    ): Promise<PaymentApiResponse> =>
        apiClient
            .post<PaymentApiResponse>(`/tenant/orders/${orderId}/payments`, data)
            .then((res) => res.data),

    /**
     * POST /api/tenant/orders/{id}/items
     * Gọi thêm món vào đơn hàng đang mở
     */
    addItems: (orderId: string, data: AddOrderItemsRequest): Promise<OrderApiResponse> =>
        apiClient
            .post<OrderApiResponse>(`/tenant/orders/${orderId}/items`, data)
            .then((res) => res.data),

    /**
     * GET /api/tenant/orders/{id}/payments
     * Lấy lịch sử thanh toán của đơn hàng
     */
    getPayments: (orderId: string): Promise<PaymentRecord[]> =>
        apiClient
            .get<{ data: PaymentRecord[] }>(`/tenant/orders/${orderId}/payments`)
            .then((res) => res.data.data),
};

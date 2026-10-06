import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { orderService } from './order.service';
import type {
    CreateOrderRequest,
    UpdateOrderStatusRequest,
    CreatePaymentRequest,
    OrderListParams,
} from '@/types';

export const ORDER_KEYS = {
    all: ['tenant_orders'] as const,
    lists: () => [...ORDER_KEYS.all, 'list'] as const,
    list: (params: OrderListParams) => [...ORDER_KEYS.lists(), params] as const,
    details: () => [...ORDER_KEYS.all, 'detail'] as const,
    detail: (id: string) => [...ORDER_KEYS.details(), id] as const,
    payments: (id: string) => [...ORDER_KEYS.detail(id), 'payments'] as const,
};

/**
 * Lấy danh sách đơn hàng có phân trang + filter
 */
export const useOrders = (params?: OrderListParams) => {
    return useQuery({
        queryKey: ORDER_KEYS.list(params ?? {}),
        queryFn: () => orderService.getOrders(params),
        staleTime: 30_000,
    });
};

/**
 * Lấy chi tiết 1 đơn hàng
 */
export const useOrder = (id: string, enabled = true) => {
    return useQuery({
        queryKey: ORDER_KEYS.detail(id),
        queryFn: () => orderService.getOrder(id),
        enabled: !!id && enabled,
    });
};

/**
 * Lấy lịch sử thanh toán của đơn hàng
 */
export const useOrderPayments = (id: string, enabled = true) => {
    return useQuery({
        queryKey: ORDER_KEYS.payments(id),
        queryFn: () => orderService.getPayments(id),
        enabled: !!id && enabled,
    });
};

/**
 * Tạo đơn hàng mới
 */
export const useCreateOrder = () => {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (data: CreateOrderRequest) => orderService.createOrder(data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
        },
    });
};

/**
 * Cập nhật trạng thái đơn
 */
export const useUpdateOrderStatus = () => {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({ id, data }: { id: string; data: UpdateOrderStatusRequest }) =>
            orderService.updateOrderStatus(id, data),
        onSuccess: (_, { id }) => {
            qc.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
            qc.invalidateQueries({ queryKey: ORDER_KEYS.detail(id) });
        },
    });
};

/**
 * Ghi nhận thanh toán
 */
export const useCreatePayment = () => {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({
            orderId,
            data,
        }: {
            orderId: string;
            data: CreatePaymentRequest;
        }) => orderService.createPayment(orderId, data),
        onSuccess: (_, { orderId }) => {
            qc.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
            qc.invalidateQueries({ queryKey: ORDER_KEYS.detail(orderId) });
            qc.invalidateQueries({ queryKey: ORDER_KEYS.payments(orderId) });
        },
    });
};

/**
 * Gọi thêm món vào đơn hàng đang mở
 */
export const useAddOrderItems = () => {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: ({
            orderId,
            items,
        }: {
            orderId: string;
            items: { item_id: string; quantity: number; note?: string }[];
        }) => orderService.addItems(orderId, { items }),
        onSuccess: (_, { orderId }) => {
            qc.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
            qc.invalidateQueries({ queryKey: ORDER_KEYS.detail(orderId) });
        },
    });
};

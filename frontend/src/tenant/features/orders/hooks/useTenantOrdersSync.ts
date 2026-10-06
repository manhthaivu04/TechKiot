import { useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { App } from 'antd';
import { getEcho } from '@/lib/echoClient';
import { ORDER_KEYS } from '../services/order.hooks';
import { useHighlightStore } from '../stores/highlight.store';
import { notifyNewOrder, notifyOrderItemsAdded, notifyOrderServed } from '../utils/orderNotification';
import { useTableNameMap } from '../hooks/useTableNameMap';
import { resolveTableName } from '../utils/orderDisplay';
import type { Order, OrderItem } from '@/types';

export const useTenantOrdersSync = (restaurantId: string | undefined) => {
    const queryClient = useQueryClient();
    const { message } = App.useApp();
    const tableNames = useTableNameMap();
    const tableNamesRef = useRef(tableNames);

    useEffect(() => {
        tableNamesRef.current = tableNames;
    }, [tableNames]);

    useEffect(() => {
        if (!restaurantId) return;

        const channelName = `restaurant.${restaurantId}.kitchen`;
        const cashierChannelName = `restaurant.${restaurantId}.cashier`;
        const echo = getEcho();
        const channel = echo.channel(channelName);
        const cashierChannel = echo.channel(cashierChannelName);

        // ── Cashier channel events ─────────────────────────────────────────────

        // Lắng nghe đơn QR mới cho Thu ngân (Cashier)
        cashierChannel.listen('.NewQrOrder', (payload: any) => {
            console.log('--- RAW EVENT .NewQrOrder ---', payload);

            const orderData: Order | undefined = payload?.order || (payload?.id ? payload : undefined);

            if (!orderData) {
                console.error('[WebSockets] Không tìm thấy dữ liệu order trong event .NewQrOrder', payload);
                return;
            }

            console.log('Có đơn QR mới từ khách:', orderData);

            queryClient.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
            useHighlightStore.getState().addHighlight(orderData.id, 'NEW_ORDER');

            notifyNewOrder({
                orderId: orderData.id,
                orderCode: orderData.order_code,
                title: 'Đơn QR mới!',
                body: `Khách vừa đặt đơn ${orderData.order_code}`,
            });
        });

        // Lắng nghe khách gọi thêm món qua QR — bắn từ cashier channel, payload có newItems
        cashierChannel.listen('.OrderItemsAdded', (e: { order: Order; newItems: OrderItem[] }) => {
            console.log('[WebSockets] .OrderItemsAdded (cashier):', e);

            // Cập nhật ngay cache detail của order đang mở (nếu có) — không cần chờ refetch
            queryClient.setQueryData(ORDER_KEYS.detail(e.order.id), (old: any) => {
                if (!old?.data) return old;
                const existingIds = new Set((old.data.items || []).map((i: OrderItem) => i.id));
                const brandNew = (e.newItems || []).filter(i => !existingIds.has(i.id));
                return {
                    ...old,
                    data: {
                        ...old.data,
                        ...e.order,
                        items: [...(old.data.items || []), ...brandNew],
                    },
                };
            });

            // Invalidate để đồng bộ list Kanban và reload detail sạch từ server
            queryClient.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
            queryClient.invalidateQueries({ queryKey: ORDER_KEYS.detail(e.order.id) });

            useHighlightStore.getState().addHighlight(e.order.id, 'NEW_ITEM');

            notifyOrderItemsAdded({
                orderId: e.order.id,
                orderCode: e.order.order_code,
                newItems: e.newItems,
            });
        });

        // ── Kitchen channel events ─────────────────────────────────────────────

        // Lắng nghe đơn hàng mới — chỉ đồng bộ dữ liệu, không báo toast (đã xử lý ở .NewQrOrder / .order.created)
        channel.listen('.OrderPlaced', (e: { order: Order }) => {
            queryClient.invalidateQueries({ queryKey: ORDER_KEYS.lists() });

            const isQrOrder =
                e.order.source_channel === 'qr_static' || e.order.source_channel === 'qr_table';

            if (isQrOrder) {
                useHighlightStore.getState().addHighlight(e.order.id, 'NEW_ORDER');
            }
        });

        // Lắng nghe thay đổi trạng thái (bao gồm cả auto lùi served → cooking khi gọi thêm)
        channel.listen('.OrderStatusTransitioned', (e: { order: Order; from: string; to: string }) => {
            queryClient.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
            queryClient.invalidateQueries({ queryKey: ORDER_KEYS.detail(e.order.id) });

            // Thông báo khi backend tự lùi trạng thái do khách gọi thêm
            if (e.from === 'served' && e.to === 'cooking') {
                message.info(`Đơn ${e.order.order_code} tự động chuyển về "Đang nấu" do có món mới.`);
            }

            // Thông báo khi bếp chuyển đơn sang served (Đã lên món)
            if (e.to === 'served' || e.to === 'READY') {
                const tableName = resolveTableName(e.order, tableNamesRef.current);
                notifyOrderServed({
                    orderId: e.order.id,
                    orderCode: e.order.order_code,
                    tableName,
                });
            }
        });

        return () => {
            channel.stopListening('.OrderPlaced');
            channel.stopListening('.OrderStatusTransitioned');
            echo.leave(channelName);

            cashierChannel.stopListening('.NewQrOrder');
            cashierChannel.stopListening('.OrderItemsAdded');
            echo.leave(cashierChannelName);
        };
    }, [restaurantId, queryClient, message]);
};

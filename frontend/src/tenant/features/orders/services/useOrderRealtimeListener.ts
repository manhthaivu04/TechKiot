/**
 * useOrderRealtimeListener.ts
 * Custom Hook lắng nghe event "order.created" realtime từ Backend
 * qua kênh Laravel Echo: `restaurant.{restaurant_id}`
 */

import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { getEcho } from '@/lib/echoClient';
import type { OrderNotificationPayload } from '@/store/useNotificationStore';
import { ORDER_KEYS } from '@/tenant/features/orders/services/order.hooks';
import { notifyFromOrderCreatedPayload } from '@/tenant/features/orders/utils/orderNotification';

// ─── Hook ────────────────────────────────────────────────────────────────────

interface UseOrderRealtimeListenerOptions {
    restaurantId: string | null | undefined;
    enabled?: boolean;
}

export const useOrderRealtimeListener = ({
    restaurantId,
    enabled = true,
}: UseOrderRealtimeListenerOptions) => {
    const queryClient = useQueryClient();

    useEffect(() => {
        if (!enabled || !restaurantId) {
            console.warn('[Realtime] Không subscribe: enabled=', enabled, 'restaurantId=', restaurantId);
            return;
        }

        const channelName = `restaurant.${restaurantId}`;
        const echo = getEcho();

        console.info('[Realtime] Đang subscribe channel:', channelName);

        // ── Subscribe channel ───────────────────────────────────────────────
        // FIX: Thử dùng channel() (Public) trước.
        // Nếu backend dùng Private Channel → đổi thành echo.private(channelName)
        // Lưu ý: Private Channel yêu cầu route /broadcasting/auth trên backend
        const channel = echo.channel(channelName);

        // Log trạng thái kết nối Pusher để debug
        const pusher = echo.connector?.pusher;
        if (pusher) {
            pusher.connection.bind('connected', () => {
                console.info('[Realtime] ✅ WebSocket connected!');
            });
            pusher.connection.bind('error', (err: unknown) => {
                console.error('[Realtime] ❌ WebSocket error:', err);
            });
            pusher.connection.bind('state_change', (states: { previous: string; current: string }) => {
                console.info('[Realtime] WS state:', states.previous, '→', states.current);
            });
        }

        // ── Lắng nghe event ─────────────────────────────────────────────────
        // FIX: Tên event KHÔNG có dấu chấm ở đầu khi dùng Public Channel.
        // - Public Channel:  channel.listen('OrderCreated', ...)  hoặc '.order.created'
        // - Private Channel: channel.listen('.order.created', ...)
        //
        // Backend Laravel broadcastAs() = 'order.created' → Frontend dùng '.order.created'
        // Nếu broadcastAs() không được định nghĩa → dùng tên class: 'App\\Events\\OrderCreated'
        channel.listen('.order.created', (payload: OrderNotificationPayload) => {
            console.info('[Realtime] 📦 Nhận event order.created:', payload);

            notifyFromOrderCreatedPayload(payload);

            // Trigger refresh danh sách đơn hàng
            queryClient.invalidateQueries({ queryKey: ORDER_KEYS.lists() });
        });

        // Cleanup khi unmount hoặc restaurantId thay đổi
        return () => {
            console.info('[Realtime] Unsubscribe channel:', channelName);
            echo.leave(channelName);
        };

        // FIX: Dependency array chỉ có restaurantId + enabled + queryClient
        // → KHÔNG đưa Zustand actions vào dep array (chúng không stable)
    }, [restaurantId, enabled, queryClient]);
};

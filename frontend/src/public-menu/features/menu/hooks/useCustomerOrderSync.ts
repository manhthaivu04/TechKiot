import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { getEcho } from '@/lib/echoClient';

export const useCustomerOrderSync = (orderId: string | undefined) => {
    const queryClient = useQueryClient();

    useEffect(() => {
        if (!orderId) return;

        const channelName = `order.${orderId}`;
        const echo = getEcho();
        const channel = echo.channel(channelName);

        channel.listen('.OrderStatusTransitioned', () => {
            // Khi có thay đổi trạng thái, invalidate query để fetch lại data mới
            queryClient.invalidateQueries({ queryKey: ['orders', 'detail', orderId] });
        });

        return () => {
            channel.stopListening('.OrderStatusTransitioned');
            echo.leave(channelName);
        };
    }, [orderId, queryClient]);
};

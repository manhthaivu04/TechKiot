import { antdNotification } from '@/lib/antdStatic';
import { useNotificationStore } from '@/store/useNotificationStore';
import type { OrderNotificationPayload } from '@/store/useNotificationStore';

const ORDER_NOTIFY_SOUND = '/sounds/order_notify.mp3';

/** Khóa chống trùng thống nhất — mọi event đơn mới đều dùng chung */
export const newOrderDedupeKey = (orderId: string) => `order:new:${orderId}`;

const playBeepFallback = (level: 'default' | 'loud') => {
    try {
        const ctx = new AudioContext();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        oscillator.type = 'sine';
        oscillator.frequency.value = level === 'loud' ? 880 : 660;
        gainNode.gain.value = level === 'loud' ? 0.8 : 0.4;
        oscillator.start();
        oscillator.stop(ctx.currentTime + 0.3);
    } catch {
        // Thiết bị không hỗ trợ → bỏ qua
    }
};

export const playNotificationSound = (level: 'default' | 'loud' = 'default') => {
    try {
        const audio = new Audio(ORDER_NOTIFY_SOUND);
        audio.volume = level === 'loud' ? 1.0 : 0.6;
        audio.play().catch(() => {
            playBeepFallback(level);
        });
    } catch {
        playBeepFallback(level);
    }
};

interface NotifyNewOrderOptions {
    orderId: string;
    orderCode: string;
    title?: string;
    body?: string;
    sound?: boolean;
    soundLevel?: 'default' | 'loud';
    badgeDelta?: number;
    priority?: 'high' | 'normal';
}

/** Hiển thị toast + âm thanh + badge cho đơn mới. Chống trùng theo idempotency key. */
export const notifyNewOrder = ({
    orderId,
    orderCode,
    title = 'Đơn hàng mới!',
    body,
    sound = true,
    soundLevel = 'default',
    badgeDelta = 1,
    priority = 'normal',
}: NotifyNewOrderOptions) => {
    const key = newOrderDedupeKey(orderId);
    const store = useNotificationStore.getState();

    if (store.isProcessed(key)) {
        console.info('[Notification] Bỏ qua đơn mới (đã xử lý):', orderId);
        return false;
    }
    store.markProcessed(key);

    if (sound) {
        playNotificationSound(soundLevel);
    }

    antdNotification.open({
        message: title,
        description: body ?? `Mã đơn: ${orderCode}`,
        placement: 'topRight',
        duration: 8,
        type: priority === 'high' ? 'warning' : 'info',
        style: { borderLeft: '4px solid #059669' },
    });

    store.incrementBadge(badgeDelta);
    return true;
};

/** Xử lý payload chuẩn từ event `.order.created` */
export const notifyFromOrderCreatedPayload = (payload: OrderNotificationPayload) => {
    const { notification, order_summary } = payload;
    const orderId = order_summary?.order_id;
    const orderCode = order_summary?.order_code;

    if (!orderId || !orderCode) {
        console.warn('[Notification] Payload order.created thiếu order_id/order_code:', payload);
        return false;
    }

    const shown = notifyNewOrder({
        orderId,
        orderCode,
        title: notification?.title ?? 'Đơn hàng mới!',
        body: notification?.body ?? `Mã đơn: ${orderCode}`,
        sound: notification?.sound ?? true,
        soundLevel: notification?.sound_level ?? 'default',
        badgeDelta: notification?.badge_delta ?? 1,
        priority: notification?.priority ?? 'normal',
    });

    if (shown) {
        useNotificationStore.getState().setLastNotification(payload);
    }

    return shown;
};

interface NotifyItemsAddedOptions {
    orderId: string;
    orderCode: string;
    newItems?: { id: string }[];
}

/** Toast + âm thanh khi khách gọi thêm món vào đơn đang mở */
export const notifyOrderItemsAdded = ({
    orderId,
    orderCode,
    newItems = [],
}: NotifyItemsAddedOptions) => {
    const itemKey = newItems.map((i) => i.id).sort().join(',') || Date.now().toString();
    const key = `items-added:${orderId}:${itemKey}`;
    const store = useNotificationStore.getState();

    if (store.isProcessed(key)) {
        console.info('[Notification] Bỏ qua gọi thêm (đã xử lý):', key);
        return false;
    }
    store.markProcessed(key);

    playNotificationSound('default');

    const newCount = newItems.length;
    antdNotification.open({
        message: 'Khách vừa gọi thêm món!',
        description:
            newCount > 0
                ? `Đơn ${orderCode} có ${newCount} món mới. Vui lòng chuẩn bị!`
                : `Đơn ${orderCode} có món mới được thêm vào. Vui lòng chuẩn bị!`,
        placement: 'topRight',
        duration: 8,
        type: 'warning',
        style: { borderLeft: '4px solid #f97316' },
    });

    return true;
};

interface NotifyOrderServedOptions {
    orderId: string;
    orderCode: string;
    tableName?: string | null;
}

/** Toast + âm thanh khi bếp chuyển trạng thái đơn hàng sang served (Đã lên món) */
export const notifyOrderServed = ({
    orderId,
    orderCode,
    tableName,
}: NotifyOrderServedOptions) => {
    const key = `order-served:${orderId}`;
    const store = useNotificationStore.getState();

    if (store.isProcessed(key)) {
        console.info('[Notification] Bỏ qua thông báo lên món (đã xử lý):', key);
        return false;
    }
    store.markProcessed(key);

    playNotificationSound('default');

    const tableInfo = tableName ? ` (Bàn: ${tableName})` : '';

    antdNotification.open({
        message: 'Món ăn đã sẵn sàng!',
        description: `Bếp đã chuẩn bị xong đơn #${orderCode}${tableInfo}. Vui lòng phục vụ khách!`,
        placement: 'topRight',
        duration: 8,
        type: 'success',
        style: { borderLeft: '4px solid #10b981' },
    });

    return true;
};

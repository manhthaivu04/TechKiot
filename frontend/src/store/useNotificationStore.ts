/**
 * useNotificationStore.ts
 * Zustand store toàn cục để quản lý:
 *   - Badge số đơn chưa đọc (newOrderBadge)
 *   - Danh sách idempotency_key đã xử lý (chống duplicate)
 *   - Thông báo gần nhất (lastNotification) để component hiển thị
 */

import { create } from 'zustand';

// ─── Types ────────────────────────────────────────────────────────────────────

/** Cấu trúc notification nhận từ Backend */
export interface OrderNotificationPayload {
    event_name: string;
    channel: string;
    notification: {
        title: string;
        body: string;
        sound: boolean;
        sound_level: 'default' | 'loud';
        badge_delta: number;
        priority: 'high' | 'normal';
        locale: string;
    };
    order_summary: {
        order_id: string;
        order_code: string;
        table_id: string | null;
        items_preview: { name: string; quantity: number }[];
        final_amount: number;
        created_at: string;
    };
    meta: {
        idempotency_key: string;
        ttl_seconds: number;
    };
}

// ─── Store Interface ──────────────────────────────────────────────────────────

interface NotificationStore {
    /** Số đơn hàng mới chưa đọc (hiển thị ở badge) */
    newOrderBadge: number;

    /** Set idempotency_key đã xử lý → chống gửi event trùng */
    processedKeys: Set<string>;

    /** Payload của notification cuối cùng nhận được */
    lastNotification: OrderNotificationPayload | null;

    // ── Actions ──
    /** Tăng badge theo badge_delta từ payload */
    incrementBadge: (delta: number) => void;
    /** Reset badge về 0 (khi thu ngân mở tab đơn hàng) */
    resetBadge: () => void;
    /** Đánh dấu idempotency_key đã xử lý */
    markProcessed: (key: string) => void;
    /** Kiểm tra key đã xử lý chưa */
    isProcessed: (key: string) => boolean;
    /** Lưu notification mới nhất vào store */
    setLastNotification: (payload: OrderNotificationPayload) => void;
}

// ─── Store ────────────────────────────────────────────────────────────────────

export const useNotificationStore = create<NotificationStore>((set, get) => ({
    newOrderBadge: 0,
    processedKeys: new Set<string>(),
    lastNotification: null,

    incrementBadge: (delta) =>
        set((state) => ({ newOrderBadge: state.newOrderBadge + delta })),

    resetBadge: () => set({ newOrderBadge: 0 }),

    markProcessed: (key) =>
        set((state) => {
            // Giới hạn set tối đa 200 key để tránh memory leak
            const next = new Set(state.processedKeys);
            if (next.size >= 200) {
                // Xoá key cũ nhất (lấy phần tử đầu iterator)
                const firstKey = next.values().next().value;
                if (firstKey) next.delete(firstKey);
            }
            next.add(key);
            return { processedKeys: next };
        }),

    isProcessed: (key) => get().processedKeys.has(key),

    setLastNotification: (payload) => set({ lastNotification: payload }),
}));

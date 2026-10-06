/**
 * useQrSessionStore — State toàn cục cho phiên quét QR của khách hàng.
 *
 * Lưu thông tin bàn đang ngồi và đơn hàng đang mở để:
 * 1. Hiển thị banner "Bạn đang ngồi tại Bàn: X"
 * 2. Khi gọi thêm món → gọi API addItems thay vì createOrder (Collaborative Ordering)
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { PublicTableInfo, Order } from '@/types/public-menu';

interface QrSessionState {
    /** Token QR hiện tại (public_token) */
    publicToken: string | null;
    /** Loại QR: qr_static | qr_table */
    qrType: 'qr_static' | 'qr_table' | null;
    /** Thông tin bàn (chỉ khi type=qr_table) */
    table: PublicTableInfo | null;
    /** Đơn hàng đang mở của bàn này */
    activeOrder: Order | null;

    // Actions
    setSession: (params: {
        publicToken: string;
        qrType: 'qr_static' | 'qr_table';
        table?: PublicTableInfo | null;
        activeOrder?: Order | null;
    }) => void;
    setActiveOrder: (order: Order | null) => void;
    clearSession: () => void;
}

export const useQrSessionStore = create<QrSessionState>()(
    persist(
        (set) => ({
            publicToken: null,
            qrType: null,
            table: null,
            activeOrder: null,

            setSession: ({ publicToken, qrType, table, activeOrder }) =>
                set({ publicToken, qrType, table: table ?? null, activeOrder: activeOrder ?? null }),

            setActiveOrder: (order) => set({ activeOrder: order }),

            clearSession: () =>
                set({ publicToken: null, qrType: null, table: null, activeOrder: null }),
        }),
        {
            name: 'kiottay-qr-session',
        }
    )
);

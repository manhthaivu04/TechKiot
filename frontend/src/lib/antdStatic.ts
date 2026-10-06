/**
 * antdStatic.ts
 * Helper lưu trữ static instance của notification/message/modal từ Ant Design v5.
 *
 * Vấn đề: Ant Design v5 không cho phép gọi notification.open() từ ngoài React tree
 * (ngoài component) mà không có <App> context → toast không hiển thị.
 *
 * Giải pháp: Dùng App.useApp() trong một component "bridge" rồi lưu instance
 * vào module-level variable → hook và service khác gọi được từ bất kỳ đâu.
 *
 * Cách dùng:
 *   import { antdNotification } from '@/lib/antdStatic';
 *   antdNotification.open({ message: '...', description: '...' });
 */

import type { NotificationInstance } from 'antd/es/notification/interface';
import type { MessageInstance } from 'antd/es/message/interface';

// Module-level holders — được gán bởi AntdStaticBridge component khi mount
let _notification: NotificationInstance | null = null;
let _message: MessageInstance | null = null;

/** Gán instance (chỉ gọi từ AntdStaticBridge) */
export const setAntdInstances = (
    notification: NotificationInstance,
    message: MessageInstance,
) => {
    _notification = notification;
    _message      = message;
};

/**
 * Proxy object — khi gọi method sẽ forward đến instance thực.
 * Nếu chưa được khởi tạo (chưa render App) sẽ warn thay vì crash.
 */
export const antdNotification: NotificationInstance = new Proxy(
    {} as NotificationInstance,
    {
        get(_target, prop: string) {
            return (...args: unknown[]) => {
                if (!_notification) {
                    console.warn('[antdStatic] notification chưa được khởi tạo. Đảm bảo <AntdStaticBridge> đã được render bên trong <App>.');
                    return;
                }
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                return (_notification as any)[prop](...args);
            };
        },
    }
);

export const antdMessage: MessageInstance = new Proxy(
    {} as MessageInstance,
    {
        get(_target, prop: string) {
            return (...args: unknown[]) => {
                if (!_message) {
                    console.warn('[antdStatic] message chưa được khởi tạo.');
                    return;
                }
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                return (_message as any)[prop](...args);
            };
        },
    }
);

/**
 * AntdStaticBridge.tsx
 * Component "cầu nối" — phải được render bên trong <App> của Ant Design.
 * Lấy notification/message instance từ context và lưu vào antdStatic module.
 *
 * Render một lần duy nhất trong App.tsx, không render bất kỳ UI nào.
 */

import { useEffect } from 'react';
import { App } from 'antd';
import { setAntdInstances } from '@/lib/antdStatic';

export const AntdStaticBridge = () => {
    const { notification, message } = App.useApp();

    useEffect(() => {
        // Gán instance vào module-level holder ngay khi component mount
        setAntdInstances(notification, message);
    }, [notification, message]);

    // Không render gì cả — chỉ là bridge lấy context
    return null;
};

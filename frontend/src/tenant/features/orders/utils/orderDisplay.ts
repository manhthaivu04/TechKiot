import type { Order, ServiceType } from '@/types';
import { SERVICE_TYPE_CONFIG } from '@/types';

export type TableNameMap = Record<string, string>;

function normalizeServiceType(serviceType: ServiceType | string): string {
    return String(serviceType).toLowerCase();
}

export function isTakeawayOrder(
    order: Pick<Order, 'service_type' | 'source_channel'>,
): boolean {
    if (order.source_channel === 'qr_static') return true;
    return normalizeServiceType(order.service_type) === 'takeaway';
}

export function isDineInOrder(
    order: Pick<Order, 'service_type' | 'table_id' | 'source_channel'>,
): boolean {
    // QR tĩnh = mang đi, không phải tại bàn
    if (order.source_channel === 'qr_static') return false;
    if (order.source_channel === 'qr_table') return true;

    const svc = normalizeServiceType(order.service_type);
    return svc === 'dine_in' || !!order.table_id;
}

export function resolveTableName(
    order: Pick<Order, 'table_id' | 'customer_reference'>,
    tableNames: TableNameMap = {},
): string | null {
    if (order.table_id && tableNames[order.table_id]) {
        return tableNames[order.table_id];
    }
    if (order.customer_reference?.trim()) {
        return order.customer_reference.trim();
    }
    return null;
}

/** Nhãn hiển thị: Mang đi | Bàn {tên} | Giao hàng */
export function getOrderServiceDisplay(
    order: Pick<Order, 'service_type' | 'table_id' | 'customer_reference' | 'source_channel' | 'service_type_label'>,
    tableNames: TableNameMap = {},
): { icon: string; label: string; badgeClass: string } {
    if (isTakeawayOrder(order)) {
        return {
            icon: '🛍️',
            label: 'Mang đi',
            badgeClass: 'bg-sky-50 text-sky-700 border border-sky-200',
        };
    }

    if (isDineInOrder(order)) {
        const tableName = resolveTableName(order, tableNames);
        return {
            icon: '🍽️',
            label: tableName ? `Bàn ${tableName}` : 'Tại bàn',
            badgeClass: 'bg-amber-50 text-amber-800 border border-amber-200',
        };
    }

    const svc = normalizeServiceType(order.service_type);
    if (svc === 'delivery') {
        return {
            icon: '🚀',
            label: 'Giao hàng',
            badgeClass: 'bg-purple-50 text-purple-700 border border-purple-200',
        };
    }

    const cfg = SERVICE_TYPE_CONFIG[order.service_type];
    return {
        icon: cfg?.icon ?? '📋',
        label: order.service_type_label || cfg?.label || String(order.service_type),
        badgeClass: 'bg-gray-100 text-gray-600 border border-gray-200',
    };
}

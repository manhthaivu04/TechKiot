import { Button, Dropdown, Tooltip } from 'antd';
import type { MenuProps } from 'antd';
import {
    CheckCircleOutlined,
    CloseCircleOutlined,
    ClockCircleOutlined,
    DollarOutlined,
    MoreOutlined,
    UserOutlined,
} from '@ant-design/icons';
import type { Order, OrderStatus } from '@/types';
import { ORDER_STATUS_CONFIG } from '@/types';
import type { HighlightType } from '../stores/highlight.store';
import { getOrderServiceDisplay, type TableNameMap } from '../utils/orderDisplay';

const fmt = (val: string | number) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(
        typeof val === 'string' ? parseFloat(val) : val
    );

const NEXT_STATUSES: Record<OrderStatus, OrderStatus[]> = {
    open: ['cooking', 'cancelled'],
    cooking: ['served', 'cancelled'],
    served: [],
    paid: [],
    cancelled: [],
    PENDING: ['CONFIRMED', 'CANCELLED'],
    CONFIRMED: ['PREPARING', 'CANCELLED'],
    PREPARING: ['READY', 'CANCELLED'],
    READY: ['COMPLETED'],
    COMPLETED: [],
    CANCELLED: [],
};

const PRIMARY_ACTION: Partial<Record<OrderStatus, { status: OrderStatus; label: string }>> = {
    open: { status: 'cooking', label: 'Bắt đầu nấu' },
    cooking: { status: 'served', label: 'Đã lên món' },
    PENDING: { status: 'CONFIRMED', label: 'Xác nhận đơn' },
    CONFIRMED: { status: 'PREPARING', label: 'Bắt đầu nấu' },
    PREPARING: { status: 'READY', label: 'Đã lên món' },
};

function getElapsedMinutes(createdAt: string) {
    return Math.floor((Date.now() - new Date(createdAt).getTime()) / 60000);
}

function formatElapsed(minutes: number) {
    if (minutes < 1) return 'Vừa xong';
    if (minutes < 60) return `${minutes} phút`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m > 0 ? `${h}g ${m}p` : `${h} giờ`;
}

interface Props {
    order: Order;
    highlight?: HighlightType;
    tableNames?: TableNameMap;
    onOpenDetail: (id: string) => void;
    onStatusChange: (orderId: string, status: OrderStatus) => void;
}

export const OrderKanbanCard = ({
    order,
    highlight,
    tableNames = {},
    onOpenDetail,
    onStatusChange,
}: Props) => {
    const nextStatuses = NEXT_STATUSES[order.status] || [];
    const primaryAction = PRIMARY_ACTION[order.status];
    const serviceDisplay = getOrderServiceDisplay(order, tableNames);
    const itemsCount = order.items.reduce((s, i) => s + i.quantity, 0);
    const pendingCount = order.items.filter((i) => i.status === 'pending').length;
    const elapsed = getElapsedMinutes(order.created_at);
    const isLongWait = elapsed >= 15;
    const previewItems = order.items.slice(0, 3);
    const moreItems = order.items.length - previewItems.length;

    const cancelStatuses = nextStatuses.filter(
        (s) => s === 'cancelled' || s === 'CANCELLED'
    );
    const otherStatuses = nextStatuses.filter(
        (s) => s !== 'cancelled' && s !== 'CANCELLED' && s !== primaryAction?.status
    );

    const moreMenuItems: MenuProps['items'] = [
        ...otherStatuses.map((s) => {
            const cfg = ORDER_STATUS_CONFIG[s];
            return {
                key: s,
                label: `Chuyển sang ${cfg?.label}`,
                icon: <CheckCircleOutlined style={{ color: cfg?.color }} />,
            };
        }),
        ...cancelStatuses.map((s) => ({
            key: s,
            label: <span className="text-red-500">Huỷ đơn</span>,
            icon: <CloseCircleOutlined className="text-red-500" />,
        })),
    ];

    const isQr = order.source_channel === 'qr_static' || order.source_channel === 'qr_table';

    return (
        <div
            className={`
                bg-white rounded-2xl border shadow-sm transition-all hover:shadow-md
                ${highlight
                    ? 'border-l-4 border-l-orange-500 ring-2 ring-orange-100'
                    : 'border-gray-100 hover:border-emerald-200'}
            `}
        >
            {/* Header */}
            <div className="px-4 pt-3 pb-2">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 flex-wrap">
                            <span className="font-mono text-lg font-bold text-emerald-700 leading-tight">
                                #{order.order_code}
                            </span>
                            {highlight && (
                                <span
                                    className={`text-[10px] font-bold px-2 py-0.5 rounded-full text-white
                                        ${highlight === 'NEW_ORDER' ? 'bg-blue-500' : 'bg-orange-500'}`}
                                >
                                    {highlight === 'NEW_ORDER' ? 'MỚI' : 'GỌI THÊM'}
                                </span>
                            )}
                            {isQr && (
                                <span className="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600">
                                    QR
                                </span>
                            )}
                        </div>
                        <div className="mt-1 flex items-center gap-1.5 text-gray-800 font-medium truncate">
                            <UserOutlined className="text-gray-400 text-xs shrink-0" />
                            <span className="truncate">{order.customer_name || 'Khách vãng lai'}</span>
                        </div>
                    </div>
                    <Tooltip title={`Đặt lúc ${new Date(order.created_at).toLocaleTimeString('vi-VN')}`}>
                        <span
                            className={`shrink-0 flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg
                                ${isLongWait ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-500'}`}
                        >
                            <ClockCircleOutlined />
                            {formatElapsed(elapsed)}
                        </span>
                    </Tooltip>
                </div>

                <div className="mt-2 flex flex-wrap gap-1.5">
                    <span
                        className={`text-xs px-2.5 py-1 rounded-md font-semibold ${serviceDisplay.badgeClass}`}
                    >
                        {serviceDisplay.icon} {serviceDisplay.label}
                    </span>
                    <span className="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-medium">
                        {itemsCount} món
                    </span>
                    {pendingCount > 0 && (
                        <span className="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-md font-bold">
                            {pendingCount} chờ nấu
                        </span>
                    )}
                </div>
            </div>

            {/* Items preview */}
            <div className="px-4 py-2 bg-gray-50/80 border-y border-gray-100">
                <ul className="space-y-1 m-0 p-0 list-none">
                    {previewItems.map((item) => (
                        <li
                            key={item.id}
                            className="flex justify-between items-center gap-2 text-sm text-gray-700"
                        >
                            <span className="truncate flex-1">
                                <span className="font-bold text-gray-900">{item.quantity}×</span>{' '}
                                {item.item_name}
                            </span>
                            <span className="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded bg-white border border-gray-100">
                                {item.status === 'pending' && <span className="text-orange-500">Chờ nấu</span>}
                                {item.status === 'cooking' && <span className="text-purple-500">Đang nấu</span>}
                                {item.status === 'ready' && <span className="text-blue-500">Đã xong</span>}
                                {item.status === 'served' && <span className="text-emerald-600">Đã lên</span>}
                                {item.status === 'cancelled' && <span className="text-red-500">Đã huỷ</span>}
                            </span>
                        </li>
                    ))}
                    {moreItems > 0 && (
                        <li className="text-xs text-gray-400 italic">+ {moreItems} món khác…</li>
                    )}
                </ul>
            </div>

            {/* Footer */}
            <div className="px-4 py-3">
                <div className="flex items-center justify-between mb-3">
                    <span className="text-xs text-gray-400 uppercase tracking-wide">Tổng tiền</span>
                    <span className="text-lg font-bold text-gray-900">{fmt(order.final_amount)}</span>
                </div>

                <div className="flex gap-2">
                    {primaryAction && (
                        <Button
                            type="primary"
                            block
                            size="large"
                            icon={<CheckCircleOutlined />}
                            className="!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600 !font-semibold"
                            onClick={() => onStatusChange(order.id, primaryAction.status)}
                        >
                            {primaryAction.label}
                        </Button>
                    )}

                    {order.status === 'served' && (
                        <Button
                            type="primary"
                            block
                            size="large"
                            icon={<DollarOutlined />}
                            className="!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600 !font-semibold"
                            onClick={() => onOpenDetail(order.id)}
                        >
                            Thu tiền
                        </Button>
                    )}

                    <Button
                        size="large"
                        onClick={() => onOpenDetail(order.id)}
                        className={primaryAction || order.status === 'served' ? '' : 'flex-1'}
                    >
                        Chi tiết
                    </Button>

                    {moreMenuItems.length > 0 && (
                        <Dropdown
                            menu={{
                                items: moreMenuItems,
                                onClick: (info) => onStatusChange(order.id, info.key as OrderStatus),
                            }}
                            trigger={['click']}
                            placement="bottomRight"
                        >
                            <Button size="large" icon={<MoreOutlined />} />
                        </Dropdown>
                    )}
                </div>
            </div>
        </div>
    );
};

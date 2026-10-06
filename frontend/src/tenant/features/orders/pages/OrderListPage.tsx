import { useState, useMemo } from 'react';
import {
    Button, Select, Input, Card, App,
    Badge, Empty, Segmented,
} from 'antd';
import {
    SearchOutlined,
    ReloadOutlined,
    ShoppingCartOutlined,
    InboxOutlined,
    FireOutlined,
    CheckCircleOutlined,
} from '@ant-design/icons';
import { useOrders, useUpdateOrderStatus } from '../services/order.hooks';
import { OrderDetailModal } from '../components/OrderDetailModal';
import { OrderKanbanCard } from '../components/OrderKanbanCard';
import { POSDrawer } from '../components/POSDrawer';
import { useHighlightStore } from '../stores/highlight.store';
import { useTableNameMap } from '../hooks/useTableNameMap';
import type { Order, OrderStatus, ServiceType, OrderListParams } from '@/types';
import { ORDER_STATUS_CONFIG } from '@/types';
import { ExclamationCircleFilled } from '@ant-design/icons';

const STATUS_OPTIONS = [
    { value: '', label: 'Tất cả trạng thái' },
    ...Object.entries(ORDER_STATUS_CONFIG)
        .filter(([k]) => !['PENDING', 'CONFIRMED', 'PREPARING', 'READY', 'COMPLETED', 'CANCELLED'].includes(k))
        .map(([k, v]) => ({
            value: k as OrderStatus,
            label: v.label,
        })),
];

const SERVICE_OPTIONS = [
    { value: '', label: 'Tất cả loại đơn' },
    { value: 'TAKEAWAY' as ServiceType, label: '🛍️ Mang đi' },
    { value: 'DINE_IN' as ServiceType, label: '🍽️ Tại bàn' },
    { value: 'DELIVERY' as ServiceType, label: '🚀 Giao hàng' },
];

const KANBAN_COLUMNS: {
    id: OrderStatus;
    title: string;
    subtitle: string;
    icon: React.ReactNode;
}[] = [
    { id: 'open', title: 'Chờ xác nhận', subtitle: 'Đơn mới cần xử lý', icon: <InboxOutlined /> },
    { id: 'cooking', title: 'Đang nấu', subtitle: 'Bếp đang chế biến', icon: <FireOutlined /> },
    { id: 'served', title: 'Đã lên món', subtitle: 'Chờ thanh toán', icon: <CheckCircleOutlined /> },
];

type ViewMode = 'all' | 'open' | 'cooking' | 'served';

export const OrderListPage = () => {
    const highlightedOrders = useHighlightStore((state) => state.highlightedOrders);
    const removeHighlight = useHighlightStore((state) => state.removeHighlight);
    const tableNames = useTableNameMap();

    const { message, modal } = App.useApp();
    const [posOpen, setPosOpen] = useState(false);
    const [viewMode, setViewMode] = useState<ViewMode>('all');

    const [params, setParams] = useState<OrderListParams>({
        page: 1,
        per_page: 100,
        status: '',
        service_type: '',
    });
    const [search, setSearch] = useState('');
    const [selectedOrderId, setSelectedOrderId] = useState<string | null>(null);

    const { data, isLoading, refetch, isFetching } = useOrders({
        ...params,
        search: search || undefined,
    });

    const { mutate: updateStatus } = useUpdateOrderStatus();

    const orders = data?.data ?? [];
    const meta = data?.meta;

    const handleStatusChange = (orderId: string, status: OrderStatus) => {
        if (status === 'CANCELLED' || status === 'cancelled') {
            modal.confirm({
                title: 'Xác nhận huỷ đơn hàng',
                icon: <ExclamationCircleFilled className="text-red-500" />,
                content: 'Bạn có chắc chắn muốn huỷ đơn hàng này không? Thao tác này không thể hoàn tác.',
                okText: 'Huỷ đơn',
                okType: 'danger',
                cancelText: 'Bỏ qua',
                onOk: () => {
                    updateStatus(
                        { id: orderId, data: { status } },
                        {
                            onSuccess: () => message.success('Huỷ đơn hàng thành công'),
                            onError: () => message.error('Huỷ đơn hàng thất bại'),
                        }
                    );
                },
            });
        } else {
            updateStatus(
                { id: orderId, data: { status } },
                {
                    onSuccess: () => message.success('Cập nhật trạng thái thành công'),
                    onError: () => message.error('Cập nhật thất bại'),
                }
            );
        }
    };

    const handleOpenDetail = (id: string) => {
        setSelectedOrderId(id);
        if (highlightedOrders[id]) {
            removeHighlight(id);
        }
    };

    const groupedOrders = useMemo(() => {
        const groups: Record<string, Order[]> = {
            open: [], cooking: [], served: [], paid: [], cancelled: [],
            PENDING: [], CONFIRMED: [], PREPARING: [], READY: [], COMPLETED: [], CANCELLED: [],
        };

        orders.forEach((o) => {
            const totalPaid = (o.payments || []).reduce((sum, p) => sum + parseFloat(p.amount), 0);
            const isFullyPaid = totalPaid >= parseFloat(o.final_amount) && totalPaid > 0;
            if (isFullyPaid) return;

            if (groups[o.status]) {
                groups[o.status].push(o);
            } else {
                groups[o.status] = [o];
            }
        });

        const sortGroup = (list: Order[]) =>
            [...list].sort((a, b) => {
                const aH = highlightedOrders[a.id] ? 1 : 0;
                const bH = highlightedOrders[b.id] ? 1 : 0;
                if (bH !== aH) return bH - aH;
                return new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime();
            });

        return {
            open: sortGroup([...groups.open, ...groups.PENDING]),
            cooking: sortGroup([...groups.cooking, ...groups.CONFIRMED, ...groups.PREPARING]),
            served: sortGroup([...groups.served, ...groups.READY]),
        };
    }, [orders, highlightedOrders]);

    const totalActive =
        groupedOrders.open.length + groupedOrders.cooking.length + groupedOrders.served.length;

    const visibleColumns =
        viewMode === 'all'
            ? KANBAN_COLUMNS
            : KANBAN_COLUMNS.filter((col) => col.id === viewMode);

    return (
        <div className="flex flex-col h-[calc(100vh-100px)] gap-4">
            {/* Header */}
            <div className="flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800 m-0">Quản lý đơn hàng</h1>
                    <p className="text-gray-500 text-sm mt-0.5 m-0">
                        {isLoading ? 'Đang tải...' : `${totalActive} đơn đang xử lý · Tổng ${meta?.total ?? 0} đơn`}
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button
                        icon={<ReloadOutlined spin={isFetching} />}
                        onClick={() => refetch()}
                        disabled={isFetching}
                        size="large"
                    >
                        Làm mới
                    </Button>
                    <Button
                        type="primary"
                        size="large"
                        icon={<ShoppingCartOutlined />}
                        className="!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600"
                        onClick={() => setPosOpen(true)}
                    >
                        Bán hàng (POS)
                    </Button>
                </div>
            </div>

            {/* Toolbar */}
            <Card size="small" className="rounded-xl shadow-sm shrink-0 !border-gray-100">
                <div className="flex flex-wrap gap-3 items-center justify-between">
                    <div className="flex flex-wrap gap-3 items-center flex-1">
                        <Input
                            prefix={<SearchOutlined className="text-gray-400" />}
                            placeholder="Tìm mã đơn, tên khách, SĐT..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setParams((p) => ({ ...p, page: 1 }));
                            }}
                            allowClear
                            size="large"
                            className="rounded-lg !max-w-xs"
                        />
                        <Select
                            options={SERVICE_OPTIONS}
                            value={params.service_type ?? ''}
                            onChange={(v) => setParams((p) => ({ ...p, service_type: v, page: 1 }))}
                            size="large"
                            className="min-w-[150px]"
                        />
                        <Select
                            options={STATUS_OPTIONS}
                            value={params.status ?? ''}
                            onChange={(v) => setParams((p) => ({ ...p, status: v, page: 1 }))}
                            size="large"
                            className="min-w-[170px]"
                        />
                        {(search || params.status || params.service_type) && (
                            <Button
                                size="large"
                                onClick={() => {
                                    setSearch('');
                                    setParams({ page: 1, per_page: 100, status: '', service_type: '' });
                                }}
                            >
                                Xoá lọc
                            </Button>
                        )}
                    </div>

                    <Segmented
                        value={viewMode}
                        onChange={(v) => setViewMode(v as ViewMode)}
                        options={[
                            { label: 'Tất cả', value: 'all' },
                            {
                                label: (
                                    <span>
                                        Chờ{' '}
                                        <Badge
                                            count={groupedOrders.open.length}
                                            size="small"
                                            style={{ backgroundColor: '#d97706' }}
                                        />
                                    </span>
                                ),
                                value: 'open',
                            },
                            {
                                label: (
                                    <span>
                                        Nấu{' '}
                                        <Badge
                                            count={groupedOrders.cooking.length}
                                            size="small"
                                            style={{ backgroundColor: '#7c3aed' }}
                                        />
                                    </span>
                                ),
                                value: 'cooking',
                            },
                            {
                                label: (
                                    <span>
                                        Lên món{' '}
                                        <Badge
                                            count={groupedOrders.served.length}
                                            size="small"
                                            style={{ backgroundColor: '#059669' }}
                                        />
                                    </span>
                                ),
                                value: 'served',
                            },
                        ]}
                    />
                </div>
            </Card>

            {/* Kanban */}
            <div className="flex-1 min-h-0 overflow-x-auto pb-2">
                <div
                    className={`grid gap-4 h-full min-w-0 ${
                        visibleColumns.length === 1
                            ? 'grid-cols-1 max-w-2xl mx-auto'
                            : visibleColumns.length === 2
                              ? 'grid-cols-1 lg:grid-cols-2'
                              : 'grid-cols-1 lg:grid-cols-3'
                    }`}
                    style={{ minHeight: '100%' }}
                >
                    {visibleColumns.map((col) => {
                        const colOrders = groupedOrders[col.id as keyof typeof groupedOrders] || [];
                        const statusCfg = ORDER_STATUS_CONFIG[col.id];

                        return (
                            <div
                                key={col.id}
                                className="flex flex-col min-h-[320px] bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
                            >
                                <div
                                    className="px-4 py-3 shrink-0 flex items-center justify-between border-b"
                                    style={{ backgroundColor: statusCfg?.bg, borderColor: `${statusCfg?.color}22` }}
                                >
                                    <div className="flex items-center gap-2">
                                        <span
                                            className="flex items-center justify-center w-8 h-8 rounded-lg text-white text-sm"
                                            style={{ backgroundColor: statusCfg?.color }}
                                        >
                                            {col.icon}
                                        </span>
                                        <div>
                                            <h3
                                                className="m-0 font-bold text-base leading-tight"
                                                style={{ color: statusCfg?.color }}
                                            >
                                                {col.title}
                                            </h3>
                                            <p className="m-0 text-xs text-gray-500">{col.subtitle}</p>
                                        </div>
                                    </div>
                                    <Badge
                                        count={colOrders.length}
                                        overflowCount={99}
                                        style={{
                                            backgroundColor: statusCfg?.color,
                                            fontSize: 14,
                                            minWidth: 28,
                                            height: 28,
                                            lineHeight: '28px',
                                            borderRadius: 14,
                                        }}
                                    />
                                </div>

                                <div className="flex-1 overflow-y-auto p-3 space-y-3 bg-gray-50/50">
                                    {colOrders.length === 0 ? (
                                        <Empty
                                            image={Empty.PRESENTED_IMAGE_SIMPLE}
                                            description={
                                                <span className="text-gray-400">Không có đơn</span>
                                            }
                                            className="py-12"
                                        />
                                    ) : (
                                        colOrders.map((order) => (
                                            <OrderKanbanCard
                                                key={order.id}
                                                order={order}
                                                highlight={highlightedOrders[order.id]}
                                                tableNames={tableNames}
                                                onOpenDetail={handleOpenDetail}
                                                onStatusChange={handleStatusChange}
                                            />
                                        ))
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {selectedOrderId && (
                <OrderDetailModal
                    orderId={selectedOrderId}
                    open={!!selectedOrderId}
                    onClose={() => setSelectedOrderId(null)}
                />
            )}

            <POSDrawer open={posOpen} onClose={() => setPosOpen(false)} />
        </div>
    );
};

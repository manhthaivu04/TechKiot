import { useState } from 'react';
import {
    Modal, Spin, Divider, Tag, Button, Descriptions, Table,
    message,
} from 'antd';
import type { ColumnsType } from 'antd/es/table';
import {
    CheckCircleOutlined,
    CloseCircleOutlined,
    DollarOutlined,
    PlusCircleOutlined,
} from '@ant-design/icons';
import { useOrder, useUpdateOrderStatus } from '../services/order.hooks';
import { useTableNameMap } from '../hooks/useTableNameMap';
import { getOrderServiceDisplay } from '../utils/orderDisplay';
import { PaymentModal } from './PaymentModal';
import { AddItemsDrawer } from './AddItemsDrawer';
import type { OrderStatus, Payment } from '@/types';
import { ORDER_STATUS_CONFIG } from '@/types';

const fmt = (val: string | number) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(
        typeof val === 'string' ? parseFloat(val) : val
    );

const fmtDate = (s: string) =>
    new Date(s).toLocaleString('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });

// Which statuses can this order transition to?
const NEXT_STATUSES: Record<OrderStatus, OrderStatus[]> = {
    // Backend mới
    open:      ['cooking', 'cancelled'],
    cooking:   ['served', 'cancelled'],
    served:    [],
    paid:      [],
    cancelled: [],

    // Legacy
    PENDING:   ['CONFIRMED', 'CANCELLED'],
    CONFIRMED: ['PREPARING', 'CANCELLED'],
    PREPARING: ['READY', 'CANCELLED'],
    READY:     ['COMPLETED'],
    COMPLETED: [],
    CANCELLED: [],
};

interface Props {
    orderId: string;
    open: boolean;
    onClose: () => void;
}

export const OrderDetailModal = ({ orderId, open, onClose }: Props) => {
    const { data, isLoading } = useOrder(orderId, open);
    const { mutate: updateStatus, isPending: updatingStatus } = useUpdateOrderStatus();
    const [payModalOpen, setPayModalOpen] = useState(false);
    const [addItemsOpen, setAddItemsOpen] = useState(false);
    const tableNames = useTableNameMap();

    const order = data?.data;

    const handleStatusChange = (status: OrderStatus) => {
        updateStatus(
            { id: orderId, data: { status } },
            {
                onSuccess: () => message.success('Cập nhật trạng thái thành công'),
                onError:   () => message.error('Cập nhật thất bại'),
            }
        );
    };

    // Payment columns (history table still uses antd Table via inline if needed — kept for payments only)
    const paymentCols: ColumnsType<Payment> = [
        { title: 'Phương thức', dataIndex: 'method',
          render: (m: string) => {
              const map: Record<string, string> = {
                  CASH: '💵 Tiền mặt', BANK_TRANSFER: '🏦 Chuyển khoản',
                  CARD: '💳 Thẻ', OTHER: '🔄 Khác',
              };
              return map[m] ?? m;
          }},
        { title: 'Số tiền', dataIndex: 'amount', align: 'right',
          render: (a: string) => <span className="font-bold text-emerald-600">{fmt(a)}</span> },
        { title: 'Thời gian', dataIndex: 'created_at',
          render: (d: string) => fmtDate(d) },
    ];

    const statusCfg = order ? ORDER_STATUS_CONFIG[order.status] : null;
    const serviceDisplay = order ? getOrderServiceDisplay(order, tableNames) : null;
    const nextStatuses = order ? (NEXT_STATUSES[order.status] || []) : [];
    const payments = order?.payments || [];
    const totalPaid = payments.reduce((s, p) => s + parseFloat(p.amount), 0);
    const remaining = order ? parseFloat(order.final_amount) - totalPaid : 0;

    return (
        <>
            <Modal
                open={open}
                onCancel={onClose}
                footer={null}
                width={860}
                className="order-detail-modal"
                title={
                    order ? (
                        <div className="flex items-center gap-3">
                            <span className="font-bold text-gray-800 text-lg">
                                Đơn #{order.order_code}
                            </span>
                            {statusCfg && (
                                <span
                                    className="text-xs font-bold px-2 py-1 rounded-full"
                                    style={{ color: statusCfg.color, backgroundColor: statusCfg.bg }}
                                >
                                    {statusCfg.label}
                                </span>
                            )}
                            {serviceDisplay && (
                                <Tag className={`rounded-full text-xs font-semibold border-0 ${serviceDisplay.badgeClass}`}>
                                    {serviceDisplay.icon} {serviceDisplay.label}
                                </Tag>
                            )}
                        </div>
                    ) : (
                        'Chi tiết đơn hàng'
                    )
                }
                centered
                destroyOnHidden
            >
                {isLoading || !order ? (
                    <div className="flex justify-center py-16">
                        <Spin size="large" tip="Đang tải..." />
                    </div>
                ) : (
                    <div className="space-y-5">
                        {/* Info grid */}
                        <Descriptions size="small" bordered column={2}>
                            <Descriptions.Item label="Mã đơn">
                                <span className="font-mono font-bold">{order.order_code}</span>
                            </Descriptions.Item>
                            <Descriptions.Item label="Tạo lúc">
                                {fmtDate(order.created_at)}
                            </Descriptions.Item>
                            {order.customer_name && (
                                <Descriptions.Item label="Khách hàng">
                                    👤 {order.customer_name}
                                </Descriptions.Item>
                            )}
                            {order.customer_phone && (
                                <Descriptions.Item label="Số điện thoại">
                                    📞 {order.customer_phone}
                                </Descriptions.Item>
                            )}
                            <Descriptions.Item label="Loại đơn">
                                {serviceDisplay && (
                                    <span className="font-semibold">
                                        {serviceDisplay.icon} {serviceDisplay.label}
                                    </span>
                                )}
                            </Descriptions.Item>
                            {order.note && (
                                <Descriptions.Item label="Ghi chú tổng" span={2}>
                                    📝 <span className="italic text-gray-600">{order.note}</span>
                                </Descriptions.Item>
                            )}
                        </Descriptions>

                        {/* Items */}
                        <div>
                            <h3 className="font-bold text-gray-700 mb-3 text-base">Danh sách món</h3>
                            <div className="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                                {[...order.items]
                                    .sort((a, b) => {
                                        if (a.status === 'pending' && b.status !== 'pending') return -1;
                                        if (a.status !== 'pending' && b.status === 'pending') return 1;
                                        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
                                    })
                                    .map((item) => {
                                        const isPending = item.status === 'pending';
                                        const isNew =
                                            new Date(item.created_at).getTime() -
                                                new Date(order.created_at).getTime() >
                                            5000;
                                        return (
                                            <div
                                                key={item.id}
                                                className={`flex items-center justify-between gap-3 p-3 rounded-xl border
                                                    ${isPending ? 'bg-orange-50 border-orange-200' : 'bg-white border-gray-100'}`}
                                            >
                                                {/* Cột 1: Tên & Ghi chú */}
                                                <div className="min-w-0 flex-1">
                                                    <div className="font-medium text-gray-900 flex items-center gap-2 flex-wrap">
                                                        <span className="text-emerald-700 font-bold">{item.quantity}×</span>
                                                        <span>{item.item_name}</span>
                                                        {isNew && !isPending && (
                                                            <Tag color="cyan" className="!m-0 text-[10px]">Gọi thêm</Tag>
                                                        )}
                                                    </div>
                                                    {item.note && (
                                                        <p className="text-xs text-gray-500 italic mt-1 mb-0">📝 {item.note}</p>
                                                    )}
                                                </div>

                                                {/* Cột 2: Trạng thái món */}
                                                <div className="shrink-0 min-w-[100px] text-center">
                                                    {item.status === 'pending' && <Tag color="orange" className="!m-0">Chờ nấu</Tag>}
                                                    {item.status === 'cooking' && <Tag color="purple" className="!m-0">Đang nấu</Tag>}
                                                    {item.status === 'ready' && <Tag color="blue" className="!m-0">Đã xong</Tag>}
                                                    {item.status === 'served' && <Tag color="success" className="!m-0">Đã lên món</Tag>}
                                                    {item.status === 'cancelled' && <Tag color="error" className="!m-0">Đã huỷ</Tag>}
                                                </div>

                                                {/* Cột 3: Giá */}
                                                <div className="text-right shrink-0 min-w-[100px]">
                                                    <div className="font-bold text-emerald-600">{fmt(item.line_total)}</div>
                                                    <div className="text-xs text-gray-400">{fmt(item.unit_price)}/món</div>
                                                </div>
                                            </div>
                                        );
                                    })}
                            </div>
                        </div>

                        {/* Summary */}
                        <div className="bg-gray-50 rounded-xl p-4 space-y-2">
                            <div className="flex justify-between text-sm text-gray-600">
                                <span>Tạm tính</span>
                                <span>{fmt(order.subtotal_amount)}</span>
                            </div>
                            {parseFloat(order.discount_amount) > 0 && (
                                <div className="flex justify-between text-sm text-red-500">
                                    <span>Giảm giá</span>
                                    <span>- {fmt(order.discount_amount)}</span>
                                </div>
                            )}
                            {parseFloat(order.tax_amount) > 0 && (
                                <div className="flex justify-between text-sm text-gray-600">
                                    <span>Thuế</span>
                                    <span>{fmt(order.tax_amount)}</span>
                                </div>
                            )}
                            <Divider className="!my-2" />
                            <div className="flex justify-between font-bold text-base">
                                <span>Tổng cộng</span>
                                <span className="text-emerald-600">{fmt(order.final_amount)}</span>
                            </div>
                            {totalPaid > 0 && (
                                <>
                                    <div className="flex justify-between text-sm text-blue-600">
                                        <span>Đã thanh toán</span>
                                        <span>{fmt(totalPaid)}</span>
                                    </div>
                                    {remaining > 0 && (
                                        <div className="flex justify-between text-sm font-bold text-red-600">
                                            <span>Còn lại</span>
                                            <span>{fmt(remaining)}</span>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>

                        {/* Payments history */}
                        {payments.length > 0 && (
                            <div>
                                <h3 className="font-bold text-gray-700 mb-2">💳 Lịch sử thanh toán</h3>
                                <Table
                                    dataSource={order.payments}
                                    columns={paymentCols}
                                    rowKey="id"
                                    pagination={false}
                                    size="small"
                                    bordered
                                />
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-gray-200">
                            {nextStatuses.length > 0 ? (
                                <div className="flex flex-wrap gap-2 flex-1">
                                    {nextStatuses.map((s) => {
                                        const cfg = ORDER_STATUS_CONFIG[s];
                                        if (!cfg) return null;
                                        const isCancel = s === 'CANCELLED' || s === 'cancelled';
                                        const icon = isCancel
                                            ? <CloseCircleOutlined />
                                            : <CheckCircleOutlined />;
                                        return (
                                            <Button
                                                key={s}
                                                size="large"
                                                icon={icon}
                                                loading={updatingStatus}
                                                danger={isCancel}
                                                onClick={() => handleStatusChange(s)}
                                                className={
                                                    !isCancel
                                                        ? '!font-semibold'
                                                        : undefined
                                                }
                                                style={
                                                    !isCancel
                                                        ? {
                                                              borderColor: cfg.color,
                                                              color: cfg.color,
                                                              minWidth: 140,
                                                          }
                                                        : undefined
                                                }
                                            >
                                                {cfg.label}
                                            </Button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div />
                            )}

                            <div className="flex items-center gap-2">
                                {/* Gọi thêm món — chỉ hiện khi đơn chưa được huỷ hoặc đã thanh toán hết */}
                                {order.status !== 'cancelled' && order.status !== 'CANCELLED' && order.status !== 'paid' && (
                                    <Button
                                        size="large"
                                        icon={<PlusCircleOutlined />}
                                        onClick={() => setAddItemsOpen(true)}
                                        className="!font-semibold !border-emerald-500 !text-emerald-600 hover:!bg-emerald-50"
                                    >
                                        Gọi thêm món
                                    </Button>
                                )}

                                {remaining > 0 ? (
                                    <Button
                                        type="primary"
                                        size="large"
                                        icon={<DollarOutlined />}
                                        className="!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600 !font-bold !h-12 !px-8"
                                        onClick={() => setPayModalOpen(true)}
                                    >
                                        Thanh toán {fmt(remaining)}
                                    </Button>
                                ) : (
                                    remaining <= 0 &&
                                    order.status !== 'CANCELLED' &&
                                    order.status !== 'cancelled' && (
                                        <Tag color="success" className="px-4 py-2 text-sm font-medium !m-0">
                                            Đã thanh toán đủ
                                        </Tag>
                                    )
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </Modal>

            {/* Payment sub-modal */}
            {order && (
                <PaymentModal
                    open={payModalOpen}
                    orderId={order.id}
                    orderCode={order.order_code}
                    finalAmount={remaining > 0 ? remaining : parseFloat(order.final_amount)}
                    onSuccess={() => setPayModalOpen(false)}
                    onCancel={() => setPayModalOpen(false)}
                />
            )}

            {/* Add items drawer */}
            {order && (
                <AddItemsDrawer
                    open={addItemsOpen}
                    orderId={order.id}
                    orderCode={order.order_code}
                    onClose={() => setAddItemsOpen(false)}
                />
            )}
        </>
    );
};

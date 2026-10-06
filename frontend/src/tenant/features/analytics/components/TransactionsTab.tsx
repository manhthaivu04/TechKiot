import { useState, useCallback } from 'react';
import {
    Card, Table, Input, Select, DatePicker, Button, Tag, Statistic, Row, Col,
    Tooltip, Modal, Descriptions, Divider, Badge,
} from 'antd';
import {
    SearchOutlined, ReloadOutlined, FileTextOutlined, DollarCircleOutlined,
    CalendarOutlined,
} from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { useTransactions } from '../hooks/useTransactions';
import type { TransactionParams } from '../services/transactions.service';
import type { Order, OrderItem } from '@/types';
import { ORDER_STATUS_CONFIG } from '@/types/order';

const { RangePicker } = DatePicker;
const { Option } = Select;

// ─── helpers ─────────────────────────────────────────────────────────────────

const fmt = (n: number | string) =>
    Number(n).toLocaleString('vi-VN') + ' ₫';

const PAYMENT_METHOD_LABELS: Record<string, string> = {
    cash:     '💵 Tiền mặt',
    card:     '💳 Thẻ',
    transfer: '🏦 Chuyển khoản',
    ewallet:  '📱 Ví điện tử',
};

const SOURCE_LABELS: Record<string, string> = {
    cashier:   'POS',
    qr_static: 'QR Tĩnh',
    qr_table:  'QR Bàn',
};

// ─── Columns ─────────────────────────────────────────────────────────────────

const buildColumns = (onViewDetail: (order: Order) => void): ColumnsType<Order> => [
    {
        title: 'Mã đơn',
        dataIndex: 'order_code',
        key: 'order_code',
        width: 160,
        render: (code: string) => (
            <span className="font-mono font-semibold text-emerald-700">{code}</span>
        ),
    },
    {
        title: 'Thời gian',
        dataIndex: 'created_at',
        key: 'created_at',
        width: 165,
        render: (v: string) => dayjs(v).format('DD/MM/YYYY HH:mm'),
    },
    {
        title: 'Khách hàng',
        key: 'customer',
        width: 170,
        render: (_: unknown, r: Order) => (
            <div>
                <div className="font-medium">{r.customer_name || <span className="text-gray-400 italic">Khách lẻ</span>}</div>
                {r.customer_phone && (
                    <div className="text-xs text-gray-500">{r.customer_phone}</div>
                )}
            </div>
        ),
    },
    {
        title: 'Kênh',
        dataIndex: 'source_channel',
        key: 'source_channel',
        width: 100,
        render: (ch: string) => (
            <Tag color="blue">{SOURCE_LABELS[ch] ?? ch}</Tag>
        ),
    },
    {
        title: 'Thanh toán',
        key: 'payment_method',
        width: 160,
        render: (_: unknown, r: Order) => {
            const methods = (r.payments ?? []).map((p: any) =>
                PAYMENT_METHOD_LABELS[p.payment_method] ?? p.payment_method
            );
            return methods.length ? (
                <div className="flex flex-col gap-1">
                    {methods.map((m, i) => (
                        <Tag key={i} color="green" style={{ fontSize: 11 }}>{m}</Tag>
                    ))}
                </div>
            ) : '—';
        },
    },
    {
        title: 'Tổng tiền',
        dataIndex: 'final_amount',
        key: 'final_amount',
        width: 130,
        align: 'right',
        render: (v: string) => (
            <span className="font-bold text-emerald-700">{fmt(v)}</span>
        ),
    },
    {
        title: '',
        key: 'action',
        width: 80,
        align: 'center',
        render: (_: unknown, record: Order) => (
            <Tooltip title="Xem chi tiết">
                <Button
                    type="text"
                    icon={<FileTextOutlined />}
                    onClick={() => onViewDetail(record)}
                />
            </Tooltip>
        ),
    },
];

// ─── Detail Modal ─────────────────────────────────────────────────────────────

const OrderDetailModal = ({
    order,
    open,
    onClose,
}: {
    order: Order | null;
    open: boolean;
    onClose: () => void;
}) => {
    if (!order) return null;
    const statusCfg = ORDER_STATUS_CONFIG[order.status] ?? { label: order.status_label, color: '#aaa', bg: '#f5f5f5' };

    return (
        <Modal
            open={open}
            onCancel={onClose}
            footer={<Button onClick={onClose}>Đóng</Button>}
            title={<span>Hóa đơn <span className="font-mono text-emerald-700">{order.order_code}</span></span>}
            width={620}
        >
            <Descriptions column={2} size="small" bordered className="mb-4">
                <Descriptions.Item label="Thời gian" span={2}>
                    {dayjs(order.created_at).format('DD/MM/YYYY HH:mm:ss')}
                </Descriptions.Item>
                <Descriptions.Item label="Trạng thái">
                    <Badge
                        text={statusCfg.label}
                        color={statusCfg.color}
                    />
                </Descriptions.Item>
                <Descriptions.Item label="Kênh bán">
                    {SOURCE_LABELS[order.source_channel] ?? order.source_channel}
                </Descriptions.Item>
                <Descriptions.Item label="Khách hàng">
                    {order.customer_name || 'Khách lẻ'}
                </Descriptions.Item>
                <Descriptions.Item label="Số điện thoại">
                    {order.customer_phone || '—'}
                </Descriptions.Item>
            </Descriptions>

            {/* Danh sách món */}
            <Divider orientation="left" plain>Danh sách món</Divider>
            <table className="w-full text-sm mb-4">
                <thead>
                    <tr className="bg-gray-50 text-gray-500">
                        <th className="text-left py-1 px-2">Món</th>
                        <th className="text-center py-1 px-2 w-16">SL</th>
                        <th className="text-right py-1 px-2 w-28">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    {(order.items ?? []).map((item: OrderItem) => (
                        <tr key={item.id} className="border-b last:border-0">
                            <td className="py-1 px-2">{item.item_name}</td>
                            <td className="text-center py-1 px-2">{item.quantity}</td>
                            <td className="text-right py-1 px-2 font-medium">{fmt(item.line_total)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* Tổng cộng */}
            <div className="flex flex-col items-end gap-1 text-sm mb-4">
                <div className="flex gap-8 text-gray-500">
                    <span>Tạm tính</span>
                    <span>{fmt(order.subtotal_amount)}</span>
                </div>
                {Number(order.tax_amount) > 0 && (
                    <div className="flex gap-8 text-gray-500">
                        <span>Thuế</span>
                        <span>{fmt(order.tax_amount)}</span>
                    </div>
                )}
                {Number(order.discount_amount) > 0 && (
                    <div className="flex gap-8 text-red-500">
                        <span>Giảm giá</span>
                        <span>- {fmt(order.discount_amount)}</span>
                    </div>
                )}
                <div className="flex gap-8 font-bold text-base text-emerald-700 border-t pt-1 mt-1">
                    <span>Tổng cộng</span>
                    <span>{fmt(order.final_amount)}</span>
                </div>
            </div>

            {/* Thanh toán */}
            <Divider orientation="left" plain>Thanh toán</Divider>
            {(order.payments ?? []).map((p: any, i: number) => (
                <div key={i} className="flex justify-between text-sm py-0.5">
                    <span>{PAYMENT_METHOD_LABELS[p.payment_method] ?? p.payment_method}</span>
                    <span className="font-medium">{fmt(p.amount)}</span>
                </div>
            ))}
        </Modal>
    );
};

// ─── Main Component ───────────────────────────────────────────────────────────

/**
 * TransactionsTab — Tab tra cứu hóa đơn đã giao dịch.
 *
 * Nằm trong analytics feature, dành cho OWNER / MANAGER.
 */
export const TransactionsTab = () => {
    const [params, setParams] = useState<TransactionParams>({
        page: 1,
        per_page: 20,
    });
    const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
    const [modalOpen, setModalOpen] = useState(false);

    const { data, isLoading } = useTransactions(params);

    const handleSearch = useCallback((value: string) => {
        setParams((p) => ({ ...p, search: value || undefined, page: 1 }));
    }, []);

    const handleDateRange = useCallback((_: unknown, dates: [string, string]) => {
        setParams((p) => ({
            ...p,
            date_from: dates[0] || undefined,
            date_to:   dates[1] || undefined,
            page: 1,
        }));
    }, []);

    const handleMethodChange = useCallback((value: string) => {
        setParams((p) => ({
            ...p,
            payment_method: (value as TransactionParams['payment_method']) || undefined,
            page: 1,
        }));
    }, []);

    const handleReset = useCallback(() => {
        setParams({ page: 1, per_page: 20 });
    }, []);

    const handleViewDetail = useCallback((order: Order) => {
        setSelectedOrder(order);
        setModalOpen(true);
    }, []);

    const columns = buildColumns(handleViewDetail);

    return (
        <div className="space-y-4">
            {/* Summary Cards */}
            {data?.summary && (
                <Row gutter={[16, 16]}>
                    <Col xs={24} sm={12}>
                        <Card className="shadow-sm text-center">
                            <Statistic
                                title="Tổng doanh thu (kết quả lọc)"
                                value={data.summary.total_revenue}
                                prefix={<DollarCircleOutlined />}
                                formatter={(v) => fmt(Number(v))}
                                valueStyle={{ color: '#10b981', fontWeight: 700 }}
                            />
                        </Card>
                    </Col>
                    <Col xs={24} sm={12}>
                        <Card className="shadow-sm text-center">
                            <Statistic
                                title="Số hóa đơn"
                                value={data.summary.total_orders}
                                prefix={<FileTextOutlined />}
                                suffix="đơn"
                                valueStyle={{ color: '#3b82f6', fontWeight: 700 }}
                            />
                        </Card>
                    </Col>
                </Row>
            )}

            {/* Filters */}
            <Card className="shadow-sm">
                <div className="flex flex-wrap gap-3 items-center">
                    {/* Search */}
                    <Input.Search
                        placeholder="Tìm mã đơn, tên, SĐT..."
                        allowClear
                        onSearch={handleSearch}
                        style={{ width: 240 }}
                        prefix={<SearchOutlined className="text-gray-400" />}
                    />

                    {/* Date range */}
                    <RangePicker
                        format="DD/MM/YYYY"
                        placeholder={['Từ ngày', 'Đến ngày']}
                        onChange={handleDateRange as any}
                        suffixIcon={<CalendarOutlined />}
                        style={{ width: 240 }}
                    />

                    {/* Payment method */}
                    <Select
                        allowClear
                        placeholder="Phương thức TT"
                        style={{ width: 180 }}
                        onChange={handleMethodChange}
                    >
                        <Option value="cash">💵 Tiền mặt</Option>
                        <Option value="card">💳 Thẻ ngân hàng</Option>
                        <Option value="transfer">🏦 Chuyển khoản</Option>
                        <Option value="ewallet">📱 Ví điện tử</Option>
                    </Select>

                    <Button
                        icon={<ReloadOutlined />}
                        onClick={handleReset}
                    >
                        Đặt lại
                    </Button>
                </div>
            </Card>

            {/* Table */}
            <Card className="shadow-sm">
                <Table<Order>
                    columns={columns}
                    dataSource={data?.items ?? []}
                    rowKey="id"
                    loading={isLoading}
                    scroll={{ x: 900 }}
                    pagination={{
                        current:   data?.meta?.current_page ?? 1,
                        pageSize:  data?.meta?.per_page ?? 20,
                        total:     data?.meta?.total ?? 0,
                        showSizeChanger: true,
                        pageSizeOptions: ['10', '20', '50'],
                        showTotal: (total, range) => `${range[0]}–${range[1]} / ${total} hóa đơn`,
                        onChange: (page, pageSize) =>
                            setParams((p) => ({ ...p, page, per_page: pageSize })),
                    }}
                    locale={{ emptyText: 'Không tìm thấy hóa đơn nào.' }}
                />
            </Card>

            {/* Detail Modal */}
            <OrderDetailModal
                order={selectedOrder}
                open={modalOpen}
                onClose={() => setModalOpen(false)}
            />
        </div>
    );
};

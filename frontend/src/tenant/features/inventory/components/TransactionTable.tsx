import { useState } from 'react';
import { Table, Tag, Typography, Select } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import type { InventoryTransaction, TransactionType } from '@/types';
import { TRANSACTION_TYPE_OPTIONS } from '@/types';
import { useInventoryTransactions } from '../services/inventory.hooks';
import dayjs from 'dayjs';

const { Text } = Typography;

interface TransactionTableProps {
    warehouseId?: string;
    itemId?: string;
}

export const TransactionTable = ({ warehouseId, itemId }: TransactionTableProps) => {
    const [page, setPage] = useState(1);
    const [typeFilter, setTypeFilter] = useState<TransactionType | 'ALL'>('ALL');

    const { data: response, isLoading } = useInventoryTransactions({
        warehouse_id: warehouseId,
        item_id: itemId,
        page,
        per_page: 15,
    });

    const transactions = response?.data || [];
    const meta = response?.meta;

    // Filter in-memory if backend doesn't support type filter yet, 
    // or ideally backend should handle it if added to TransactionListParams
    const filteredTransactions = typeFilter === 'ALL' 
        ? transactions 
        : transactions.filter(t => t.transaction_type === typeFilter);

    const getTypeColor = (type: TransactionType) => {
        return TRANSACTION_TYPE_OPTIONS.find((o) => o.value === type)?.color || 'default';
    };

    const columns: ColumnsType<InventoryTransaction> = [
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 150,
            render: (date: string) => dayjs(date).format('DD/MM/YYYY HH:mm'),
        },
        {
            title: 'Nguyên liệu',
            dataIndex: 'item_name',
            key: 'item_name',
            render: (name: string, record) => (
                <div>
                    <Text strong>{name}</Text>
                    <Text type="secondary" className="block text-xs">
                        Kho: {record.warehouse_name}
                    </Text>
                </div>
            ),
        },
        {
            title: 'Loại giao dịch',
            dataIndex: 'transaction_label',
            key: 'transaction_label',
            width: 150,
            render: (label: string, record) => (
                <Tag color={getTypeColor(record.transaction_type)}>{label}</Tag>
            ),
        },
        {
            title: 'Thay đổi',
            dataIndex: 'quantity_change',
            key: 'quantity_change',
            width: 120,
            align: 'right',
            render: (change: string, record) => {
                const num = parseFloat(change);
                const isPositive = num > 0;
                return (
                    <Text strong className={isPositive ? 'text-green-600' : 'text-red-500'}>
                        {isPositive ? '+' : ''}
                        {num.toLocaleString('vi-VN', { maximumFractionDigits: 3 })} {record.item_unit}
                    </Text>
                );
            },
        },
        {
            title: 'Tồn (Trước → Sau)',
            key: 'balance',
            width: 180,
            align: 'center',
            render: (_, record) => {
                const before = parseFloat(record.before_quantity);
                const after = parseFloat(record.after_quantity);
                return (
                    <div className="flex items-center justify-center gap-2 text-sm">
                        <Text type="secondary">{before.toLocaleString('vi-VN', { maximumFractionDigits: 3 })}</Text>
                        <span className="text-gray-300">→</span>
                        <Text strong>{after.toLocaleString('vi-VN', { maximumFractionDigits: 3 })}</Text>
                    </div>
                );
            },
        },
        {
            title: 'Nguồn',
            dataIndex: 'reference_type',
            key: 'reference_type',
            width: 130,
            render: (ref: string | null) => {
                if (ref === 'stock_document') return <Tag>Chứng từ kho</Tag>;
                if (ref === 'order') return <Tag>Đơn hàng</Tag>;
                return '—';
            },
        },
        {
            title: 'Người thực hiện',
            dataIndex: 'creator_name',
            key: 'creator_name',
            width: 140,
            render: (name: string | null) => name || '—',
        },
    ];

    return (
        <div>
            <div className="mb-4">
                <Select
                    value={typeFilter}
                    onChange={setTypeFilter}
                    style={{ width: 200 }}
                    placeholder="Lọc loại biến động"
                >
                    <Select.Option value="ALL">Tất cả biến động</Select.Option>
                    {TRANSACTION_TYPE_OPTIONS.map((opt) => (
                        <Select.Option key={opt.value} value={opt.value}>
                            {opt.label}
                        </Select.Option>
                    ))}
                </Select>
            </div>

            <Table
                columns={columns}
                dataSource={filteredTransactions}
                rowKey="id"
                loading={isLoading}
                pagination={{
                    current: page,
                    pageSize: meta?.per_page || 15,
                    total: meta?.total || 0,
                    onChange: (newPage) => setPage(newPage),
                    showSizeChanger: false,
                }}
            />
        </div>
    );
};

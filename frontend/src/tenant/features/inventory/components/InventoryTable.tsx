import { useState } from 'react';
import { Table, Input, Typography, Empty } from 'antd';
import { SearchOutlined, HistoryOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import type { InventoryItem } from '@/types';
import { useInventory } from '../services/inventory.hooks';
import dayjs from 'dayjs';

const { Text } = Typography;

interface InventoryTableProps {
    warehouseId?: string;
    onViewHistory: (itemId: string) => void;
}

export const InventoryTable = ({ warehouseId, onViewHistory }: InventoryTableProps) => {
    const [search, setSearch] = useState('');

    const { data: inventoryData, isLoading } = useInventory({
        warehouse_id: warehouseId,
        search: search || undefined,
    });

    const inventory = inventoryData?.data || [];

    const columns: ColumnsType<InventoryItem> = [
        {
            title: 'Tên nguyên liệu',
            dataIndex: 'item_name',
            key: 'item_name',
            render: (name: string) => <Text strong>{name}</Text>,
        },
        {
            title: 'Đơn vị',
            dataIndex: 'item_unit',
            key: 'item_unit',
            width: 120,
        },
        {
            title: 'Tồn kho',
            dataIndex: 'quantity',
            key: 'quantity',
            width: 150,
            align: 'right',
            render: (qty: string) => {
                const num = parseFloat(qty);
                const isOutOfStock = num <= 0;
                return (
                    <Text strong className={isOutOfStock ? 'text-red-500' : 'text-green-600'}>
                        {num.toLocaleString('vi-VN', { maximumFractionDigits: 3 })}
                    </Text>
                );
            },
        },
        {
            title: 'Kho',
            dataIndex: 'warehouse_name',
            key: 'warehouse_name',
            width: 180,
            render: (name: string) => <Text type="secondary">{name}</Text>,
        },
        {
            title: 'Cập nhật lần cuối',
            dataIndex: 'updated_at',
            key: 'updated_at',
            width: 180,
            render: (date: string) => dayjs(date).format('DD/MM/YYYY HH:mm'),
        },
        {
            title: 'Thao tác',
            key: 'action',
            width: 120,
            align: 'right',
            render: (_, record) => (
                <a
                    onClick={() => onViewHistory(record.item_id)}
                    className="text-blue-500 hover:text-blue-700 flex items-center justify-end gap-1"
                >
                    <HistoryOutlined />
                    <span className="text-sm">Lịch sử</span>
                </a>
            ),
        },
    ];

    return (
        <div>
            <div className="mb-4">
                <Input
                    placeholder="Tìm kiếm nguyên liệu..."
                    prefix={<SearchOutlined className="text-gray-400" />}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    style={{ width: 300 }}
                    allowClear
                />
            </div>

            <Table
                columns={columns}
                dataSource={inventory}
                rowKey="id"
                loading={isLoading}
                pagination={{ pageSize: 15, showSizeChanger: false }}
                locale={{
                    emptyText: (
                        <Empty description="Chưa có dữ liệu tồn kho. Dữ liệu sẽ xuất hiện khi có phiếu nhập kho." />
                    ),
                }}
            />
        </div>
    );
};

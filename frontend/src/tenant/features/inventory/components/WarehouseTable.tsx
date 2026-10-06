import { Table, Tag, Button, Space, Popconfirm, Tooltip, Typography } from 'antd';
import { EditOutlined, DeleteOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import type { Warehouse } from '@/types';
import dayjs from 'dayjs';

const { Text } = Typography;

interface WarehouseTableProps {
    warehouses: Warehouse[];
    isLoading: boolean;
    onEdit: (warehouse: Warehouse) => void;
    onDelete: (id: string) => void;
}

export const WarehouseTable = ({
    warehouses,
    isLoading,
    onEdit,
    onDelete,
}: WarehouseTableProps) => {
    const columns: ColumnsType<Warehouse> = [
        {
            title: 'Tên kho',
            dataIndex: 'name',
            key: 'name',
            sorter: (a, b) => a.name.localeCompare(b.name),
            render: (name: string, record) => (
                <div>
                    <Text strong>{name}</Text>
                    {record.is_default && (
                        <Tag color="blue" className="ml-2">Mặc định</Tag>
                    )}
                </div>
            ),
        },
        {
            title: 'Ngày tạo',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 180,
            render: (date: string) => dayjs(date).format('DD/MM/YYYY HH:mm'),
            sorter: (a, b) => dayjs(a.created_at).unix() - dayjs(b.created_at).unix(),
        },
        {
            title: 'Thao tác',
            key: 'action',
            width: 150,
            align: 'right',
            render: (_, record) => (
                <Space size="middle">
                    <Button
                        type="default"
                        icon={<EditOutlined />}
                        onClick={() => onEdit(record)}
                    />
                    {record.is_default ? (
                        <Tooltip title="Không thể xóa kho mặc định">
                            <Button danger icon={<DeleteOutlined />} disabled />
                        </Tooltip>
                    ) : (
                        <Popconfirm
                            title="Xóa kho chứa"
                            description={`Bạn có chắc chắn muốn xóa kho '${record.name}'?`}
                            onConfirm={() => onDelete(record.id)}
                            okText="Xóa"
                            cancelText="Hủy"
                            okButtonProps={{ danger: true }}
                        >
                            <Button danger icon={<DeleteOutlined />} />
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <Table
            columns={columns}
            dataSource={warehouses}
            rowKey="id"
            loading={isLoading}
            pagination={{ pageSize: 10, showSizeChanger: false }}
        />
    );
};

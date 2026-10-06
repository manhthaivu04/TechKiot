import { Card, Tag, Button, Popconfirm, Tooltip } from 'antd';
import { EditOutlined, DeleteOutlined, UserOutlined, EnvironmentOutlined, QrcodeOutlined } from '@ant-design/icons';
import { motion } from 'framer-motion';
import type { RestaurantTable } from '@/types';
import { TABLE_STATUS_OPTIONS } from '@/types';

interface TableCardProps {
    table: RestaurantTable;
    onEdit: (table: RestaurantTable) => void;
    onDelete: (id: string) => void;
    onShowQr?: (table: RestaurantTable) => void;
}

export const TableCard = ({ table, onEdit, onDelete, onShowQr }: TableCardProps) => {
    const statusConfig = TABLE_STATUS_OPTIONS.find((s) => s.value === table.status);

    const cardActions = [
        onShowQr && (
            <Tooltip title="Mã QR" key="qr">
                <Button
                    type="text"
                    icon={<QrcodeOutlined />}
                    onClick={() => onShowQr(table)}
                    style={{ color: '#059669' }}
                />
            </Tooltip>
        ),
        <Tooltip title="Sửa" key="edit">
            <Button
                type="text"
                icon={<EditOutlined />}
                onClick={() => onEdit(table)}
                style={{ color: '#10b981' }}
            />
        </Tooltip>,
        <Popconfirm
            key="delete"
            title="Xóa bàn"
            description={`Xóa bàn '${table.name}' (${table.uid})? Hành động này không thể hoàn tác.`}
            onConfirm={() => onDelete(table.id)}
            okText="Xóa"
            cancelText="Hủy"
            okButtonProps={{ danger: true }}
        >
            <Tooltip title="Xóa">
                <Button
                    type="text"
                    icon={<DeleteOutlined />}
                    danger
                />
            </Tooltip>
        </Popconfirm>,
    ].filter(Boolean) as React.ReactNode[];

    return (
        <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25 }}
            whileHover={{ y: -4 }}
        >
            <Card
                hoverable
                style={{
                    borderRadius: 12,
                    overflow: 'hidden',
                    border: '1px solid #f0f0f0',
                    height: '100%',
                }}
                styles={{
                    body: { padding: '20px' },
                }}
                actions={cardActions}
            >
                {/* UID badge */}
                <div style={{
                    fontSize: 12,
                    color: '#9ca3af',
                    fontFamily: 'monospace',
                    marginBottom: 4,
                    letterSpacing: '0.5px',
                }}>
                    {table.uid}
                </div>

                {/* Table name */}
                <div style={{
                    fontSize: 17,
                    fontWeight: 600,
                    color: '#1f2937',
                    marginBottom: 12,
                    lineHeight: 1.3,
                }}>
                    {table.name}
                </div>

                {/* Capacity */}
                <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 6,
                    fontSize: 13,
                    color: '#6b7280',
                    marginBottom: 6,
                }}>
                    <UserOutlined style={{ fontSize: 13 }} />
                    <span>{table.capacity} chỗ</span>
                </div>

                {/* Area */}
                <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 6,
                    fontSize: 13,
                    color: '#6b7280',
                    marginBottom: 14,
                }}>
                    <EnvironmentOutlined style={{ fontSize: 13 }} />
                    <span>{table.area?.name || 'Chưa phân khu vực'}</span>
                </div>

                {/* Status badge */}
                <Tag
                    color={statusConfig?.antColor || 'default'}
                    style={{
                        borderRadius: 20,
                        padding: '2px 14px',
                        fontSize: 13,
                        fontWeight: 500,
                    }}
                >
                    {table.status_label}
                </Tag>
            </Card>
        </motion.div>
    );
};

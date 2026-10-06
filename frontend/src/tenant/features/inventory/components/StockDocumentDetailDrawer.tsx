import { Drawer, Descriptions, Table, Tag, Typography, Divider } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import type { StockDocument, StockDocumentItem } from '@/types';
import { DOCUMENT_TYPE_OPTIONS, DOCUMENT_STATUS_OPTIONS } from '@/types';
import dayjs from 'dayjs';

const { Text } = Typography;

interface StockDocumentDetailDrawerProps {
    open: boolean;
    onClose: () => void;
    document: StockDocument | null;
}

export const StockDocumentDetailDrawer = ({
    open,
    onClose,
    document: doc,
}: StockDocumentDetailDrawerProps) => {
    if (!doc) return null;

    const typeOption = DOCUMENT_TYPE_OPTIONS.find((o) => o.value === doc.document_type);
    const statusOption = DOCUMENT_STATUS_OPTIONS.find((o) => o.value === doc.status);

    const totalValue = doc.items.reduce(
        (sum, item) => sum + parseFloat(item.total_cost || '0'),
        0
    );

    const itemColumns: ColumnsType<StockDocumentItem> = [
        {
            title: 'Tên nguyên liệu',
            dataIndex: 'item_name',
            key: 'item_name',
            render: (name: string | null) => <Text strong>{name || '—'}</Text>,
        },
        {
            title: 'Đơn vị',
            dataIndex: 'item_unit',
            key: 'item_unit',
            width: 90,
            render: (unit: string | null) => unit || '—',
        },
        {
            title: 'Số lượng',
            dataIndex: 'quantity',
            key: 'quantity',
            width: 110,
            align: 'right',
            render: (qty: string) =>
                parseFloat(qty).toLocaleString('vi-VN', { maximumFractionDigits: 3 }),
        },
        {
            title: 'Đơn giá',
            dataIndex: 'unit_cost',
            key: 'unit_cost',
            width: 130,
            align: 'right',
            render: (cost: string) => `${Number(cost).toLocaleString('vi-VN')} đ`,
        },
        {
            title: 'Thành tiền',
            dataIndex: 'total_cost',
            key: 'total_cost',
            width: 140,
            align: 'right',
            render: (cost: string) => (
                <Text strong className="text-blue-600">
                    {Number(cost).toLocaleString('vi-VN')} đ
                </Text>
            ),
        },
    ];

    return (
        <Drawer
            title={`Chi tiết chứng từ: ${doc.code}`}
            open={open}
            onClose={onClose}
            width={700}
            destroyOnClose
        >
            <Descriptions column={2} bordered size="small">
                <Descriptions.Item label="Mã chứng từ">{doc.code}</Descriptions.Item>
                <Descriptions.Item label="Loại">
                    <Tag color={typeOption?.color}>{doc.document_label}</Tag>
                </Descriptions.Item>
                <Descriptions.Item label="Kho">{doc.warehouse_name || '—'}</Descriptions.Item>
                <Descriptions.Item label="Trạng thái">
                    <Tag color={statusOption?.antColor}>{doc.status_label}</Tag>
                </Descriptions.Item>
                <Descriptions.Item label="Người tạo">{doc.creator_name || '—'}</Descriptions.Item>
                <Descriptions.Item label="Ngày tạo">
                    {dayjs(doc.created_at).format('DD/MM/YYYY HH:mm')}
                </Descriptions.Item>
                {doc.note && (
                    <Descriptions.Item label="Ghi chú" span={2}>
                        {doc.note}
                    </Descriptions.Item>
                )}
            </Descriptions>

            <Divider />

            <Text strong style={{ fontSize: 15, display: 'block', marginBottom: 12 }}>
                Chi tiết hàng hóa
            </Text>

            <Table
                columns={itemColumns}
                dataSource={doc.items}
                rowKey="id"
                pagination={false}
                size="small"
                summary={() => (
                    <Table.Summary fixed>
                        <Table.Summary.Row>
                            <Table.Summary.Cell index={0} colSpan={4} align="right">
                                <Text strong>Tổng cộng:</Text>
                            </Table.Summary.Cell>
                            <Table.Summary.Cell index={1} align="right">
                                <Text strong className="text-red-600 text-base">
                                    {totalValue.toLocaleString('vi-VN')} đ
                                </Text>
                            </Table.Summary.Cell>
                        </Table.Summary.Row>
                    </Table.Summary>
                )}
            />
        </Drawer>
    );
};

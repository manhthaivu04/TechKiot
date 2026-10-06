import { useState, useMemo } from 'react';
import { Table, Tag, Button, Space, Popconfirm, Select, message } from 'antd';
import { EyeOutlined, CheckCircleOutlined, CloseCircleOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import type { StockDocument, DocumentType, DocumentStatus } from '@/types';
import { DOCUMENT_TYPE_OPTIONS, DOCUMENT_STATUS_OPTIONS } from '@/types';
import { useConfirmStockDocument, useCancelStockDocument } from '../services/stock-document.hooks';
import dayjs from 'dayjs';
import type { AxiosError } from 'axios';
import type { ApiErrorResponse } from '@/api/http';

interface StockDocumentTableProps {
    documents: StockDocument[];
    isLoading: boolean;
    onViewDetail: (doc: StockDocument) => void;
}

export const StockDocumentTable = ({
    documents,
    isLoading,
    onViewDetail,
}: StockDocumentTableProps) => {
    const [typeFilter, setTypeFilter] = useState<DocumentType | 'ALL'>('ALL');
    const [statusFilter, setStatusFilter] = useState<DocumentStatus | 'ALL'>('ALL');

    const confirmMutation = useConfirmStockDocument();
    const cancelMutation = useCancelStockDocument();

    const filteredDocs = useMemo(() => {
        let result = documents;
        if (typeFilter !== 'ALL') {
            result = result.filter((d) => d.document_type === typeFilter);
        }
        if (statusFilter !== 'ALL') {
            result = result.filter((d) => d.status === statusFilter);
        }
        return result;
    }, [documents, typeFilter, statusFilter]);

    const handleConfirm = (id: string) => {
        confirmMutation.mutate(id, {
            onSuccess: () => message.success('Đã xác nhận chứng từ!'),
            onError: (error: unknown) => {
                const axiosErr = error as AxiosError<ApiErrorResponse>;
                message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra!');
            },
        });
    };

    const handleCancel = (id: string) => {
        cancelMutation.mutate(id, {
            onSuccess: () => message.success('Đã hủy chứng từ!'),
            onError: (error: unknown) => {
                const axiosErr = error as AxiosError<ApiErrorResponse>;
                message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra!');
            },
        });
    };

    const getTypeColor = (type: DocumentType) => {
        return DOCUMENT_TYPE_OPTIONS.find((o) => o.value === type)?.color || 'default';
    };

    const getStatusColor = (status: DocumentStatus) => {
        return DOCUMENT_STATUS_OPTIONS.find((o) => o.value === status)?.antColor || 'default';
    };

    const columns: ColumnsType<StockDocument> = [
        {
            title: 'Mã chứng từ',
            dataIndex: 'code',
            key: 'code',
            width: 130,
            render: (code: string) => <span className="font-semibold">{code}</span>,
        },
        {
            title: 'Loại',
            dataIndex: 'document_label',
            key: 'document_label',
            width: 160,
            render: (label: string, record) => (
                <Tag color={getTypeColor(record.document_type)}>{label}</Tag>
            ),
        },
        {
            title: 'Kho',
            dataIndex: 'warehouse_name',
            key: 'warehouse_name',
            width: 150,
            render: (name: string | null) => name || '—',
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status_label',
            key: 'status_label',
            width: 130,
            render: (label: string, record) => (
                <Tag color={getStatusColor(record.status)}>{label}</Tag>
            ),
        },
        {
            title: 'Người tạo',
            dataIndex: 'creator_name',
            key: 'creator_name',
            width: 140,
            render: (name: string | null) => name || '—',
        },
        {
            title: 'Ngày tạo',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 160,
            render: (date: string) => dayjs(date).format('DD/MM/YYYY HH:mm'),
            sorter: (a, b) => dayjs(a.created_at).unix() - dayjs(b.created_at).unix(),
            defaultSortOrder: 'descend',
        },
        {
            title: 'Thao tác',
            key: 'action',
            width: 180,
            align: 'right',
            render: (_, record) => (
                <Space size="small">
                    <Button
                        type="default"
                        icon={<EyeOutlined />}
                        onClick={() => onViewDetail(record)}
                        title="Xem chi tiết"
                        size="small"
                    />
                    {record.status === 'draft' && (
                        <>
                            <Popconfirm
                                title="Xác nhận chứng từ"
                                description="Sau khi xác nhận, tồn kho sẽ được cập nhật. Tiếp tục?"
                                onConfirm={() => handleConfirm(record.id)}
                                okText="Xác nhận"
                                cancelText="Hủy"
                            >
                                <Button
                                    type="primary"
                                    icon={<CheckCircleOutlined />}
                                    size="small"
                                    style={{ background: '#10b981', borderColor: '#10b981' }}
                                    title="Xác nhận"
                                    loading={confirmMutation.isPending}
                                />
                            </Popconfirm>
                            <Popconfirm
                                title="Hủy chứng từ"
                                description="Bạn có chắc muốn hủy chứng từ này?"
                                onConfirm={() => handleCancel(record.id)}
                                okText="Hủy phiếu"
                                cancelText="Đóng"
                                okButtonProps={{ danger: true }}
                            >
                                <Button
                                    danger
                                    icon={<CloseCircleOutlined />}
                                    size="small"
                                    title="Hủy phiếu"
                                    loading={cancelMutation.isPending}
                                />
                            </Popconfirm>
                        </>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div>
            {/* Filter Bar */}
            <div className="flex flex-wrap gap-3 mb-4">
                <Select
                    value={typeFilter}
                    onChange={setTypeFilter}
                    style={{ width: 200 }}
                    placeholder="Lọc theo loại"
                >
                    <Select.Option value="ALL">Tất cả loại</Select.Option>
                    {DOCUMENT_TYPE_OPTIONS.map((opt) => (
                        <Select.Option key={opt.value} value={opt.value}>
                            {opt.label}
                        </Select.Option>
                    ))}
                </Select>
                <Select
                    value={statusFilter}
                    onChange={setStatusFilter}
                    style={{ width: 180 }}
                    placeholder="Lọc theo trạng thái"
                >
                    <Select.Option value="ALL">Tất cả trạng thái</Select.Option>
                    {DOCUMENT_STATUS_OPTIONS.map((opt) => (
                        <Select.Option key={opt.value} value={opt.value}>
                            {opt.label}
                        </Select.Option>
                    ))}
                </Select>
            </div>

            <Table
                columns={columns}
                dataSource={filteredDocs}
                rowKey="id"
                loading={isLoading}
                pagination={{ pageSize: 10, showSizeChanger: false }}
                scroll={{ x: 'max-content' }}
            />
        </div>
    );
};

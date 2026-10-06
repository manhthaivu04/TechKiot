import { useState, useMemo } from 'react';
import { Row, Col, Card, Button, Popconfirm, Empty, Skeleton, Typography, message } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined, SortAscendingOutlined } from '@ant-design/icons';
import { motion, AnimatePresence } from 'framer-motion';
import { TableAreaModal } from '../components/TableAreaModal';
import {
    useTableAreas,
    useCreateTableArea,
    useUpdateTableArea,
    useDeleteTableArea,
} from '../services/table.hooks';
import type { TableArea, CreateTableAreaRequest, UpdateTableAreaRequest } from '@/types';
import type { AxiosError } from 'axios';
import type { ApiErrorResponse } from '@/api/http';

const { Title, Text, Paragraph } = Typography;

export const TableAreaPage = () => {
    // --- State ---
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [editingArea, setEditingArea] = useState<TableArea | null>(null);

    // --- Queries ---
    const { data: areaData, isLoading } = useTableAreas();
    const areas = useMemo(() => areaData?.data || [], [areaData?.data]);

    // --- Mutations ---
    const createMutation = useCreateTableArea();
    const updateMutation = useUpdateTableArea(editingArea?.id || '');
    const deleteMutation = useDeleteTableArea();

    // --- Handlers ---
    const handleAdd = () => {
        setEditingArea(null);
        setIsModalVisible(true);
    };

    const handleEdit = (area: TableArea) => {
        setEditingArea(area);
        setIsModalVisible(true);
    };

    const handleDelete = (id: string) => {
        deleteMutation.mutate(id, {
            onSuccess: () => {
                message.success('Đã xóa khu vực bàn!');
            },
            onError: (error: unknown) => {
                const axiosErr = error as AxiosError<ApiErrorResponse>;
                message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra khi xóa!');
            },
        });
    };

    const handleSubmit = (values: Record<string, unknown>) => {
        if (editingArea) {
            updateMutation.mutate(values as UpdateTableAreaRequest, {
                onSuccess: () => {
                    message.success('Cập nhật khu vực thành công!');
                    setIsModalVisible(false);
                },
                onError: (error: unknown) => {
                    const axiosErr = error as AxiosError<ApiErrorResponse>;
                    message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra!');
                },
            });
        } else {
            createMutation.mutate(values as unknown as CreateTableAreaRequest, {
                onSuccess: () => {
                    message.success('Thêm khu vực mới thành công!');
                    setIsModalVisible(false);
                },
                onError: (error: unknown) => {
                    const axiosErr = error as AxiosError<ApiErrorResponse>;
                    message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra!');
                },
            });
        }
    };

    return (
        <div>
            {/* Page Header */}
            <div style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                marginBottom: 24,
            }}>
                <div>
                    <Title level={3} style={{ margin: 0, color: '#1f2937' }}>
                        Khu vực bàn
                    </Title>
                    <Text type="secondary" style={{ fontSize: 14 }}>
                        Quản lý các khu vực bố trí bàn trong nhà hàng
                    </Text>
                </div>
                <Button
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={handleAdd}
                    size="large"
                    style={{
                        background: '#10b981',
                        borderColor: '#10b981',
                        borderRadius: 8,
                        fontWeight: 500,
                    }}
                >
                    Thêm khu vực
                </Button>
            </div>

            {/* Loading */}
            {isLoading && (
                <Row gutter={[16, 16]}>
                    {[1, 2, 3].map((i) => (
                        <Col xs={24} sm={12} lg={8} key={i}>
                            <Card style={{ borderRadius: 12 }}>
                                <Skeleton active paragraph={{ rows: 2 }} />
                            </Card>
                        </Col>
                    ))}
                </Row>
            )}

            {/* Empty state */}
            {!isLoading && areas.length === 0 && (
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4 }}
                >
                    <Card style={{
                        borderRadius: 16,
                        textAlign: 'center',
                        padding: '60px 20px',
                        border: '2px dashed #e5e7eb',
                        background: '#fafafa',
                    }}>
                        <Empty
                            description={
                                <div>
                                    <Text style={{ fontSize: 16, color: '#6b7280', display: 'block', marginBottom: 8 }}>
                                        Chưa có khu vực bàn nào
                                    </Text>
                                    <Text type="secondary">
                                        Tạo khu vực đầu tiên để bắt đầu quản lý bàn ăn
                                    </Text>
                                </div>
                            }
                        >
                            <Button
                                type="primary"
                                icon={<PlusOutlined />}
                                onClick={handleAdd}
                                style={{
                                    background: '#10b981',
                                    borderColor: '#10b981',
                                    borderRadius: 8,
                                    marginTop: 8,
                                }}
                            >
                                Thêm khu vực đầu tiên
                            </Button>
                        </Empty>
                    </Card>
                </motion.div>
            )}

            {/* Area Cards */}
            {!isLoading && areas.length > 0 && (
                <AnimatePresence>
                    <Row gutter={[16, 16]}>
                        {areas.map((area, index) => (
                            <Col xs={24} sm={12} lg={8} xl={6} key={area.id}>
                                <motion.div
                                    initial={{ opacity: 0, y: 12 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.25, delay: index * 0.05 }}
                                    whileHover={{ y: -4 }}
                                    style={{ height: '100%' }}
                                >
                                    <Card
                                        hoverable
                                        style={{
                                            borderRadius: 12,
                                            height: '100%',
                                            border: '1px solid #f0f0f0',
                                            overflow: 'hidden',
                                        }}
                                        styles={{
                                            body: { padding: '20px', display: 'flex', flexDirection: 'column', height: '100%' },
                                        }}
                                    >
                                        {/* Header */}
                                        <div style={{ flex: 1 }}>
                                            <div style={{
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                alignItems: 'flex-start',
                                                marginBottom: 8,
                                            }}>
                                                <Text strong style={{ fontSize: 17, color: '#1f2937' }}>
                                                    {area.name}
                                                </Text>
                                                <div style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: 4,
                                                    color: '#9ca3af',
                                                    fontSize: 12,
                                                    background: '#f3f4f6',
                                                    padding: '2px 8px',
                                                    borderRadius: 6,
                                                    flexShrink: 0,
                                                }}>
                                                    <SortAscendingOutlined style={{ fontSize: 11 }} />
                                                    {area.display_order}
                                                </div>
                                            </div>

                                            {area.description && (
                                                <Paragraph
                                                    type="secondary"
                                                    ellipsis={{ rows: 2 }}
                                                    style={{ fontSize: 13, marginBottom: 0, color: '#9ca3af' }}
                                                >
                                                    {area.description}
                                                </Paragraph>
                                            )}

                                            {!area.description && (
                                                <Text type="secondary" italic style={{ fontSize: 13, color: '#d1d5db' }}>
                                                    Không có mô tả
                                                </Text>
                                            )}
                                        </div>

                                        {/* Actions */}
                                        <div style={{
                                            display: 'flex',
                                            gap: 8,
                                            marginTop: 16,
                                            paddingTop: 12,
                                            borderTop: '1px solid #f3f4f6',
                                        }}>
                                            <Button
                                                type="text"
                                                icon={<EditOutlined />}
                                                onClick={() => handleEdit(area)}
                                                style={{ color: '#10b981', flex: 1 }}
                                            >
                                                Sửa
                                            </Button>
                                            <Popconfirm
                                                title="Xóa khu vực"
                                                description={`Xóa khu vực '${area.name}'? Các bàn thuộc khu vực này sẽ chuyển thành chưa phân khu vực.`}
                                                onConfirm={() => handleDelete(area.id)}
                                                okText="Xóa"
                                                cancelText="Hủy"
                                                okButtonProps={{ danger: true }}
                                            >
                                                <Button
                                                    type="text"
                                                    icon={<DeleteOutlined />}
                                                    danger
                                                    style={{ flex: 1 }}
                                                >
                                                    Xóa
                                                </Button>
                                            </Popconfirm>
                                        </div>
                                    </Card>
                                </motion.div>
                            </Col>
                        ))}
                    </Row>
                </AnimatePresence>
            )}

            {/* Modal */}
            <TableAreaModal
                visible={isModalVisible}
                onClose={() => setIsModalVisible(false)}
                onSubmit={handleSubmit}
                initialData={editingArea}
                isLoading={createMutation.isPending || updateMutation.isPending}
            />
        </div>
    );
};

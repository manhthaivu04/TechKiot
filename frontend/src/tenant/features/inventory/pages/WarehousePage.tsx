import { useState, useMemo } from 'react';
import { Button, Typography, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import { WarehouseTable } from '../components/WarehouseTable';
import { WarehouseModal } from '../components/WarehouseModal';
import {
    useWarehouses,
    useCreateWarehouse,
    useUpdateWarehouse,
    useDeleteWarehouse,
} from '../services/warehouse.hooks';
import type { Warehouse, CreateWarehouseRequest, UpdateWarehouseRequest } from '@/types';
import type { AxiosError } from 'axios';
import type { ApiErrorResponse } from '@/api/http';

const { Title, Text } = Typography;

export const WarehousePage = () => {
    // --- State ---
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [editingWarehouse, setEditingWarehouse] = useState<Warehouse | null>(null);

    // --- Queries ---
    const { data: warehouseData, isLoading } = useWarehouses();
    const warehouses = useMemo(() => warehouseData?.data || [], [warehouseData?.data]);

    // --- Mutations ---
    const createMutation = useCreateWarehouse();
    const updateMutation = useUpdateWarehouse(editingWarehouse?.id || '');
    const deleteMutation = useDeleteWarehouse();

    // --- Handlers ---
    const handleAdd = () => {
        setEditingWarehouse(null);
        setIsModalVisible(true);
    };

    const handleEdit = (warehouse: Warehouse) => {
        setEditingWarehouse(warehouse);
        setIsModalVisible(true);
    };

    const handleDelete = (id: string) => {
        deleteMutation.mutate(id, {
            onSuccess: () => {
                message.success('Đã xóa kho chứa!');
            },
            onError: (error: unknown) => {
                const axiosErr = error as AxiosError<ApiErrorResponse>;
                message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra khi xóa!');
            },
        });
    };

    const handleSubmit = (values: Record<string, unknown>) => {
        if (editingWarehouse) {
            updateMutation.mutate(values as UpdateWarehouseRequest, {
                onSuccess: () => {
                    message.success('Cập nhật kho chứa thành công!');
                    setIsModalVisible(false);
                },
                onError: (error: unknown) => {
                    const axiosErr = error as AxiosError<ApiErrorResponse>;
                    message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra!');
                },
            });
        } else {
            createMutation.mutate(values as unknown as CreateWarehouseRequest, {
                onSuccess: () => {
                    message.success('Thêm kho chứa mới thành công!');
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
                        Quản lý Kho chứa
                    </Title>
                    <Text type="secondary" style={{ fontSize: 14 }}>
                        Quản lý các kho chứa nguyên liệu và hàng hóa
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
                    Thêm kho mới
                </Button>
            </div>

            {/* Table */}
            <div style={{
                background: '#fff',
                borderRadius: 12,
                padding: 24,
            }}>
                <WarehouseTable
                    warehouses={warehouses}
                    isLoading={isLoading}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                />
            </div>

            {/* Modal */}
            <WarehouseModal
                open={isModalVisible}
                onClose={() => setIsModalVisible(false)}
                onSubmit={handleSubmit}
                warehouse={editingWarehouse}
                isLoading={createMutation.isPending || updateMutation.isPending}
            />
        </div>
    );
};

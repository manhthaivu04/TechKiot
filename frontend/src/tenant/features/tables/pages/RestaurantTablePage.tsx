import { useState, useMemo, useCallback } from 'react';
import { Row, Col, Table, Tag, Button, Popconfirm, Empty, Pagination, Typography, Skeleton, message, Tooltip } from 'antd';
import { EditOutlined, DeleteOutlined, UserOutlined, EnvironmentOutlined, QrcodeOutlined } from '@ant-design/icons';
import { motion } from 'framer-motion';
import { TableCard } from '../components/TableCard';
import { TableSummaryBar } from '../components/TableSummaryBar';
import { TableFilterBar, type ViewMode } from '../components/TableFilterBar';
import { RestaurantTableModal } from '../components/RestaurantTableModal';
import { TableQrModal } from '../components/TableQrModal';
import { useFeatureFlag } from '@/auth/hooks/useFeatureFlag';
import {
    useTableAreas,
    useRestaurantTables,
    useCreateRestaurantTable,
    useUpdateRestaurantTable,
    useDeleteRestaurantTable,
} from '../services/table.hooks';
import type { RestaurantTable as RestaurantTableType, TableStatus, CreateRestaurantTableRequest, UpdateRestaurantTableRequest } from '@/types';
import { TABLE_STATUS_OPTIONS, FeatureCode } from '@/types';
import type { AxiosError } from 'axios';
import type { ApiErrorResponse } from '@/api/http';
import type { ColumnsType } from 'antd/es/table';

const { Title, Text } = Typography;

export const RestaurantTablePage = () => {
    // --- Filter state ---
    const [selectedAreaId, setSelectedAreaId] = useState<string | undefined>(undefined);
    const [selectedStatus, setSelectedStatus] = useState<TableStatus | undefined>(undefined);
    const [searchValue, setSearchValue] = useState('');
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [viewMode, setViewMode] = useState<ViewMode>('grid');

    // --- Modal state ---
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [editingTable, setEditingTable] = useState<RestaurantTableType | null>(null);
    const [serverErrors, setServerErrors] = useState<Record<string, string[]> | null>(null);

    // --- QR Modal state ---
    const hasQrTableOrder = useFeatureFlag(FeatureCode.QR_TABLE_ORDER);
    const [qrTable, setQrTable] = useState<RestaurantTableType | null>(null);
    const [isQrModalVisible, setIsQrModalVisible] = useState(false);

    const handleShowQr = useCallback((table: RestaurantTableType) => {
        setQrTable(table);
        setIsQrModalVisible(true);
    }, []);

    // --- Debounce search ---
    const searchTimeoutRef = useState<ReturnType<typeof setTimeout> | null>(null);
    const handleSearchChange = useCallback((value: string) => {
        setSearchValue(value);
        if (searchTimeoutRef[0]) clearTimeout(searchTimeoutRef[0]);
        searchTimeoutRef[0] = setTimeout(() => {
            setDebouncedSearch(value);
            setCurrentPage(1);
        }, 400);
    }, [searchTimeoutRef]);

    // --- Build query params ---
    const queryParams = useMemo(() => {
        const params: Record<string, unknown> = { per_page: 15, page: currentPage };
        if (selectedAreaId && selectedAreaId !== '__none__') params.area_id = selectedAreaId;
        // For "unassigned" we pass area_id as empty or handle specially
        // The API may support area_id=null — for now we only filter by valid UUIDs
        if (selectedStatus) params.status = selectedStatus;
        if (debouncedSearch) params.search = debouncedSearch;
        return params;
    }, [selectedAreaId, selectedStatus, debouncedSearch, currentPage]);

    // --- Queries ---
    const { data: areaData } = useTableAreas();
    const { data: tableData, isLoading: isLoadingTables } = useRestaurantTables(queryParams);

    const areas = useMemo(() => areaData?.data || [], [areaData?.data]);
    const tables = useMemo(() => tableData?.data || [], [tableData?.data]);
    const meta = tableData?.meta;

    // --- Mutations ---
    const createMutation = useCreateRestaurantTable();
    const updateMutation = useUpdateRestaurantTable(editingTable?.id || '');
    const deleteMutation = useDeleteRestaurantTable();

    // --- Handlers ---
    const handleAreaChange = (areaId: string | undefined) => {
        setSelectedAreaId(areaId);
        setCurrentPage(1);
    };

    const handleStatusChange = (status: TableStatus | undefined) => {
        setSelectedStatus(status);
        setCurrentPage(1);
    };

    const handleAdd = () => {
        setEditingTable(null);
        setServerErrors(null);
        setIsModalVisible(true);
    };

    const handleEdit = (table: RestaurantTableType) => {
        setEditingTable(table);
        setServerErrors(null);
        setIsModalVisible(true);
    };

    const handleDelete = (id: string) => {
        deleteMutation.mutate(id, {
            onSuccess: () => {
                message.success('Đã xóa bàn ăn!');
            },
            onError: (error: unknown) => {
                const axiosErr = error as AxiosError<ApiErrorResponse>;
                message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra khi xóa!');
            },
        });
    };

    const handleSubmit = (values: Record<string, unknown>) => {
        setServerErrors(null);

        if (editingTable) {
            updateMutation.mutate(values as UpdateRestaurantTableRequest, {
                onSuccess: () => {
                    message.success('Cập nhật bàn ăn thành công!');
                    setIsModalVisible(false);
                },
                onError: (error: unknown) => {
                    const axiosErr = error as AxiosError<ApiErrorResponse>;
                    if (axiosErr.response?.status === 422 && axiosErr.response?.data?.errors) {
                        setServerErrors(axiosErr.response.data.errors);
                    } else {
                        message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra!');
                    }
                },
            });
        } else {
            createMutation.mutate(values as unknown as CreateRestaurantTableRequest, {
                onSuccess: () => {
                    message.success('Thêm bàn ăn mới thành công!');
                    setIsModalVisible(false);
                },
                onError: (error: unknown) => {
                    const axiosErr = error as AxiosError<ApiErrorResponse>;
                    if (axiosErr.response?.status === 422 && axiosErr.response?.data?.errors) {
                        setServerErrors(axiosErr.response.data.errors);
                    } else {
                        message.error(axiosErr.response?.data?.message || 'Có lỗi xảy ra!');
                    }
                },
            });
        }
    };

    // --- Table columns for list view ---
    const columns: ColumnsType<RestaurantTableType> = [
        {
            title: 'Mã bàn',
            dataIndex: 'uid',
            key: 'uid',
            width: 100,
            render: (uid: string) => (
                <Text code style={{ fontSize: 13 }}>{uid}</Text>
            ),
        },
        {
            title: 'Tên bàn',
            dataIndex: 'name',
            key: 'name',
            render: (name: string) => (
                <Text strong>{name}</Text>
            ),
        },
        {
            title: 'Sức chứa',
            dataIndex: 'capacity',
            key: 'capacity',
            width: 100,
            align: 'center',
            render: (capacity: number) => (
                <span>
                    <UserOutlined style={{ marginRight: 4, color: '#9ca3af' }} />
                    {capacity}
                </span>
            ),
        },
        {
            title: 'Khu vực',
            key: 'area',
            width: 160,
            render: (_: unknown, record: RestaurantTableType) => (
                <span>
                    <EnvironmentOutlined style={{ marginRight: 4, color: '#9ca3af' }} />
                    {record.area?.name || <Text type="secondary" italic>Chưa phân khu vực</Text>}
                </span>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 150,
            align: 'center',
            render: (_: unknown, record: RestaurantTableType) => {
                const config = TABLE_STATUS_OPTIONS.find((s) => s.value === record.status);
                return (
                    <Tag
                        color={config?.antColor || 'default'}
                        style={{ borderRadius: 20, padding: '1px 12px', fontWeight: 500 }}
                    >
                        {record.status_label}
                    </Tag>
                );
            },
        },
        {
            title: 'Hành động',
            key: 'action',
            width: 140,
            align: 'center',
            render: (_: unknown, record: RestaurantTableType) => (
                <div style={{ display: 'flex', gap: 4, justifyContent: 'center' }}>
                    {hasQrTableOrder && (
                        <Tooltip title="Mã QR">
                            <Button
                                type="text"
                                size="small"
                                icon={<QrcodeOutlined />}
                                onClick={() => handleShowQr(record)}
                                style={{ color: '#059669' }}
                            />
                        </Tooltip>
                    )}
                    <Tooltip title="Sửa">
                        <Button
                            type="text"
                            size="small"
                            icon={<EditOutlined />}
                            onClick={() => handleEdit(record)}
                            style={{ color: '#10b981' }}
                        />
                    </Tooltip>
                    <Popconfirm
                        title="Xóa bàn"
                        description={`Xóa bàn '${record.name}' (${record.uid})? Hành động này không thể hoàn tác.`}
                        onConfirm={() => handleDelete(record.id)}
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Tooltip title="Xóa">
                            <Button type="text" size="small" icon={<DeleteOutlined />} danger />
                        </Tooltip>
                    </Popconfirm>
                </div>
            ),
        },
    ];

    return (
        <div>
            {/* Page Header */}
            <div style={{ marginBottom: 20 }}>
                <Title level={3} style={{ margin: 0, color: '#1f2937' }}>
                    Danh sách bàn ăn
                </Title>
                <Text type="secondary" style={{ fontSize: 14 }}>
                    Quản lý tất cả bàn ăn trong nhà hàng
                </Text>
            </div>

            {/* Summary Bar */}
            <TableSummaryBar tables={tables} total={meta?.total || tables.length} />

            {/* Filter Bar */}
            <TableFilterBar
                areas={areas}
                selectedAreaId={selectedAreaId}
                selectedStatus={selectedStatus}
                searchValue={searchValue}
                viewMode={viewMode}
                onAreaChange={handleAreaChange}
                onStatusChange={handleStatusChange}
                onSearchChange={handleSearchChange}
                onViewModeChange={setViewMode}
                onAddTable={handleAdd}
            />

            {/* Loading */}
            {isLoadingTables && viewMode === 'grid' && (
                <Row gutter={[16, 16]}>
                    {Array.from({ length: 6 }).map((_, i) => (
                        <Col xs={24} sm={12} md={8} lg={6} key={i}>
                            <div style={{
                                background: '#fff',
                                borderRadius: 12,
                                padding: 20,
                                border: '1px solid #f0f0f0',
                            }}>
                                <Skeleton active paragraph={{ rows: 4 }} />
                            </div>
                        </Col>
                    ))}
                </Row>
            )}

            {/* Empty state */}
            {!isLoadingTables && tables.length === 0 && (
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4 }}
                >
                    <div style={{
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
                                        {debouncedSearch || selectedAreaId || selectedStatus
                                            ? 'Không tìm thấy bàn phù hợp'
                                            : 'Chưa có bàn ăn nào'}
                                    </Text>
                                    <Text type="secondary">
                                        {debouncedSearch || selectedAreaId || selectedStatus
                                            ? 'Thử thay đổi bộ lọc để xem kết quả khác'
                                            : 'Thêm bàn ăn đầu tiên để bắt đầu quản lý'}
                                    </Text>
                                </div>
                            }
                        />
                    </div>
                </motion.div>
            )}

            {/* Grid View */}
            {!isLoadingTables && tables.length > 0 && viewMode === 'grid' && (
                <Row gutter={[16, 16]}>
                    {tables.map((table) => (
                        <Col xs={24} sm={12} md={8} lg={6} key={table.id}>
                            <TableCard
                                table={table}
                                onEdit={handleEdit}
                                onDelete={handleDelete}
                                onShowQr={hasQrTableOrder ? handleShowQr : undefined}
                            />
                        </Col>
                    ))}
                </Row>
            )}

            {/* Table View */}
            {!isLoadingTables && tables.length > 0 && viewMode === 'table' && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.3 }}
                >
                    <Table
                        columns={columns}
                        dataSource={tables}
                        rowKey="id"
                        pagination={false}
                        style={{
                            background: '#fff',
                            borderRadius: 12,
                            overflow: 'hidden',
                        }}
                        size="middle"
                    />
                </motion.div>
            )}

            {/* Pagination */}
            {meta && meta.last_page > 1 && (
                <div style={{
                    display: 'flex',
                    justifyContent: 'center',
                    marginTop: 24,
                    padding: '12px 0',
                }}>
                    <Pagination
                        current={meta.current_page}
                        total={meta.total}
                        pageSize={meta.per_page}
                        onChange={(page) => setCurrentPage(page)}
                        showSizeChanger={false}
                        showTotal={(total, range) =>
                            `${range[0]}-${range[1]} trên ${total} bàn`
                        }
                    />
                </div>
            )}

            {/* Modal */}
            <RestaurantTableModal
                visible={isModalVisible}
                onClose={() => setIsModalVisible(false)}
                onSubmit={handleSubmit}
                initialData={editingTable}
                isLoading={createMutation.isPending || updateMutation.isPending}
                areas={areas}
                serverErrors={serverErrors}
            />

            {/* Table QR Modal */}
            <TableQrModal
                visible={isQrModalVisible}
                table={qrTable}
                onClose={() => setIsQrModalVisible(false)}
            />
        </div>
    );
};

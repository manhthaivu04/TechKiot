import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, Table, Button, Space, Input, Select, Row, Col, Modal, message, Tooltip, Tag } from 'antd';
import { PlusOutlined, EditOutlined, EyeOutlined, LockOutlined, UnlockOutlined } from '@ant-design/icons';
import { restaurantService } from '../services/restaurant.service';
import { useRestaurants } from '../services/restaurant.hooks';
import { formatDate } from '@/lib/formatters';
import { getErrorMessage } from '@/lib/error-handlers';
import { LoadingState } from '@/lib/loading-state';
import { queryClient } from '@/api/query-client';
import type { Restaurant, RestaurantListParams } from '@/types';
import { useDebounce } from '@/lib/use-debounce';

export const RestaurantListPage = () => {
    const navigate = useNavigate();

    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search, 400);
    const [status, setStatus] = useState<string | undefined>(undefined);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(15);

    const params: RestaurantListParams = useMemo(
        () => ({
            per_page: perPage,
            page,
            search: debouncedSearch || undefined,
            status: status || undefined,
        }),
        [perPage, page, debouncedSearch, status]
    );

    const [lockingId, setLockingId] = useState<string>('');
    const [unlockedId, setUnlockedId] = useState<string>('');

    const { data, isLoading, error } = useRestaurants(params);

    const restaurants = data?.data ?? [];
    const pagination = data?.meta ?? undefined;

    useEffect(() => {
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setPage(1);
    }, [debouncedSearch, status, perPage]);

    const handleLock = async (record: Restaurant) => {
        Modal.confirm({
            title: 'Khóa Nhà Hàng',
            content: `Bạn có chắc muốn khóa nhà hàng "${record.name}" không?`,
            okText: 'Khóa',
            cancelText: 'Hủy',
            okButtonProps: { danger: true },
            onOk: async () => {
                setLockingId(record.id);
                try {
                    await restaurantService.lockRestaurant(record.id);
                    message.success('Khóa nhà hàng thành công');
                    queryClient.invalidateQueries({ queryKey: ['restaurants'] });
                } catch (e) {
                    message.error(getErrorMessage(e));
                } finally {
                    setLockingId('');
                }
            },
        });
    };

    const handleUnlock = async (record: Restaurant) => {
        Modal.confirm({
            title: 'Mở Khóa Nhà Hàng',
            content: `Bạn có chắc muốn mở khóa nhà hàng "${record.name}" không?`,
            okText: 'Mở Khóa',
            cancelText: 'Hủy',
            onOk: async () => {
                setUnlockedId(record.id);
                try {
                    await restaurantService.unlockRestaurant(record.id);
                    message.success('Mở khóa nhà hàng thành công');
                    queryClient.invalidateQueries({ queryKey: ['restaurants'] });
                } catch (e) {
                    message.error(getErrorMessage(e));
                } finally {
                    setUnlockedId('');
                }
            },
        });
    };

    const columns = [
        {
            title: 'Tên Nhà Hàng',
            dataIndex: 'name',
            key: 'name',
            width: 220,
        },
        {
            title: 'Địa Chỉ',
            dataIndex: 'address',
            key: 'address',
            render: (text: string) => text || '—',
            width: 240,
        },
        {
            title: 'Điện Thoại',
            dataIndex: 'phone',
            key: 'phone',
            render: (text: string) => text || '—',
            width: 140,
        },
        {
            title: 'Trạng Thái',
            dataIndex: 'status_label',
            key: 'status',
            render: (text: string, record: Restaurant) => (
                <Tag color={record.status === 'active' ? 'green' : 'red'}>{text}</Tag>
            ),
            width: 140,
        },
        {
            title: 'Gói Dịch Vụ',
            dataIndex: ['active_subscription', 'package', 'name'],
            key: 'package',
            render: (text: string) => text || 'Chưa có',
            width: 180,
        },
        {
            title: 'Ngày Tạo',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (text: string) => formatDate(text),
            width: 140,
        },
        {
            title: 'Hành Động',
            key: 'actions',
            fixed: 'right' as const,
            width: 160,
            render: (_: unknown, record: Restaurant) => (
                <Space size="small">
                    <Tooltip title="Chi tiết">
                        <Button
                            type="text"
                            size="small"
                            icon={<EyeOutlined />}
                            onClick={() => navigate(`/super-admin/restaurants/${record.id}`)}
                        />
                    </Tooltip>
                    <Tooltip title="Sửa">
                        <Button
                            type="text"
                            size="small"
                            icon={<EditOutlined />}
                            onClick={() => navigate(`/super-admin/restaurants/${record.id}/edit`)}
                        />
                    </Tooltip>
                    {record.status !== 'active' ? (
                        <Tooltip title="Mở khóa">
                            <Button
                                type="text"
                                size="small"
                                icon={<UnlockOutlined />}
                                loading={unlockedId === record.id}
                                onClick={() => handleUnlock(record)}
                            />
                        </Tooltip>
                    ) : (
                        <Tooltip title="Khóa">
                            <Button
                                type="text"
                                size="small"
                                danger
                                icon={<LockOutlined />}
                                loading={lockingId === record.id}
                                onClick={() => handleLock(record)}
                            />
                        </Tooltip>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <Card
            title="Nhà hàng"
            extra={
                <Button
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={() => navigate('/super-admin/restaurants/new')}
                >
                    Tạo nhà hàng
                </Button>
            }
        >
            <Row gutter={[16, 16]} className="mb-4">
                <Col xs={24} sm={12} md={8}>
                    <Input
                        placeholder="Tìm kiếm theo tên..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        allowClear
                    />
                </Col>
                <Col xs={24} sm={12} md={8}>
                    <Select
                        placeholder="Lọc theo trạng thái"
                        allowClear
                        value={status}
                        onChange={(v) => setStatus(v)}
                        style={{ width: '100%' }}
                        options={[
                            { label: 'Hoạt động', value: 'active' },
                            { label: 'Khóa', value: 'locked' },
                            { label: 'Tạm dừng', value: 'suspended' },
                        ]}
                    />
                </Col>
                <Col xs={24} sm={12} md={8}>
                    <Select
                        placeholder="Số dòng / trang"
                        value={perPage}
                        onChange={(v) => setPerPage(v)}
                        style={{ width: '100%' }}
                        options={[
                            { label: '15', value: 15 },
                            { label: '30', value: 30 },
                            { label: '50', value: 50 },
                        ]}
                    />
                </Col>
            </Row>

            <LoadingState
                isLoading={isLoading}
                isEmpty={restaurants.length === 0}
                error={error}
                emptyMessage="Không có nhà hàng nào"
            >
                <Table
                    dataSource={restaurants.map((r) => ({ ...r, key: r.id }))}
                    columns={columns}
                    pagination={{
                        current: pagination?.current_page || 1,
                        pageSize: pagination?.per_page || perPage,
                        total: pagination?.total || 0,
                        onChange: (nextPage) => setPage(nextPage),
                        showSizeChanger: false,
                    }}
                    scroll={{ x: 1200 }}
                />
            </LoadingState>
        </Card>
    );
};

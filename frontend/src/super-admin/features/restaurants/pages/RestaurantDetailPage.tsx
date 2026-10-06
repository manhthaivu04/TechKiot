import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    Card,
    Button,
    Space,
    Spin,
    Descriptions,
    Tag,
    Tabs,
    Table,
    Modal,
    Select,
    message,
    Alert,
} from 'antd';
import {
    ArrowLeftOutlined,
    EditOutlined,
    LockOutlined,
    UnlockOutlined,
} from '@ant-design/icons';
import {
    useRestaurant,
    useLockRestaurant,
    useUnlockRestaurant,
} from '../services/restaurant.hooks';
import {
    useRestaurantSubscriptions,
    useActiveSubscription,
    useAssignPackage,
    useCancelSubscription,
} from '@/super-admin/features/subscriptions/services/subscription.hooks';
import { usePackages } from '@/super-admin/features/packages/services/package.hooks';
import { formatDate, formatCurrency, getDaysRemaining, isSubscriptionExpired } from '@/lib/formatters';
import { getErrorMessage } from '@/lib/error-handlers';
import type { Subscription, AssignPackageRequest } from '@/types';

export const RestaurantDetailPage = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [isAssignModalVisible, setIsAssignModalVisible] = useState(false);
    const [selectedPackageId, setSelectedPackageId] = useState<string>('');

    const { data: restaurant, isLoading: restaurantLoading } = useRestaurant(id);
    const { data: subscriptions = [], isLoading: subscriptionsLoading } =
        useRestaurantSubscriptions(id);
    const { data: activeSubscription } = useActiveSubscription(id);
    const { data: packagesData } = usePackages({ per_page: 100 });
    const { mutate: lockRestaurant } = useLockRestaurant(id!);
    const { mutate: unlockRestaurant } = useUnlockRestaurant(id!);
    const { mutate: assignPackage, isPending: assigningPackage } =
        useAssignPackage(id!);
    const { mutate: cancelSubscription, isPending: cancelingSubscription } =
        useCancelSubscription();

    const isLoading = restaurantLoading || subscriptionsLoading;

    const handleLock = () => {
        Modal.confirm({
            title: 'Khóa Nhà Hàng',
            content: 'Bạn có chắc muốn khóa nhà hàng này không?',
            okText: 'Khóa',
            cancelText: 'Hủy',
            okButtonProps: { danger: true },
            onOk: () => {
                lockRestaurant(undefined, {
                    onSuccess: () => {
                        message.success('Khóa nhà hàng thành công');
                    },
                    onError: (error) => {
                        message.error(getErrorMessage(error));
                    },
                });
            },
        });
    };

    const handleUnlock = () => {
        Modal.confirm({
            title: 'Mở Khóa Nhà Hàng',
            content: 'Bạn có chắc muốn mở khóa nhà hàng này không?',
            okText: 'Mở Khóa',
            cancelText: 'Hủy',
            onOk: () => {
                unlockRestaurant(undefined, {
                    onSuccess: () => {
                        message.success('Mở khóa nhà hàng thành công');
                    },
                    onError: (error) => {
                        message.error(getErrorMessage(error));
                    },
                });
            },
        });
    };

    const handleAssignPackage = () => {
        if (!selectedPackageId) {
            message.error('Vui lòng chọn gói dịch vụ');
            return;
        }

        const data: AssignPackageRequest = {
            package_id: selectedPackageId,
        };

        assignPackage(data, {
            onSuccess: () => {
                message.success('Gán gói dịch vụ thành công');
                setIsAssignModalVisible(false);
                setSelectedPackageId('');
            },
            onError: (error) => {
                message.error(getErrorMessage(error));
            },
        });
    };

    const handleCancelSubscription = (subscription: Subscription) => {
        Modal.confirm({
            title: 'Hủy Đăng Ký',
            content: `Bạn có chắc muốn hủy gói dịch vụ "${subscription.package.name}" không?`,
            okText: 'Hủy',
            cancelText: 'Không',
            okButtonProps: { danger: true },
            onOk: () => {
                cancelSubscription(subscription.id, {
                    onSuccess: () => {
                        message.success('Hủy đăng ký thành công');
                    },
                    onError: (error) => {
                        message.error(getErrorMessage(error));
                    },
                });
            },
        });
    };

    if (isLoading) {
        return <Spin />;
    }

    if (!restaurant) {
        return <Alert message="Không tìm thấy nhà hàng" type="error" />;
    }

    const tabItems = [
        {
            key: 'overview',
            label: 'Tổng Quan',
            children: (
                <Descriptions bordered column={1} className="mt-4">
                    <Descriptions.Item label="ID">{restaurant.id}</Descriptions.Item>
                    <Descriptions.Item label="Tên">{restaurant.name}</Descriptions.Item>
                    <Descriptions.Item label="Địa Chỉ">
                        {restaurant.address || '—'}
                    </Descriptions.Item>
                    <Descriptions.Item label="Điện Thoại">
                        {restaurant.phone || '—'}
                    </Descriptions.Item>
                    <Descriptions.Item label="Trạng Thái">
                        <Tag color={restaurant.status === 'active' ? 'green' : 'red'}>
                            {restaurant.status_label}
                        </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Token Công Khai">
                        <code>{restaurant.public_order_token || '—'}</code>
                    </Descriptions.Item>
                    <Descriptions.Item label="Ngày Tạo">
                        {formatDate(restaurant.created_at)}
                    </Descriptions.Item>
                    <Descriptions.Item label="Cập Nhật">
                        {formatDate(restaurant.updated_at)}
                    </Descriptions.Item>
                </Descriptions>
            ),
        },
        {
            key: 'subscriptions',
            label: 'Đăng Ký Dịch Vụ',
            children: (
                <div className="mt-4">
                    {activeSubscription ? (
                        <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                            <h3 className="font-semibold mb-2">Gói Dịch Vụ Hiện Tại</h3>
                            <Descriptions size="small" column={2} bordered>
                                <Descriptions.Item label="Tên Gói">
                                    {activeSubscription.package.name}
                                </Descriptions.Item>
                                <Descriptions.Item label="Giá">
                                    {formatCurrency(activeSubscription.package.price)}
                                </Descriptions.Item>
                                <Descriptions.Item label="Ngày Bắt Đầu">
                                    {formatDate(activeSubscription.start_date)}
                                </Descriptions.Item>
                                <Descriptions.Item label="Ngày Kết Thúc">
                                    {formatDate(activeSubscription.end_date)}
                                </Descriptions.Item>
                                <Descriptions.Item label="Trạng Thái">
                                    <Tag
                                        color={
                                            isSubscriptionExpired(activeSubscription.end_date)
                                                ? 'red'
                                                : 'green'
                                        }
                                    >
                                        {isSubscriptionExpired(activeSubscription.end_date)
                                            ? 'Hết Hạn'
                                            : `Còn ${getDaysRemaining(activeSubscription.end_date)} ngày`}
                                    </Tag>
                                </Descriptions.Item>
                                <Descriptions.Item label="Tính Năng">
                                    <Space wrap>
                                        {activeSubscription.package.features.map((feature) => (
                                            <Tag key={feature.id}>{feature.name}</Tag>
                                        ))}
                                    </Space>
                                </Descriptions.Item>
                            </Descriptions>
                            <Button
                                danger
                                className="mt-4"
                                loading={cancelingSubscription}
                                onClick={() => handleCancelSubscription(activeSubscription)}
                            >
                                Hủy Đăng Ký
                            </Button>
                        </div>
                    ) : (
                        <Alert
                            message="Không có gói dịch vụ nào đang hoạt động"
                            type="warning"
                            className="mb-4"
                        />
                    )}

                    <Button
                        type="primary"
                        className="mb-4"
                        onClick={() => setIsAssignModalVisible(true)}
                    >
                        Gán Gói Dịch Vụ Mới
                    </Button>

                    <h3 className="font-semibold mb-2">Lịch Sử Đăng Ký</h3>
                    <Table
                        dataSource={subscriptions.map((s) => ({
                            ...s,
                            key: s.id,
                        }))}
                        columns={[
                            {
                                title: 'Gói',
                                dataIndex: ['package', 'name'],
                                key: 'package',
                            },
                            {
                                title: 'Trạng Thái',
                                dataIndex: 'status_label',
                                key: 'status',
                            },
                            {
                                title: 'Ngày Bắt Đầu',
                                dataIndex: 'start_date',
                                key: 'start_date',
                                render: (text) => formatDate(text),
                            },
                            {
                                title: 'Ngày Kết Thúc',
                                dataIndex: 'end_date',
                                key: 'end_date',
                                render: (text) => formatDate(text),
                            },
                        ]}
                        pagination={false}
                        size="small"
                    />
                </div>
            ),
        },
    ];

    return (
        <div>
            <Space className="mb-4">
                <Button
                    type="text"
                    icon={<ArrowLeftOutlined />}
                    onClick={() => navigate('/super-admin/restaurants')}
                >
                    Quay lại
                </Button>
            </Space>

            <Card
                title={restaurant.name}
                extra={
                    <Space>
                        <Button
                            icon={<EditOutlined />}
                            onClick={() => navigate(`/super-admin/restaurants/${id}/edit`)}
                        >
                            Sửa
                        </Button>
                        {restaurant.status === 'active' ? (
                            <Button
                                danger
                                icon={<LockOutlined />}
                                onClick={handleLock}
                            >
                                Khóa
                            </Button>
                        ) : (
                            <Button
                                icon={<UnlockOutlined />}
                                onClick={handleUnlock}
                            >
                                Mở Khóa
                            </Button>
                        )}
                    </Space>
                }
            >
                <Tabs items={tabItems} />
            </Card>

            <Modal
                title="Gán Gói Dịch Vụ"
                open={isAssignModalVisible}
                onOk={handleAssignPackage}
                onCancel={() => {
                    setIsAssignModalVisible(false);
                    setSelectedPackageId('');
                }}
                confirmLoading={assigningPackage}
            >
                <Select
                    placeholder="Chọn gói dịch vụ"
                    value={selectedPackageId || undefined}
                    onChange={setSelectedPackageId}
                    style={{ width: '100%' }}
                    options={
                        packagesData?.data?.map((pkg) => ({
                            label: `${pkg.name} - ${formatCurrency(pkg.price)}`,
                            value: pkg.id,
                        })) || []
                    }
                />
            </Modal>
        </div>
    );
};

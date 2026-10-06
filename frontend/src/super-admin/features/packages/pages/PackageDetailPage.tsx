import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    Card,
    Button,
    Space,
    Spin,
    Descriptions,
    Tag,
    Modal,
    Select,
    message,
    Alert,
    Table,
} from 'antd';
import { ArrowLeftOutlined, EditOutlined } from '@ant-design/icons';
import { usePackage } from '../services/package.hooks';
import { useSyncPackageFeatures } from '../services/package.hooks';
import { useFeatures } from '@/super-admin/features/feature-flags/services/feature.hooks';
import { formatDate, formatCurrency } from '@/lib/formatters';
import { getErrorMessage } from '@/lib/error-handlers';
import type { SyncPackageFeaturesRequest } from '@/types';

export const PackageDetailPage = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [isFeatureModalVisible, setIsFeatureModalVisible] = useState(false);
    const [selectedFeatureIds, setSelectedFeatureIds] = useState<string[]>([]);

    const { data: pkg, isLoading } = usePackage(id);
    const { data: featuresData } = useFeatures({ per_page: 100, is_active: true });
    const { mutate: syncFeatures, isPending: syncingFeatures } =
        useSyncPackageFeatures(id!);

    const handleSyncFeatures = () => {
        const data: SyncPackageFeaturesRequest = {
            feature_ids: selectedFeatureIds,
        };

        syncFeatures(data, {
            onSuccess: () => {
                message.success('Đồng bộ tính năng thành công');
                setIsFeatureModalVisible(false);
            },
            onError: (error) => {
                message.error(getErrorMessage(error));
            },
        });
    };

    const openFeatureModal = () => {
        setSelectedFeatureIds(pkg?.features.map((f) => f.id) || []);
        setIsFeatureModalVisible(true);
    };

    if (isLoading) {
        return <Spin />;
    }

    if (!pkg) {
        return <Alert message="Không tìm thấy gói dịch vụ" type="error" />;
    }

    const featureColumns = [
        {
            title: 'Mã',
            dataIndex: 'code',
            key: 'code',
            render: (text: string) => <code>{text}</code>,
        },
        {
            title: 'Tên',
            dataIndex: 'name',
            key: 'name',
        },
        {
            title: 'Mô Tả',
            dataIndex: 'description',
            key: 'description',
            render: (text: string) => text || '—',
        },
    ];

    return (
        <div>
            <Space className="mb-4">
                <Button
                    type="text"
                    icon={<ArrowLeftOutlined />}
                    onClick={() => navigate('/super-admin/packages')}
                >
                    Quay lại
                </Button>
            </Space>

            <Card
                title={pkg.name}
                extra={
                    <Space>
                        <Button
                            icon={<EditOutlined />}
                            onClick={() => navigate(`/super-admin/packages/${id}/edit`)}
                        >
                            Sửa
                        </Button>
                    </Space>
                }
            >
                <Descriptions bordered column={1} className="mb-6">
                    <Descriptions.Item label="ID">{pkg.id}</Descriptions.Item>
                    <Descriptions.Item label="Mã">{pkg.code}</Descriptions.Item>
                    <Descriptions.Item label="Giá">
                        {formatCurrency(pkg.price)}
                    </Descriptions.Item>
                    <Descriptions.Item label="Thời Hạn">
                        {pkg.duration_days} ngày
                    </Descriptions.Item>
                    <Descriptions.Item label="Mô Tả">
                        {pkg.description || '—'}
                    </Descriptions.Item>
                    <Descriptions.Item label="Trạng Thái">
                        <Tag color={pkg.is_active ? 'green' : 'red'}>
                            {pkg.is_active ? 'Hoạt động' : 'Tắt'}
                        </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Ngày Tạo">
                        {formatDate(pkg.created_at)}
                    </Descriptions.Item>
                    <Descriptions.Item label="Cập Nhật">
                        {formatDate(pkg.updated_at)}
                    </Descriptions.Item>
                </Descriptions>

                <Card title="Các Mốc Giá (Prices Tier)" className="mb-6">
                    <Table
                        dataSource={pkg.prices?.map((p) => ({ ...p, key: p.id })) || []}
                        columns={[
                            {
                                title: 'Thời Hạn',
                                dataIndex: 'duration_days',
                                key: 'duration_days',
                                render: (days) => `${days} ngày`,
                            },
                            {
                                title: 'Giá Bán',
                                dataIndex: 'price',
                                key: 'price',
                                render: (price) => formatCurrency(price),
                            },
                            {
                                title: 'Giá Gốc',
                                dataIndex: 'original_price',
                                key: 'original_price',
                                render: (price) => (price ? formatCurrency(price) : '—'),
                            },
                            {
                                title: 'Trạng Thái',
                                dataIndex: 'is_active',
                                key: 'is_active',
                                render: (isActive: boolean) => (
                                    <Tag color={isActive ? 'green' : 'red'}>
                                        {isActive ? 'Hoạt động' : 'Tắt'}
                                    </Tag>
                                ),
                            },
                        ]}
                        pagination={false}
                        size="small"
                        locale={{ emptyText: 'Chưa cấu hình mốc giá nào (Đang dùng giá mặc định)' }}
                    />
                </Card>

                <Card title="Tính Năng" className="mb-6">
                    <Button
                        type="primary"
                        className="mb-4"
                        onClick={openFeatureModal}
                    >
                        Cập Nhật Tính Năng
                    </Button>

                    <Table
                        dataSource={pkg.features.map((f) => ({ ...f, key: f.id }))}
                        columns={featureColumns}
                        pagination={false}
                        size="small"
                    />
                </Card>
            </Card>

            <Modal
                title="Cập Nhật Tính Năng"
                open={isFeatureModalVisible}
                onOk={handleSyncFeatures}
                onCancel={() => setIsFeatureModalVisible(false)}
                confirmLoading={syncingFeatures}
            >
                <Select
                    mode="multiple"
                    placeholder="Chọn tính năng"
                    value={selectedFeatureIds}
                    onChange={setSelectedFeatureIds}
                    style={{ width: '100%' }}
                    options={
                        featuresData?.data?.map((f) => ({
                            label: f.name,
                            value: f.id,
                        })) || []
                    }
                />
            </Modal>
        </div>
    );
};

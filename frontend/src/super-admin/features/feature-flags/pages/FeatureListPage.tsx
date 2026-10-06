import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Card,
    Table,
    Button,
    Space,
    Input,
    Switch,
    Row,
    Col,
    message,
    Tooltip,
} from 'antd';
import { PlusOutlined, EditOutlined } from '@ant-design/icons';
import { useFeatures, useToggleFeature } from '../services/feature.hooks';
import { formatDate } from '@/lib/formatters';
import { getErrorMessage } from '@/lib/error-handlers';
import { LoadingState } from '@/lib/loading-state';
import type { Feature, FeatureListParams } from '@/types';

const { Search } = Input;

export const FeatureListPage = () => {
    const navigate = useNavigate();
    const [params, setParams] = useState<FeatureListParams>({
        per_page: 50,
        search: '',
    });

    const { data, isLoading, error } = useFeatures(params);
    const { mutate: toggleFeature } = useToggleFeature();

    const features = data?.data || [];
    const pagination = data?.meta;

    const handleSearch = (value: string) => {
        setParams((prev) => ({ ...prev, search: value, page: 1 }));
    };

    const handleToggleActive = (record: Feature) => {
        toggleFeature(record.id, {
            onSuccess: () => {
                message.success(
                    `Tính năng đã ${record.is_active ? 'tắt' : 'bật'}`
                );
            },
            onError: (error) => {
                message.error(getErrorMessage(error));
            },
        });
    };

    const columns = [
        {
            title: 'Mã',
            dataIndex: 'code',
            key: 'code',
            width: 150,
            render: (text: string) => <code>{text}</code>,
        },
        {
            title: 'Tên',
            dataIndex: 'name',
            key: 'name',
            width: 200,
        },
        {
            title: 'Mô Tả',
            dataIndex: 'description',
            key: 'description',
            render: (text: string) => text || '—',
            width: 250,
        },
        {
            title: 'Trạng Thái',
            dataIndex: 'is_active',
            key: 'is_active',
            width: 120,
            render: (isActive: boolean, record: Feature) => (
                <Switch
                    checked={isActive}
                    onChange={() => handleToggleActive(record)}
                />
            ),
        },
        {
            title: 'Ngày Tạo',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (text: string) => formatDate(text),
            width: 120,
        },
        {
            title: 'Hành Động',
            key: 'actions',
            fixed: 'right' as const,
            width: 100,
            render: (_: unknown, record: Feature) => (
                <Space size="small">
                    <Tooltip title="Sửa">
                        <Button
                            type="text"
                            size="small"
                            icon={<EditOutlined />}
                            onClick={() => navigate(`/super-admin/features/${record.id}/edit`)}
                        />
                    </Tooltip>
                </Space>
            ),
        },
    ];

    return (
        <Card
            title="Quản Lý Tính Năng"
            extra={
                <Button
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={() => navigate('/super-admin/features/new')}
                >
                    Thêm Mới
                </Button>
            }
        >
            <Row gutter={[16, 16]} className="mb-4">
                <Col xs={24} sm={12}>
                    <Search
                        placeholder="Tìm kiếm theo tên hoặc mã..."
                        onSearch={handleSearch}
                        allowClear
                    />
                </Col>
            </Row>

            <LoadingState
                isLoading={isLoading}
                isEmpty={features.length === 0}
                error={error}
                emptyMessage="Không có tính năng nào"
            >
                <Table
                    dataSource={features.map((f) => ({ ...f, key: f.id }))}
                    columns={columns}
                    pagination={{
                        current: pagination?.current_page || 1,
                        pageSize: pagination?.per_page || 50,
                        total: pagination?.total || 0,
                        onChange: (page) => {
                            setParams((prev) => ({ ...prev, page }));
                        },
                    }}
                    scroll={{ x: 1000 }}
                />
            </LoadingState>
        </Card>
    );
};

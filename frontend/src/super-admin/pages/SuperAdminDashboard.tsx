import { useMemo } from 'react';
import { Card, Row, Col, Statistic, Table, Spin } from 'antd';
import {
    ShoppingOutlined,
    AppstoreOutlined,
    ProductOutlined,
    CheckCircleOutlined,
    CloseCircleOutlined,
} from '@ant-design/icons';
import { useRestaurants } from '@/super-admin/features/restaurants/services/restaurant.hooks';
import { useFeatures } from '@/super-admin/features/feature-flags/services/feature.hooks';
import { usePackages } from '@/super-admin/features/packages/services/package.hooks';
import { formatCurrency } from '@/lib/formatters';

export const SuperAdminDashboard = () => {
    const { data: restaurantsData, isLoading: restaurantsLoading } =
        useRestaurants({
            per_page: 100,
        });
    const { data: featuresData, isLoading: featuresLoading } = useFeatures({
        per_page: 100,
    });
    const { data: packagesData, isLoading: packagesLoading } = usePackages({
        per_page: 100,
    });

    const stats = useMemo(() => {
        const restaurants = restaurantsData?.data || [];
        const features = featuresData?.data || [];
        const packages = packagesData?.data || [];

        const activeRestaurants = restaurants.filter(
            (r) => r.status !== 'locked' && r.status !== 'suspended'
        );
        const lockedRestaurants = restaurants.filter(
            (r) => r.status === 'locked' || r.status === 'suspended'
        );

        return {
            totalRestaurants: restaurants.length,
            activeRestaurants: activeRestaurants.length,
            lockedRestaurants: lockedRestaurants.length,
            totalFeatures: features.length,
            activeFeatures: features.filter((f) => f.is_active).length,
            totalPackages: packages.length,
            activePackages: packages.filter((p) => p.is_active).length,
            restaurants,
            packages,
        };
    }, [restaurantsData, featuresData, packagesData]);

    const isLoading = restaurantsLoading || featuresLoading || packagesLoading;

    if (isLoading) {
        return (
            <div className="flex justify-center items-center py-20">
                <Spin />
            </div>
        );
    }

    return (
        <div>
            <h1 className="text-3xl font-bold mb-6">Dashboard</h1>

            {/* Stats Cards */}
            <Row gutter={[16, 16]} className="mb-6">
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Tổng Nhà Hàng"
                            value={stats.totalRestaurants}
                            prefix={<ShoppingOutlined />}
                            valueStyle={{ color: '#1890ff' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Nhà Hàng Hoạt Động"
                            value={stats.activeRestaurants}
                            prefix={<CheckCircleOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Nhà Hàng Bị Khóa"
                            value={stats.lockedRestaurants}
                            prefix={<CloseCircleOutlined />}
                            valueStyle={{ color: '#ff4d4f' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Tổng Tính Năng"
                            value={stats.totalFeatures}
                            prefix={<AppstoreOutlined />}
                            valueStyle={{ color: '#1890ff' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Tính Năng Hoạt Động"
                            value={stats.activeFeatures}
                            prefix={<CheckCircleOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Tổng Gói Dịch Vụ"
                            value={stats.totalPackages}
                            prefix={<ProductOutlined />}
                            valueStyle={{ color: '#1890ff' }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Gói Dịch Vụ Hoạt Động"
                            value={stats.activePackages}
                            prefix={<CheckCircleOutlined />}
                            valueStyle={{ color: '#52c41a' }}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Recent Restaurants */}
            <Row gutter={[16, 16]}>
                <Col xs={24} lg={12}>
                    <Card title="Nhà Hàng Mới Nhất" className="h-full">
                        <Table
                            dataSource={stats.restaurants.slice(0, 5).map((r) => ({
                                ...r,
                                key: r.id,
                            }))}
                            columns={[
                                {
                                    title: 'Tên',
                                    dataIndex: 'name',
                                    key: 'name',
                                },
                                {
                                    title: 'Trạng Thái',
                                    dataIndex: 'status_label',
                                    key: 'status_label',
                                },
                            ]}
                            pagination={false}
                            size="small"
                        />
                    </Card>
                </Col>

                {/* Recent Packages */}
                <Col xs={24} lg={12}>
                    <Card title="Gói Dịch Vụ" className="h-full">
                        <Table
                            dataSource={stats.packages.slice(0, 5).map((p) => ({
                                ...p,
                                key: p.id,
                            }))}
                            columns={[
                                {
                                    title: 'Tên',
                                    dataIndex: 'name',
                                    key: 'name',
                                },
                                {
                                    title: 'Giá',
                                    dataIndex: 'price',
                                    key: 'price',
                                    render: (price) => formatCurrency(price),
                                },
                            ]}
                            pagination={false}
                            size="small"
                        />
                    </Card>
                </Col>
            </Row>
        </div>
    );
};

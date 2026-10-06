import { useState } from 'react';
import { Typography, Row, Col, Alert, Card, Button, Tabs } from 'antd';
import { useNavigate } from 'react-router-dom';
import { OrderedListOutlined, SmileOutlined } from '@ant-design/icons';
import { useAuthStore } from '@/store/auth.store';
import { UserRole } from '@/types/auth';
import { useAnalytics } from '../features/analytics/hooks/useAnalytics';
import { PeriodSelector } from '../features/analytics/components/PeriodSelector';
import { OverviewCards } from '../features/analytics/components/OverviewCards';
import { RevenueChart } from '../features/analytics/components/RevenueChart';
import { PaymentBreakdown } from '../features/analytics/components/PaymentBreakdown';
import { TopItemsTable } from '../features/analytics/components/TopItemsTable';
import type { AnalyticsParams } from '../features/analytics/types/analytics.types';
import { TransactionsTab } from '../features/analytics/components/TransactionsTab';

const { Title } = Typography;

// ── Trang chào cho nhân viên không có quyền xem analytics ──────────────────
const StaffWelcome = ({ name, roleLabel }: { name: string; roleLabel: string }) => {
    const navigate = useNavigate();
    return (
        <div className="flex flex-col items-center justify-center min-h-[60vh] gap-6">
            <SmileOutlined style={{ fontSize: 64, color: '#10b981' }} />
            <div className="text-center">
                <Title level={2} className="!mb-1">Xin chào, {name}!</Title>
                <p className="text-gray-500 text-base">
                    Bạn đang đăng nhập với vai trò <span className="font-semibold text-emerald-600">{roleLabel}</span>.
                </p>
            </div>
            <Card className="shadow-sm w-full max-w-sm text-center">
                <p className="text-gray-500 mb-4">Truy cập nhanh vào màn hình làm việc của bạn:</p>
                <Button
                    type="primary"
                    size="large"
                    icon={<OrderedListOutlined />}
                    onClick={() => navigate('/portal/orders')}
                    style={{ backgroundColor: '#10b981', borderColor: '#10b981' }}
                >
                    Vào màn hình Đơn hàng
                </Button>
            </Card>
        </div>
    );
};

// ── Dashboard analytics cho OWNER / MANAGER ─────────────────────────────────
const AnalyticsDashboard = ({ name }: { name: string }) => {
    const [params, setParams] = useState<AnalyticsParams>({ period: 'today' });
    const { data, isLoading, isError, error } = useAnalytics(params);

    const overviewTab = (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <Title level={2} className="!mb-0">Tổng quan Nhà hàng</Title>
                    <p className="text-gray-500 mt-1">
                        Xin chào <span className="font-semibold">{name}</span>, đây là tình hình kinh doanh của bạn.
                    </p>
                </div>
                <PeriodSelector params={params} onChange={setParams} />
            </div>

            {isError && (
                <Alert
                    type="error"
                    message="Không thể tải dữ liệu thống kê"
                    description={(error as any)?.message ?? 'Vui lòng thử lại sau.'}
                    showIcon
                />
            )}

            <OverviewCards data={data?.overview} loading={isLoading} />
            <RevenueChart data={data?.chart_data} loading={isLoading} period={params.period} />

            <Row gutter={[16, 16]}>
                <Col xs={24} lg={10}>
                    <PaymentBreakdown data={data?.by_payment_method} loading={isLoading} />
                </Col>
                <Col xs={24} lg={14}>
                    <TopItemsTable data={data?.top_items} loading={isLoading} />
                </Col>
            </Row>
        </div>
    );

    return (
        <Tabs
            defaultActiveKey="overview"
            size="large"
            items={[
                {
                    key: 'overview',
                    label: '📊 Tổng quan',
                    children: overviewTab,
                },
                {
                    key: 'transactions',
                    label: '🧾 Giao dịch',
                    children: <TransactionsTab />,
                },
            ]}
        />
    );
};

// ── Trang gốc: phân nhánh theo role ─────────────────────────────────────────
export const TenantDashboard = () => {
    const user = useAuthStore((state) => state.user);
    const userRole = user?.role as UserRole;

    const isOwnerOrManager = userRole === UserRole.OWNER || userRole === UserRole.MANAGER;

    if (!isOwnerOrManager) {
        return <StaffWelcome name={user?.name ?? ''} roleLabel={user?.role_label ?? 'Nhân viên'} />;
    }

    return <AnalyticsDashboard name={user?.name ?? ''} />;
};

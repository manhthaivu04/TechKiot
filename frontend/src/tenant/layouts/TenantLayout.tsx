import { useState, useEffect } from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { Layout, Menu, Dropdown, Avatar, Space, Button, Badge } from 'antd';
import {
    DashboardOutlined,
    ShopOutlined,
    TableOutlined,
    SettingOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    OrderedListOutlined,
    TeamOutlined,
    DatabaseOutlined,
    BarChartOutlined,
} from '@ant-design/icons';
import { useAuthStore } from '@/store/auth.store';
import { useLogout } from '@/auth/services/auth.hooks';
import { useFeatureFlag } from '@/auth/hooks/useFeatureFlag';
import { FeatureCode } from '@/types';
import { UserRole } from '@/types/auth';
import type { MenuProps } from 'antd';
import { useOrderRealtimeListener } from '@/tenant/features/orders/services/useOrderRealtimeListener';
import { useTenantOrdersSync } from '@/tenant/features/orders/hooks/useTenantOrdersSync';
import { useNotificationStore } from '@/store/useNotificationStore';

const { Header, Sider, Content } = Layout;

export const TenantLayout = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const [collapsed, setCollapsed] = useState(false);
    const user = useAuthStore((state) => state.user);
    const { mutate: logout } = useLogout();

    // ── Realtime: lấy restaurantId từ user profile ────────────────────────
    const restaurantId = (user as { restaurant_id?: string } | null)?.restaurant_id;

    // ── Notification badge state ──────────────────────────────────────────
    const { newOrderBadge, resetBadge } = useNotificationStore();

    // ── Kích hoạt realtime listener toàn app ─────────────────────────────
    useOrderRealtimeListener({
        restaurantId,
        enabled: !!restaurantId,
    });
    useTenantOrdersSync(restaurantId);

    // ── Reset badge khi người dùng vào trang danh sách đơn hàng ──────────
    useEffect(() => {
        if (location.pathname.startsWith('/portal/orders')) {
            resetBadge();
        }
    }, [location.pathname, resetBadge]);

    const hasMenuManagement = useFeatureFlag(FeatureCode.MENU_MANAGEMENT);
    const hasTableManagement = useFeatureFlag(FeatureCode.TABLE_MANAGEMENT);
    const hasStaffManagement = useFeatureFlag(FeatureCode.STAFF_MANAGEMENT);
    const hasPosQuickOrder = useFeatureFlag(FeatureCode.POS_QUICK_ORDER);
    const hasInventoryManagement = useFeatureFlag(FeatureCode.INVENTORY_MANAGEMENT);
    const hasQrStaticOrder = useFeatureFlag(FeatureCode.QR_STATIC_ORDER);

    const userRole = user?.role as UserRole;
    const isOwner = userRole === UserRole.OWNER;
    const isOwnerOrManager = isOwner || userRole === UserRole.MANAGER;

    const menuItems: MenuProps['items'] = [
        {
            key: '/portal',
            icon: <DashboardOutlined />,
            label: 'Tổng quan',
            onClick: () => navigate('/portal'),
        },
        hasMenuManagement && isOwnerOrManager ? {
            key: '/portal/menu',
            icon: <ShopOutlined />,
            label: 'Thực đơn',
            onClick: () => navigate('/portal/menu'),
        } : null,
        hasPosQuickOrder ? {
            key: '/portal/orders',
            // Hiển thị badge đỏ khi có đơn hàng mới chưa xem
            icon: (
                <Badge
                    count={newOrderBadge}
                    size="small"
                    offset={[6, -2]}
                    style={{ backgroundColor: '#ef4444' }}
                >
                    <OrderedListOutlined />
                </Badge>
            ),
            label: (
                <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                    Đơn hàng
                    {newOrderBadge > 0 && !collapsed && (
                        <span
                            style={{
                                background: '#ef4444',
                                color: '#fff',
                                borderRadius: 10,
                                padding: '0 6px',
                                fontSize: 11,
                                fontWeight: 700,
                                lineHeight: '18px',
                                minWidth: 18,
                                textAlign: 'center',
                                display: 'inline-block',
                            }}
                        >
                            {newOrderBadge > 99 ? '99+' : newOrderBadge}
                        </span>
                    )}
                </span>
            ),
            onClick: () => navigate('/portal/orders'),
        } : null,
        hasTableManagement && isOwner ? {
            key: '/portal/tables-group',
            icon: <TableOutlined />,
            label: 'Quản lý bàn',
            children: [
                {
                    key: '/portal/table-areas',
                    label: 'Khu vực bàn',
                    onClick: () => navigate('/portal/table-areas'),
                },
                {
                    key: '/portal/restaurant-tables',
                    label: 'Danh sách bàn',
                    onClick: () => navigate('/portal/restaurant-tables'),
                },
            ],
        } : null,
        hasStaffManagement && isOwnerOrManager ? {
            key: '/portal/staff',
            icon: <TeamOutlined />,
            label: 'Nhân viên',
            onClick: () => navigate('/portal/staff'),
        } : null,
        hasInventoryManagement && isOwnerOrManager ? {
            key: '/portal/inventory-group',
            icon: <DatabaseOutlined />,
            label: 'Quản lý Kho',
            children: [
                {
                    key: '/portal/inventory',
                    icon: <BarChartOutlined />,
                    label: 'Tồn kho',
                    onClick: () => navigate('/portal/inventory'),
                },
                {
                    key: '/portal/warehouses',
                    label: 'Kho chứa',
                    onClick: () => navigate('/portal/warehouses'),
                },
                {
                    key: '/portal/stock-documents',
                    label: 'Chứng từ kho',
                    onClick: () => navigate('/portal/stock-documents'),
                },
            ],
        } : null,
        isOwnerOrManager ? {
            key: '/portal/settings-group',
            icon: <SettingOutlined />,
            label: 'Cài đặt',
            children: [
                {
                    key: '/portal/settings',
                    label: 'Cấu hình thanh toán',
                    onClick: () => navigate('/portal/settings'),
                },
                hasQrStaticOrder ? {
                    key: '/portal/settings/qr-static',
                    label: 'QR Menu Tĩnh',
                    onClick: () => navigate('/portal/settings/qr-static'),
                } : null,
            ].filter(Boolean) as MenuProps['items'],
        } : null,
    ].filter(Boolean) as MenuProps['items'];

    const userMenuItems: MenuProps['items'] = [
        {
            key: 'logout',
            icon: <LogoutOutlined />,
            label: 'Đăng xuất',
            onClick: () => logout(),
        },
    ];

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Sider
                breakpoint="lg"
                collapsedWidth="0"
                onBreakpoint={(broken) => {
                    if (broken) {
                        setCollapsed(true);
                    }
                }}
                trigger={null}
                collapsible
                collapsed={collapsed}
                width={250}
                style={{ backgroundColor: '#064e3b', zIndex: 1000 }} // Emerald 900
            >
                <div
                    className="p-4 text-white text-lg font-bold"
                    style={{ height: '64px', display: 'flex', alignItems: 'center', backgroundColor: '#022c22' }} // Emerald 950
                >
                    {collapsed ? 'KT' : 'Portal Quản Lý'}
                </div>
                <Menu
                    theme="dark"
                    mode="inline"
                    items={menuItems}
                    selectedKeys={[location.pathname]}
                    style={{ backgroundColor: '#064e3b' }}
                />
            </Sider>

            <Layout>
                <Header
                    style={{
                        background: '#fff',
                        paddingLeft: 16,
                        boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                    }}
                >
                    <Button
                        type="text"
                        icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
                        onClick={() => setCollapsed(!collapsed)}
                        size="large"
                    />

                    <Dropdown menu={{ items: userMenuItems }} trigger={['click']}>
                        <Space style={{ cursor: 'pointer' }}>
                            <Avatar size="large" style={{ backgroundColor: '#10b981' }}>
                                {user?.name.charAt(0).toUpperCase()}
                            </Avatar>
                            <div className="hidden sm:block">
                                <div className="font-medium text-gray-800">{user?.name}</div>
                                <div className="text-xs text-gray-500">{user?.role_label || 'Quản lý'}</div>
                            </div>
                        </Space>
                    </Dropdown>
                </Header>

                <Content
                    style={{
                        margin: location.pathname === '/portal/orders/takeaway' ? 0 : '16px',
                        padding: location.pathname === '/portal/orders/takeaway' ? 0 : '24px',
                        background: '#f0f2f5',
                        borderRadius: location.pathname === '/portal/orders/takeaway' ? 0 : '8px',
                        overflow: 'auto',
                    }}
                >
                    <Outlet />
                </Content>
            </Layout>
        </Layout>
    );
};

import { useState } from 'react';
import { Outlet, useNavigate } from 'react-router-dom';
import { Layout, Menu, Dropdown, Avatar, Space, Button } from 'antd';
import {
    DashboardOutlined,
    ShoppingOutlined,
    AppstoreOutlined,
    ProductOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
} from '@ant-design/icons';
import { useAuthStore } from '@/store/auth.store';
import { useLogout } from '@/auth/services/auth.hooks';
import type { MenuProps } from 'antd';

const { Header, Sider, Content } = Layout;

export const SuperAdminLayout = () => {
    const navigate = useNavigate();
    const [collapsed, setCollapsed] = useState(false);
    const user = useAuthStore((state) => state.user);
    const { mutate: logout } = useLogout();

    const menuItems: MenuProps['items'] = [
        {
            key: '/super-admin',
            icon: <DashboardOutlined />,
            label: 'Dashboard',
            onClick: () => navigate('/super-admin'),
        },
        {
            key: '/super-admin/restaurants',
            icon: <ShoppingOutlined />,
            label: 'Nhà hàng',
            onClick: () => navigate('/super-admin/restaurants'),
        },
        {
            key: '/super-admin/features',
            icon: <AppstoreOutlined />,
            label: 'Tính năng',
            onClick: () => navigate('/super-admin/features'),
        },
        {
            key: '/super-admin/packages',
            icon: <ProductOutlined />,
            label: 'Gói dịch vụ',
            onClick: () => navigate('/super-admin/packages'),
        },
    ];

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
                style={{ backgroundColor: '#1e1b4b', zIndex: 1000 }}
            >
                <div
                    className="p-4 text-white text-lg font-bold"
                    style={{ height: '64px', display: 'flex', alignItems: 'center', backgroundColor: '#312e81' }}
                >
                    {collapsed ? 'SA' : 'Super Admin'}
                </div>
                <Menu
                    theme="dark"
                    mode="inline"
                    items={menuItems}
                    defaultSelectedKeys={['/super-admin']}
                    style={{ backgroundColor: '#1e1b4b' }}
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
                            <Avatar size="large" style={{ backgroundColor: '#1890ff' }}>
                                {user?.name.charAt(0).toUpperCase()}
                            </Avatar>
                            <div className="hidden sm:block">
                                <div className="font-medium text-gray-800">{user?.name}</div>
                                <div className="text-xs text-gray-500">{user?.email}</div>
                            </div>
                        </Space>
                    </Dropdown>
                </Header>

                <Content
                    style={{
                        margin: '16px',
                        padding: '24px',
                        background: '#f0f2f5',
                        borderRadius: '8px',
                        overflow: 'auto',
                    }}
                >
                    <Outlet />
                </Content>
            </Layout>
        </Layout>
    );
};

import { Layout } from 'antd';
import { Outlet } from 'react-router-dom';

const { Content } = Layout;

export const PublicLayout = () => {
    return (
        <Layout style={{ minHeight: '100vh' }}>
            <Content style={{ padding: 24 }}>
                <Outlet />
            </Content>
        </Layout>
    );
};


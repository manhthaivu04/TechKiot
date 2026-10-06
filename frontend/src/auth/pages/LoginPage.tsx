import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Form, Input, Button, Card, Alert, Spin } from 'antd';
import { LockOutlined, MailOutlined } from '@ant-design/icons';
import { useLogin } from '../services/auth.hooks';
import { useAuthStore } from '@/store/auth.store';
import { getErrorMessage } from '@/lib/error-handlers';
import type { LoginRequest } from '@/types';

export const LoginPage = () => {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const { mutate: login, isPending, error } = useLogin();
    const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
    const user = useAuthStore((state) => state.user);

    useEffect(() => {
        if (isAuthenticated && user) {
            if (user.role === 'SUPER_ADMIN') {
                navigate('/super-admin');
            } else if (['OWNER', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER'].includes(user.role)) {
                navigate('/portal');
            } else {
                navigate('/');
            }
        }
    }, [isAuthenticated, user, navigate]);

    const handleSubmit = (values: LoginRequest) => {
        login(values, {
            onSuccess: (response) => {
                const userRole = response.user.role;
                if (userRole === 'SUPER_ADMIN') {
                    navigate('/super-admin');
                } else if (['OWNER', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER'].includes(userRole)) {
                    navigate('/portal');
                } else {
                    navigate('/');
                }
            },
        });
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
            <Card className="w-full max-w-md shadow-lg">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-center text-gray-800">
                        KiotTay Login
                    </h1>
                    <p className="text-center text-gray-600 mt-2">
                        Đăng nhập vào hệ thống quản lý
                    </p>
                </div>

                {error && (
                    <Alert
                        message="Đăng nhập thất bại"
                        description={getErrorMessage(error)}
                        type="error"
                        showIcon
                        closable
                        className="mb-4"
                    />
                )}

                <Spin spinning={isPending}>
                    <Form form={form} onFinish={handleSubmit} layout="vertical">
                        <Form.Item
                            name="email"
                            rules={[
                                { required: true, message: 'Vui lòng nhập email' },
                                {
                                    type: 'email',
                                    message: 'Email không hợp lệ',
                                },
                            ]}
                        >
                            <Input
                                prefix={<MailOutlined />}
                                placeholder="Email"
                                disabled={isPending}
                            />
                        </Form.Item>

                        <Form.Item
                            name="password"
                            rules={[{ required: true, message: 'Vui lòng nhập mật khẩu' }]}
                        >
                            <Input.Password
                                prefix={<LockOutlined />}
                                placeholder="Mật khẩu"
                                disabled={isPending}
                            />
                        </Form.Item>

                        <Form.Item>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={isPending}
                                block
                                size="large"
                            >
                                Đăng nhập
                            </Button>
                        </Form.Item>
                    </Form>
                </Spin>

                <p className="text-center text-sm text-gray-600 mt-4">
                    Demo account: admin@kiottay.local / password
                </p>
            </Card>
        </div>
    );
};

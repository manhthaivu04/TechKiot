import { useNavigate } from 'react-router-dom';
import { Card, Form, Input, Button, Space, message, Select, Typography, Divider, Row, Col } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { useCreateRestaurant } from '../services/restaurant.hooks';
import { usePackages } from '@/super-admin/features/packages/services/package.hooks';
import { getErrorMessage, getFieldErrors } from '@/lib/error-handlers';
import type { RestaurantOnboardRequest } from '@/types';
import { formatCurrency } from '@/lib/formatters';

const { Title, Text } = Typography;

export const RestaurantCreatePage = () => {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const { mutateAsync: createRestaurantAsync, isPending } = useCreateRestaurant();
    
    // Fetch packages for the dropdown
    const { data: packagesData, isLoading: packagesLoading } = usePackages({
        per_page: 100, // fetch all active packages
        is_active: 1, // only active packages
    });

    const packages = packagesData?.data || [];

    const handleSubmit = async (values: Record<string, unknown>) => {
        // Format the data to match RestaurantOnboardRequest
        const payload: RestaurantOnboardRequest = {
            restaurant: {
                name: values.restaurant_name as string,
                address: values.restaurant_address as string,
                phone: values.restaurant_phone as string,
            },
            package_id: values.package_id as string,
            owner: {
                name: values.owner_name as string,
                email: values.owner_email as string,
                password: values.owner_password as string,
                is_active: true,
            }
        };

        try {
            await createRestaurantAsync(payload);
            message.success('Đăng ký nhà hàng và cấp phát gói thành công');
            window.location.href = '/super-admin/restaurants';
        } catch (error: any) {
            const fieldErrors = getFieldErrors(error);
            if (fieldErrors) {
                // Map nested errors to flat form field names if necessary
                const errorFields = Object.entries(fieldErrors).map(([field, errors]) => {
                    let formField = field;
                    if (field === 'restaurant.name') formField = 'restaurant_name';
                    if (field === 'restaurant.address') formField = 'restaurant_address';
                    if (field === 'restaurant.phone') formField = 'restaurant_phone';
                    if (field === 'owner.name') formField = 'owner_name';
                    if (field === 'owner.email') formField = 'owner_email';
                    if (field === 'owner.password') formField = 'owner_password';
                    if (field === 'package_id') formField = 'package_id';
                    
                    return {
                        name: formField,
                        errors: errors,
                    };
                });
                form.setFields(errorFields);
            } else {
                message.error(getErrorMessage(error));
            }
        }
    };

    return (
        <div className="max-w-4xl mx-auto">
            <Space className="mb-4">
                <Button
                    type="text"
                    icon={<ArrowLeftOutlined />}
                    onClick={() => navigate('/super-admin/restaurants')}
                >
                    Quay lại
                </Button>
            </Space>

            <Card title={<Title level={4} className="!m-0">Thêm Mới Nhà Hàng (Onboard)</Title>}>
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    autoComplete="off"
                >
                    <Row gutter={24}>
                        {/* Cột trái: Thông tin nhà hàng và Chủ cửa hàng */}
                        <Col xs={24} md={14}>
                            <Title level={5} className="mb-4">Thông Tin Nhà Hàng</Title>
                            <Form.Item
                                label="Tên Nhà Hàng"
                                name="restaurant_name"
                                rules={[
                                    { required: true, message: 'Vui lòng nhập tên nhà hàng' },
                                    { min: 2, message: 'Tên phải có ít nhất 2 ký tự' },
                                ]}
                            >
                                <Input placeholder="Ví dụ: KiotTay Quán" size="large" />
                            </Form.Item>

                            <Row gutter={16}>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Số Điện Thoại"
                                        name="restaurant_phone"
                                    >
                                        <Input placeholder="Nhập số điện thoại nhà hàng" size="large" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Địa Chỉ"
                                        name="restaurant_address"
                                    >
                                        <Input placeholder="Nhập địa chỉ nhà hàng" size="large" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Divider />

                            <Title level={5} className="mb-4">Thông Tin Tài Khoản Chủ Quản (Owner)</Title>
                            <Form.Item
                                label="Họ Tên Chủ Quản"
                                name="owner_name"
                                rules={[
                                    { required: true, message: 'Vui lòng nhập họ tên chủ quản' },
                                ]}
                            >
                                <Input placeholder="Nhập họ tên" size="large" />
                            </Form.Item>

                            <Row gutter={16}>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Email Đăng Nhập"
                                        name="owner_email"
                                        rules={[
                                            { required: true, message: 'Vui lòng nhập email' },
                                            { type: 'email', message: 'Email không hợp lệ' },
                                        ]}
                                    >
                                        <Input placeholder="admin@nhahang.com" size="large" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} sm={12}>
                                    <Form.Item
                                        label="Mật Khẩu"
                                        name="owner_password"
                                        rules={[
                                            { required: true, message: 'Vui lòng nhập mật khẩu' },
                                            { min: 6, message: 'Mật khẩu phải từ 6 ký tự' },
                                        ]}
                                    >
                                        <Input.Password placeholder="Nhập mật khẩu" size="large" />
                                    </Form.Item>
                                </Col>
                            </Row>
                        </Col>

                        {/* Cột phải: Chọn gói dịch vụ */}
                        <Col xs={24} md={10}>
                            <div className="bg-gray-50 p-4 rounded-lg border border-gray-200 h-full">
                                <Title level={5} className="mb-4">Gói Dịch Vụ Mặc Định</Title>
                                <Text className="text-gray-500 block mb-4">
                                    Chọn gói dịch vụ để kích hoạt ngay cho nhà hàng này. Hệ thống sẽ tự động tạo gói thuê bao (subscription) tương ứng.
                                </Text>

                                <Form.Item
                                    name="package_id"
                                    rules={[
                                        { required: true, message: 'Vui lòng chọn gói dịch vụ' },
                                    ]}
                                >
                                    <Select 
                                        placeholder="-- Chọn Gói --" 
                                        size="large"
                                        loading={packagesLoading}
                                        optionLabelProp="label"
                                    >
                                        {packages.map((pkg) => (
                                            <Select.Option key={pkg.id} value={pkg.id} label={pkg.name}>
                                                <div className="flex justify-between items-center w-full">
                                                    <span className="font-medium">{pkg.name}</span>
                                                    <span className="text-orange-600 font-bold">{formatCurrency(pkg.price)}</span>
                                                </div>
                                            </Select.Option>
                                        ))}
                                    </Select>
                                </Form.Item>

                                <div className="mt-8">
                                    <Button 
                                        type="primary" 
                                        htmlType="submit" 
                                        loading={isPending}
                                        size="large"
                                        block
                                    >
                                        Tạo Nhà Hàng & Cấp Gói
                                    </Button>
                                    <Button 
                                        onClick={() => navigate('/super-admin/restaurants')}
                                        size="large"
                                        block
                                        className="mt-2"
                                    >
                                        Quay Lại Danh Sách
                                    </Button>
                                </div>
                            </div>
                        </Col>
                    </Row>
                </Form>
            </Card>
        </div>
    );
};

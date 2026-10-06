import { useNavigate, useParams } from 'react-router-dom';
import { Card, Form, Input, Button, Space, message, Spin } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { useRestaurant, useUpdateRestaurant } from '../services/restaurant.hooks';
import { getErrorMessage, getFieldErrors } from '@/lib/error-handlers';
import type { UpdateRestaurantRequest } from '@/types';

export const RestaurantEditPage = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const { data: restaurant, isLoading } = useRestaurant(id);
    const { mutate: updateRestaurant, isPending } = useUpdateRestaurant(id!);

    const handleSubmit = (values: UpdateRestaurantRequest) => {
        updateRestaurant(values, {
            onSuccess: () => {
                message.success('Cập nhật nhà hàng thành công');
                navigate(`/super-admin/restaurants/${id}`);
            },
            onError: (error) => {
                const fieldErrors = getFieldErrors(error);
                if (fieldErrors) {
                    const errorFields = Object.entries(fieldErrors).map(([field, errors]) => ({
                        name: field,
                        errors: errors,
                    }));
                    form.setFields(errorFields);
                } else {
                    message.error(getErrorMessage(error));
                }
            },
        });
    };

    if (isLoading) {
        return <Spin />;
    }

    return (
        <div>
            <Space className="mb-4">
                <Button
                    type="text"
                    icon={<ArrowLeftOutlined />}
                    onClick={() => navigate(`/super-admin/restaurants/${id}`)}
                >
                    Quay lại
                </Button>
            </Space>

            <Card title={`Sửa: ${restaurant?.name}`}>
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    initialValues={restaurant}
                    autoComplete="off"
                    style={{ maxWidth: 600 }}
                >
                    <Form.Item
                        label="Tên Nhà Hàng"
                        name="name"
                        rules={[
                            { required: true, message: 'Vui lòng nhập tên nhà hàng' },
                            { min: 2, message: 'Tên phải có ít nhất 2 ký tự' },
                        ]}
                    >
                        <Input placeholder="Nhập tên nhà hàng" />
                    </Form.Item>

                    <Form.Item
                        label="Địa Chỉ"
                        name="address"
                    >
                        <Input.TextArea placeholder="Nhập địa chỉ" rows={3} />
                    </Form.Item>

                    <Form.Item
                        label="Điện Thoại"
                        name="phone"
                    >
                        <Input placeholder="Nhập số điện thoại" />
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" loading={isPending}>
                                Cập Nhật
                            </Button>
                            <Button onClick={() => navigate(`/super-admin/restaurants/${id}`)}>
                                Hủy
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Card>
        </div>
    );
};

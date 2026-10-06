import { useNavigate } from 'react-router-dom';
import { Card, Form, Input, Button, Space, message, Checkbox } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { useCreateFeature } from '../services/feature.hooks';
import { getErrorMessage, getFieldErrors } from '@/lib/error-handlers';
import type { CreateFeatureRequest } from '@/types';

export const FeatureCreatePage = () => {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const { mutate: createFeature, isPending } = useCreateFeature();

    const handleSubmit = (values: CreateFeatureRequest) => {
        createFeature(values, {
            onSuccess: () => {
                message.success('Tạo tính năng thành công');
                navigate('/super-admin/features');
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

    return (
        <div>
            <Space className="mb-4">
                <Button
                    type="text"
                    icon={<ArrowLeftOutlined />}
                    onClick={() => navigate('/super-admin/features')}
                >
                    Quay lại
                </Button>
            </Space>

            <Card title="Tạo Tính Năng Mới">
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    autoComplete="off"
                    style={{ maxWidth: 600 }}
                >
                    <Form.Item
                        label="Mã (Ký Tự Hoa Và Gạch Dưới)"
                        name="code"
                        rules={[
                            { required: true, message: 'Vui lòng nhập mã tính năng' },
                            {
                                pattern: /^[A-Z0-9_]+$/,
                                message: 'Mã chỉ được chứa chữ hoa, số và gạch dưới',
                            },
                        ]}
                    >
                        <Input placeholder="VD: FEATURE_NAME" />
                    </Form.Item>

                    <Form.Item
                        label="Tên"
                        name="name"
                        rules={[
                            { required: true, message: 'Vui lòng nhập tên tính năng' },
                        ]}
                    >
                        <Input placeholder="Nhập tên tính năng" />
                    </Form.Item>

                    <Form.Item
                        label="Mô Tả"
                        name="description"
                    >
                        <Input.TextArea placeholder="Nhập mô tả tính năng" rows={3} />
                    </Form.Item>

                    <Form.Item
                        name="is_active"
                        valuePropName="checked"
                        initialValue={true}
                    >
                        <Checkbox>Kích hoạt tính năng ngay</Checkbox>
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" loading={isPending}>
                                Tạo
                            </Button>
                            <Button onClick={() => navigate('/super-admin/features')}>
                                Hủy
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Card>
        </div>
    );
};

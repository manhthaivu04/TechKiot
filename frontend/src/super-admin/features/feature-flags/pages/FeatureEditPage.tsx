import { useNavigate, useParams } from 'react-router-dom';
import { Card, Form, Input, Button, Space, message, Checkbox, Spin, Alert } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { useFeature, useUpdateFeature } from '../services/feature.hooks';
import { getErrorMessage, getFieldErrors } from '@/lib/error-handlers';
import type { UpdateFeatureRequest } from '@/types';

export const FeatureEditPage = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const { data: feature, isLoading, error } = useFeature(id);
    const { mutate: updateFeature, isPending } = useUpdateFeature(id!);

    const handleSubmit = (values: UpdateFeatureRequest) => {
        updateFeature(values, {
            onSuccess: () => {
                message.success('Cập nhật tính năng thành công');
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

    if (isLoading) {
        return <Spin />;
    }

    if (error) {
        return <Alert message="Không tìm thấy tính năng" type="error" />;
    }

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

            <Card title={`Sửa: ${feature?.name}`}>
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={handleSubmit}
                    initialValues={feature}
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
                    >
                        <Checkbox>Kích hoạt</Checkbox>
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" loading={isPending}>
                                Cập Nhật
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

import { useNavigate } from 'react-router-dom';
import { Card, Form, Input, Button, Space, message, Checkbox, InputNumber, Select, Spin } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { useCreatePackage } from '../services/package.hooks';
import { useFeatures } from '@/super-admin/features/feature-flags/services/feature.hooks';
import { getErrorMessage, getFieldErrors } from '@/lib/error-handlers';
import type { CreatePackageRequest } from '@/types';

export const PackageCreatePage = () => {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const { mutate: createPackage, isPending } = useCreatePackage();
    const { data: featuresData, isLoading: featuresLoading } = useFeatures({
        per_page: 100,
        is_active: true,
    });

    const handleSubmit = (values: CreatePackageRequest) => {
        createPackage(values, {
            onSuccess: () => {
                message.success('Tạo gói dịch vụ thành công');
                navigate('/super-admin/packages');
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

    if (featuresLoading) {
        return <Spin />;
    }

    const features = featuresData?.data || [];

    return (
        <div>
            <Space className="mb-4">
                <Button
                    type="text"
                    icon={<ArrowLeftOutlined />}
                    onClick={() => navigate('/super-admin/packages')}
                >
                    Quay lại
                </Button>
            </Space>

            <Card title="Tạo Gói Dịch Vụ Mới">
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
                            { required: true, message: 'Vui lòng nhập mã gói' },
                            {
                                pattern: /^[A-Z0-9_]+$/,
                                message: 'Mã chỉ được chứa chữ hoa, số và gạch dưới',
                            },
                        ]}
                    >
                        <Input placeholder="VD: PACKAGE_NAME" />
                    </Form.Item>

                    <Form.Item
                        label="Tên"
                        name="name"
                        rules={[{ required: true, message: 'Vui lòng nhập tên gói' }]}
                    >
                        <Input placeholder="Nhập tên gói" />
                    </Form.Item>

                    <Form.Item
                        label="Giá (VNĐ)"
                        name="price"
                        rules={[
                            { required: true, message: 'Vui lòng nhập giá' },
                            {
                                type: 'number',
                                min: 0,
                                message: 'Giá phải lớn hơn hoặc bằng 0',
                            },
                        ]}
                    >
                        <InputNumber style={{ width: '100%' }} />
                    </Form.Item>

                    <Form.Item
                        label="Thời Hạn (Ngày)"
                        name="duration_days"
                        initialValue={30}
                        rules={[
                            { required: true, message: 'Vui lòng nhập thời hạn' },
                            {
                                type: 'number',
                                min: 1,
                                message: 'Thời hạn phải lớn hơn 0',
                            },
                        ]}
                    >
                        <InputNumber style={{ width: '100%' }} />
                    </Form.Item>

                    <Form.Item
                        label="Mô Tả"
                        name="description"
                    >
                        <Input.TextArea placeholder="Nhập mô tả gói" rows={3} />
                    </Form.Item>

                    <Form.Item
                        label="Tính Năng"
                        name="feature_ids"
                    >
                        <Select
                            mode="multiple"
                            placeholder="Chọn tính năng"
                            options={features.map((f) => ({
                                label: f.name,
                                value: f.id,
                            }))}
                        />
                    </Form.Item>

                    <Form.Item
                        name="is_active"
                        valuePropName="checked"
                        initialValue={true}
                    >
                        <Checkbox>Kích hoạt gói ngay</Checkbox>
                    </Form.Item>

                    <Card size="small" title="Cấu Hình Các Mốc Giá & Thời Hạn (Prices Tier)" className="mb-6">
                        <Form.List name="prices">
                            {(fields, { add, remove }) => (
                                <div className="flex flex-col gap-4">
                                    {fields.map(({ key, name, ...restField }) => (
                                        <Card size="small" key={key} className="bg-gray-50 border border-gray-200">
                                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                                <Form.Item
                                                    {...restField}
                                                    name={[name, 'duration_days']}
                                                    label="Thời Hạn (Ngày)"
                                                    rules={[{ required: true, message: 'Nhập số ngày' }]}
                                                >
                                                    <InputNumber placeholder="30, 90, 365..." style={{ width: '100%' }} />
                                                </Form.Item>
                                                <Form.Item
                                                    {...restField}
                                                    name={[name, 'price']}
                                                    label="Giá Bán (VNĐ)"
                                                    rules={[{ required: true, message: 'Nhập giá bán' }]}
                                                >
                                                    <InputNumber placeholder="Giá bán" style={{ width: '100%' }} />
                                                </Form.Item>
                                                <Form.Item
                                                    {...restField}
                                                    name={[name, 'original_price']}
                                                    label="Giá Gốc (VNĐ - Tùy chọn)"
                                                >
                                                    <InputNumber placeholder="Giá trước giảm" style={{ width: '100%' }} />
                                                </Form.Item>
                                            </div>
                                            <div className="flex justify-between items-center mt-2">
                                                <Form.Item
                                                    {...restField}
                                                    name={[name, 'is_active']}
                                                    valuePropName="checked"
                                                    initialValue={true}
                                                    noStyle
                                                >
                                                    <Checkbox>Đang hoạt động</Checkbox>
                                                </Form.Item>
                                                <Button type="link" danger onClick={() => remove(name)}>
                                                    Xóa mốc này
                                                </Button>
                                            </div>
                                        </Card>
                                    ))}
                                    <Button type="dashed" onClick={() => add()} block>
                                        + Thêm Mốc Giá Mới (1 tháng, 3 tháng, 12 tháng...)
                                    </Button>
                                </div>
                            )}
                        </Form.List>
                    </Card>

                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" loading={isPending}>
                                Tạo
                            </Button>
                            <Button onClick={() => navigate('/super-admin/packages')}>
                                Hủy
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Card>
        </div>
    );
};

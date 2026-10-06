import { useEffect } from 'react';
import { Modal, Form, Input, Switch } from 'antd';
import type { Warehouse } from '@/types';

interface WarehouseModalProps {
    open: boolean;
    onClose: () => void;
    onSubmit: (values: Record<string, unknown>) => void;
    warehouse?: Warehouse | null;
    isLoading?: boolean;
}

export const WarehouseModal = ({
    open,
    onClose,
    onSubmit,
    warehouse,
    isLoading,
}: WarehouseModalProps) => {
    const [form] = Form.useForm();

    useEffect(() => {
        if (open) {
            if (warehouse) {
                form.setFieldsValue({
                    name: warehouse.name,
                    is_default: warehouse.is_default,
                });
            } else {
                form.resetFields();
                form.setFieldsValue({
                    is_default: false,
                });
            }
        }
    }, [open, warehouse, form]);

    const handleOk = () => {
        form.validateFields().then((values) => {
            onSubmit(values);
        });
    };

    return (
        <Modal
            title={warehouse ? 'Chỉnh sửa Kho chứa' : 'Thêm Kho chứa mới'}
            open={open}
            onOk={handleOk}
            onCancel={onClose}
            confirmLoading={isLoading}
            okText={warehouse ? 'Cập nhật' : 'Thêm mới'}
            cancelText="Hủy"
            width={480}
            destroyOnClose
        >
            <Form form={form} layout="vertical" className="mt-4">
                <Form.Item
                    name="name"
                    label="Tên kho"
                    rules={[
                        { required: true, message: 'Vui lòng nhập tên kho' },
                        { max: 255, message: 'Tên kho không quá 255 ký tự' },
                    ]}
                >
                    <Input placeholder="VD: Kho chính, Kho lạnh, Kho phụ..." />
                </Form.Item>

                <Form.Item
                    name="is_default"
                    label="Đặt làm kho mặc định"
                    valuePropName="checked"
                    extra="Kho mặc định sẽ được sử dụng khi nhập/xuất hàng nếu không chỉ định kho cụ thể."
                >
                    <Switch checkedChildren="Có" unCheckedChildren="Không" />
                </Form.Item>
            </Form>
        </Modal>
    );
};

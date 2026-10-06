import { Modal, Form, Input, InputNumber, Switch } from 'antd';
import type { ItemGroup } from '@/types';
import { useEffect } from 'react';

interface ItemGroupModalProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: Record<string, unknown>) => void;
    initialData?: ItemGroup | null;
    isLoading?: boolean;
}

export const ItemGroupModal = ({
    visible,
    onClose,
    onSubmit,
    initialData,
    isLoading,
}: ItemGroupModalProps) => {
    const [form] = Form.useForm();

    useEffect(() => {
        if (visible) {
            if (initialData) {
                form.setFieldsValue({
                    name: initialData.name,
                    display_order: initialData.display_order,
                    is_active: initialData.is_active,
                });
            } else {
                form.resetFields();
                form.setFieldsValue({
                    display_order: 0,
                    is_active: true,
                });
            }
        }
    }, [visible, initialData, form]);

    const handleOk = () => {
        form.validateFields().then((values) => {
            onSubmit(values);
        });
    };

    return (
        <Modal
            title={initialData ? 'Chỉnh sửa Nhóm món' : 'Thêm Nhóm món mới'}
            open={visible}
            onOk={handleOk}
            onCancel={onClose}
            confirmLoading={isLoading}
            okText={initialData ? 'Cập nhật' : 'Thêm mới'}
            cancelText="Hủy"
        >
            <Form form={form} layout="vertical">
                <Form.Item
                    name="name"
                    label="Tên nhóm món"
                    rules={[{ required: true, message: 'Vui lòng nhập tên nhóm món' }]}
                >
                    <Input placeholder="Ví dụ: Nước uống" />
                </Form.Item>

                <Form.Item
                    name="display_order"
                    label="Thứ tự hiển thị"
                    rules={[{ required: true, message: 'Vui lòng nhập thứ tự' }]}
                >
                    <InputNumber min={0} style={{ width: '100%' }} />
                </Form.Item>

                <Form.Item
                    name="is_active"
                    label="Trạng thái hiển thị"
                    valuePropName="checked"
                >
                    <Switch checkedChildren="Bật" unCheckedChildren="Tắt" />
                </Form.Item>
            </Form>
        </Modal>
    );
};

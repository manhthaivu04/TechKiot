import { Modal, Form, Input, InputNumber } from 'antd';
import type { TableArea } from '@/types';
import { useEffect } from 'react';

const { TextArea } = Input;

interface TableAreaModalProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: Record<string, unknown>) => void;
    initialData?: TableArea | null;
    isLoading?: boolean;
}

export const TableAreaModal = ({
    visible,
    onClose,
    onSubmit,
    initialData,
    isLoading,
}: TableAreaModalProps) => {
    const [form] = Form.useForm();

    useEffect(() => {
        if (visible) {
            if (initialData) {
                form.setFieldsValue({
                    name: initialData.name,
                    description: initialData.description || '',
                    display_order: initialData.display_order,
                });
            } else {
                form.resetFields();
                form.setFieldsValue({
                    display_order: 0,
                });
            }
        }
    }, [visible, initialData, form]);

    const handleOk = () => {
        form.validateFields().then((values) => {
            // For edit: compute diff, only send changed fields
            if (initialData) {
                const changedFields: Record<string, unknown> = {};
                if (values.name !== initialData.name) changedFields.name = values.name;
                if ((values.description || null) !== (initialData.description || null)) {
                    changedFields.description = values.description || null;
                }
                if (values.display_order !== initialData.display_order) {
                    changedFields.display_order = values.display_order;
                }
                // Only send if there are changes
                if (Object.keys(changedFields).length > 0) {
                    onSubmit(changedFields);
                } else {
                    onClose();
                }
            } else {
                // For create: send all values, clean up empty description
                const payload = { ...values };
                if (!payload.description) {
                    delete payload.description;
                }
                onSubmit(payload);
            }
        });
    };

    return (
        <Modal
            title={initialData ? 'Chỉnh sửa Khu vực bàn' : 'Thêm Khu vực bàn mới'}
            open={visible}
            onOk={handleOk}
            onCancel={onClose}
            confirmLoading={isLoading}
            okText={initialData ? 'Cập nhật' : 'Thêm mới'}
            cancelText="Hủy"
            destroyOnClose
        >
            <Form form={form} layout="vertical" style={{ marginTop: 16 }}>
                <Form.Item
                    name="name"
                    label="Tên khu vực"
                    rules={[
                        { required: true, message: 'Vui lòng nhập tên khu vực' },
                        { max: 100, message: 'Tên khu vực tối đa 100 ký tự' },
                    ]}
                >
                    <Input placeholder="Ví dụ: Tầng 1, Sân vườn, Khu VIP..." />
                </Form.Item>

                <Form.Item
                    name="description"
                    label="Mô tả"
                >
                    <TextArea
                        rows={3}
                        placeholder="Mô tả ngắn về khu vực (tùy chọn)"
                        maxLength={500}
                        showCount
                    />
                </Form.Item>

                <Form.Item
                    name="display_order"
                    label="Thứ tự hiển thị"
                    tooltip="Số nhỏ hơn sẽ hiển thị trước"
                >
                    <InputNumber min={0} style={{ width: '100%' }} placeholder="0" />
                </Form.Item>
            </Form>
        </Modal>
    );
};

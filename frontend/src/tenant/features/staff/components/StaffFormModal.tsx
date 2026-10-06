import { useEffect } from 'react';
import { Modal, Form, Input, Select, Switch, message } from 'antd';
import { useCreateStaff, useUpdateStaff } from '../services/staff.hooks';
import { User, UserRole } from '@/types';

interface StaffFormModalProps {
    visible: boolean;
    onClose: () => void;
    initialData: User | null;
}

export const StaffFormModal = ({ visible, onClose, initialData }: StaffFormModalProps) => {
    const [form] = Form.useForm();
    const isEditing = !!initialData;

    const createMutation = useCreateStaff();
    const updateMutation = useUpdateStaff(initialData?.id || '');

    useEffect(() => {
        if (visible) {
            if (initialData) {
                form.setFieldsValue({
                    ...initialData,
                });
            } else {
                form.resetFields();
                form.setFieldsValue({ is_active: true, role: UserRole.WAITER });
            }
        }
    }, [visible, initialData, form]);

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            
            const mutation = isEditing ? updateMutation : createMutation;
            
            mutation.mutate(values, {
                onSuccess: () => {
                    message.success(isEditing ? 'Cập nhật thành công!' : 'Tạo mới thành công!');
                    onClose();
                },
                onError: (error: any) => {
                    const errorMsg = error?.response?.data?.message || 'Có lỗi xảy ra!';
                    const validationErrors = error?.response?.data?.errors;
                    
                    if (validationErrors) {
                        const fields = Object.keys(validationErrors).map(key => ({
                            name: key,
                            errors: validationErrors[key],
                        }));
                        form.setFields(fields);
                    } else {
                        message.error(errorMsg);
                    }
                }
            });
        } catch (error) {
            // Validation failed
        }
    };

    return (
        <Modal
            title={isEditing ? 'Sửa thông tin nhân viên' : 'Thêm nhân viên mới'}
            open={visible}
            onOk={handleSubmit}
            onCancel={onClose}
            confirmLoading={createMutation.isPending || updateMutation.isPending}
            okText={isEditing ? 'Cập nhật' : 'Tạo mới'}
            cancelText="Hủy"
            width={500}
        >
            <Form form={form} layout="vertical" className="mt-4">
                <Form.Item
                    name="name"
                    label="Tên nhân viên"
                    rules={[{ required: true, message: 'Vui lòng nhập tên nhân viên' }]}
                >
                    <Input placeholder="Ví dụ: Nguyễn Văn A" />
                </Form.Item>

                <Form.Item
                    name="email"
                    label="Tài khoản đăng nhập (Email)"
                    rules={[
                        { required: true, message: 'Vui lòng nhập email' },
                        { type: 'email', message: 'Email không hợp lệ' }
                    ]}
                >
                    <Input placeholder="Ví dụ: nva@kiottay.com" disabled={isEditing} />
                </Form.Item>

                <Form.Item
                    name="password"
                    label={isEditing ? 'Mật khẩu mới (Để trống nếu không đổi)' : 'Mật khẩu'}
                    rules={[
                        { required: !isEditing, message: 'Vui lòng nhập mật khẩu' },
                        { min: 8, message: 'Mật khẩu phải từ 8 ký tự' }
                    ]}
                >
                    <Input.Password placeholder="Nhập mật khẩu" />
                </Form.Item>

                <Form.Item
                    name="role"
                    label="Chức vụ"
                    rules={[{ required: true, message: 'Vui lòng chọn chức vụ' }]}
                >
                    <Select placeholder="Chọn chức vụ">
                        <Select.Option value={UserRole.MANAGER}>Quản lý</Select.Option>
                        <Select.Option value={UserRole.WAITER}>Phục vụ</Select.Option>
                        <Select.Option value={UserRole.KITCHEN}>Bếp</Select.Option>
                        <Select.Option value={UserRole.CASHIER}>Thu ngân</Select.Option>
                    </Select>
                </Form.Item>

                <Form.Item
                    name="is_active"
                    label="Trạng thái hoạt động"
                    valuePropName="checked"
                >
                    <Switch />
                </Form.Item>
            </Form>
        </Modal>
    );
};

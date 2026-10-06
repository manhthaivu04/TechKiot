import { Modal, Form, Input, InputNumber, Select } from 'antd';
import type { RestaurantTable, TableArea } from '@/types';
import { TABLE_STATUS_OPTIONS } from '@/types';
import { useEffect, useRef } from 'react';
import type { FormInstance } from 'antd';

interface RestaurantTableModalProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: Record<string, unknown>) => void;
    initialData?: RestaurantTable | null;
    isLoading?: boolean;
    areas: TableArea[];
    serverErrors?: Record<string, string[]> | null;
}

export const RestaurantTableModal = ({
    visible,
    onClose,
    onSubmit,
    initialData,
    isLoading,
    areas,
    serverErrors,
}: RestaurantTableModalProps) => {
    const [form] = Form.useForm();
    const formRef = useRef<FormInstance>(form);
    formRef.current = form;

    useEffect(() => {
        if (visible) {
            if (initialData) {
                form.setFieldsValue({
                    name: initialData.name,
                    uid: initialData.uid,
                    area_id: initialData.area_id || '__none__',
                    capacity: initialData.capacity,
                    status: initialData.status,
                });
            } else {
                form.resetFields();
                form.setFieldsValue({
                    area_id: '__none__',
                    capacity: 4,
                    status: 'available',
                });
            }
        }
    }, [visible, initialData, form]);

    // Show server-side validation errors on form fields
    useEffect(() => {
        if (serverErrors && visible) {
            const fields = Object.entries(serverErrors).map(([name, messages]) => ({
                name,
                errors: messages,
            }));
            formRef.current.setFields(fields);
        }
    }, [serverErrors, visible]);

    const areaOptions = [
        { label: 'Không thuộc khu vực nào', value: '__none__' },
        ...areas.map((area) => ({ label: area.name, value: area.id })),
    ];

    const statusOptions = TABLE_STATUS_OPTIONS.map((s) => ({
        label: s.label,
        value: s.value,
    }));

    const handleOk = () => {
        form.validateFields().then((values) => {
            // Normalize area_id
            const areaId = values.area_id === '__none__' ? null : values.area_id;

            if (initialData) {
                // Compute diff for PATCH
                const changedFields: Record<string, unknown> = {};
                if (values.name !== initialData.name) changedFields.name = values.name;
                if ((values.uid || '') !== (initialData.uid || '')) changedFields.uid = values.uid;
                if (areaId !== initialData.area_id) changedFields.area_id = areaId;
                if (values.capacity !== initialData.capacity) changedFields.capacity = values.capacity;
                if (values.status !== initialData.status) changedFields.status = values.status;

                if (Object.keys(changedFields).length > 0) {
                    onSubmit(changedFields);
                } else {
                    onClose();
                }
            } else {
                // Create — build clean payload
                const payload: Record<string, unknown> = {
                    name: values.name,
                    capacity: values.capacity,
                    status: values.status,
                };
                if (areaId) payload.area_id = areaId;
                if (values.uid) payload.uid = values.uid;
                onSubmit(payload);
            }
        });
    };

    return (
        <Modal
            title={initialData ? 'Chỉnh sửa bàn ăn' : 'Thêm bàn ăn mới'}
            open={visible}
            onOk={handleOk}
            onCancel={onClose}
            confirmLoading={isLoading}
            okText={initialData ? 'Cập nhật' : 'Thêm mới'}
            cancelText="Hủy"
            destroyOnClose
            width={520}
        >
            <Form form={form} layout="vertical" style={{ marginTop: 16 }}>
                <Form.Item
                    name="name"
                    label="Tên bàn"
                    rules={[
                        { required: true, message: 'Vui lòng nhập tên bàn' },
                        { max: 100, message: 'Tên bàn tối đa 100 ký tự' },
                    ]}
                >
                    <Input placeholder="Ví dụ: Bàn VIP 1, Bàn số 5..." />
                </Form.Item>

                <Form.Item
                    name="uid"
                    label="Mã bàn"
                    tooltip="Để trống hệ thống sẽ tự sinh mã bàn (B-001, B-002...)"
                >
                    <Input placeholder="Để trống để tự sinh (B-001, B-002...)" />
                </Form.Item>

                <Form.Item
                    name="area_id"
                    label="Khu vực"
                >
                    <Select
                        options={areaOptions}
                        placeholder="Chọn khu vực"
                    />
                </Form.Item>

                <div style={{ display: 'flex', gap: 16 }}>
                    <Form.Item
                        name="capacity"
                        label="Sức chứa"
                        style={{ flex: 1 }}
                        rules={[
                            { required: true, message: 'Vui lòng nhập sức chứa' },
                        ]}
                    >
                        <InputNumber min={1} style={{ width: '100%' }} addonAfter="chỗ" />
                    </Form.Item>

                    <Form.Item
                        name="status"
                        label="Trạng thái"
                        style={{ flex: 1 }}
                    >
                        <Select options={statusOptions} />
                    </Form.Item>
                </div>
            </Form>
        </Modal>
    );
};

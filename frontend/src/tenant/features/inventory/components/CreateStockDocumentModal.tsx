import { useEffect, useMemo } from 'react';
import { Modal, Form, Select, Input, InputNumber, Button, Typography, message } from 'antd';
import { PlusOutlined, MinusCircleOutlined } from '@ant-design/icons';
import { DOCUMENT_TYPE_OPTIONS } from '@/types';
import { useWarehouses } from '../services/warehouse.hooks';
import { useIngredients } from '@/tenant/features/menu/services/menu.hooks';
import { useCreateStockDocument } from '../services/stock-document.hooks';
import type { AxiosError } from 'axios';
import type { ApiErrorResponse } from '@/api/http';

const { Text } = Typography;

interface CreateStockDocumentModalProps {
    open: boolean;
    onClose: () => void;
}

interface FormItemRow {
    item_id: string;
    quantity: number;
    unit_cost: number;
}

export const CreateStockDocumentModal = ({
    open,
    onClose,
}: CreateStockDocumentModalProps) => {
    const [form] = Form.useForm();
    const { data: warehouseData, isLoading: isLoadingWarehouses } = useWarehouses();
    const { data: ingredientData, isLoading: isLoadingIngredients } = useIngredients();
    const createMutation = useCreateStockDocument();

    const warehouseOptions = useMemo(() => {
        return (warehouseData?.data || []).map((w) => ({
            value: w.id,
            label: w.name,
        }));
    }, [warehouseData?.data]);

    const ingredientOptions = useMemo(() => {
        return (ingredientData?.data || []).map((item) => ({
            value: item.id,
            label: `${item.name} (${item.unit})`,
        }));
    }, [ingredientData?.data]);

    useEffect(() => {
        if (open) {
            form.resetFields();
            form.setFieldsValue({
                items: [{ item_id: undefined, quantity: undefined, unit_cost: undefined }],
            });
        }
    }, [open, form]);

    // Watch items for total calculation
    const watchedItems: FormItemRow[] | undefined = Form.useWatch('items', form);

    const totalValue = useMemo(() => {
        if (!watchedItems) return 0;
        return watchedItems.reduce((sum, row) => {
            if (!row?.quantity || !row?.unit_cost) return sum;
            return sum + row.quantity * row.unit_cost;
        }, 0);
    }, [watchedItems]);

    const handleSubmit = () => {
        form.validateFields().then((values) => {
            const validItems = (values.items || []).filter(
                (row: FormItemRow) => row.item_id && row.quantity && row.unit_cost !== undefined
            );

            if (validItems.length === 0) {
                message.warning('Vui lòng thêm ít nhất 1 nguyên liệu!');
                return;
            }

            createMutation.mutate(
                {
                    warehouse_id: values.warehouse_id,
                    document_type: values.document_type,
                    note: values.note || undefined,
                    items: validItems.map((row: FormItemRow) => ({
                        item_id: row.item_id,
                        quantity: row.quantity,
                        unit_cost: row.unit_cost,
                    })),
                },
                {
                    onSuccess: () => {
                        message.success('Tạo chứng từ thành công!');
                        onClose();
                    },
                    onError: (error: unknown) => {
                        const axiosErr = error as AxiosError<ApiErrorResponse>;
                        message.error(
                            axiosErr.response?.data?.message || 'Có lỗi xảy ra khi tạo chứng từ!'
                        );
                    },
                }
            );
        });
    };

    return (
        <Modal
            title="Tạo chứng từ kho"
            open={open}
            onOk={handleSubmit}
            onCancel={onClose}
            confirmLoading={createMutation.isPending}
            okText="Tạo phiếu"
            cancelText="Hủy"
            width={800}
            destroyOnClose
        >
            <Form form={form} layout="vertical" autoComplete="off" className="mt-2">
                {/* Header fields */}
                <div className="grid grid-cols-2 gap-4">
                    <Form.Item
                        name="warehouse_id"
                        label="Kho chứa"
                        rules={[{ required: true, message: 'Vui lòng chọn kho' }]}
                    >
                        <Select
                            placeholder="Chọn kho"
                            loading={isLoadingWarehouses}
                            options={warehouseOptions}
                            showSearch
                            filterOption={(input, option) =>
                                (option?.label ?? '')
                                    .toString()
                                    .toLowerCase()
                                    .includes(input.toLowerCase())
                            }
                        />
                    </Form.Item>

                    <Form.Item
                        name="document_type"
                        label="Loại chứng từ"
                        rules={[{ required: true, message: 'Vui lòng chọn loại chứng từ' }]}
                    >
                        <Select placeholder="Chọn loại">
                            {DOCUMENT_TYPE_OPTIONS.map((opt) => (
                                <Select.Option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </Select.Option>
                            ))}
                        </Select>
                    </Form.Item>
                </div>

                <Form.Item name="note" label="Ghi chú">
                    <Input.TextArea rows={2} placeholder="Ghi chú cho phiếu (tùy chọn)" />
                </Form.Item>

                {/* Dynamic item rows */}
                <Text strong style={{ display: 'block', marginBottom: 8 }}>
                    Danh sách hàng hóa
                </Text>

                <Form.List name="items">
                    {(fields, { add, remove }) => (
                        <>
                            {/* Column headers */}
                            {fields.length > 0 && (
                                <div className="grid grid-cols-12 gap-2 mb-1 px-1">
                                    <div className="col-span-4">
                                        <Text className="text-xs text-gray-500">Nguyên liệu *</Text>
                                    </div>
                                    <div className="col-span-2">
                                        <Text className="text-xs text-gray-500">Số lượng *</Text>
                                    </div>
                                    <div className="col-span-3">
                                        <Text className="text-xs text-gray-500">Đơn giá *</Text>
                                    </div>
                                    <div className="col-span-2">
                                        <Text className="text-xs text-gray-500">Thành tiền</Text>
                                    </div>
                                    <div className="col-span-1" />
                                </div>
                            )}

                            {fields.map(({ key, name, ...restField }) => {
                                const currentRow = watchedItems?.[name];
                                const rowTotal =
                                    (currentRow?.quantity || 0) * (currentRow?.unit_cost || 0);

                                return (
                                    <div key={key} className="grid grid-cols-12 gap-2 items-start">
                                        <div className="col-span-4">
                                            <Form.Item
                                                {...restField}
                                                name={[name, 'item_id']}
                                                rules={[
                                                    { required: true, message: 'Chọn NL' },
                                                ]}
                                                className="mb-3"
                                            >
                                                <Select
                                                    placeholder="Chọn nguyên liệu"
                                                    loading={isLoadingIngredients}
                                                    showSearch
                                                    filterOption={(input, option) =>
                                                        (option?.label ?? '')
                                                            .toString()
                                                            .toLowerCase()
                                                            .includes(input.toLowerCase())
                                                    }
                                                    options={ingredientOptions}
                                                />
                                            </Form.Item>
                                        </div>
                                        <div className="col-span-2">
                                            <Form.Item
                                                {...restField}
                                                name={[name, 'quantity']}
                                                rules={[
                                                    { required: true, message: 'Nhập SL' },
                                                    {
                                                        type: 'number',
                                                        min: 0.001,
                                                        message: '> 0',
                                                    },
                                                ]}
                                                className="mb-3"
                                            >
                                                <InputNumber
                                                    placeholder="0"
                                                    style={{ width: '100%' }}
                                                    min={0.001}
                                                    step={0.1}
                                                    precision={3}
                                                />
                                            </Form.Item>
                                        </div>
                                        <div className="col-span-3">
                                            <Form.Item
                                                {...restField}
                                                name={[name, 'unit_cost']}
                                                rules={[
                                                    { required: true, message: 'Nhập giá' },
                                                    {
                                                        type: 'number',
                                                        min: 0,
                                                        message: '≥ 0',
                                                    },
                                                ]}
                                                className="mb-3"
                                            >
                                                <InputNumber<number>
                                                    placeholder="0"
                                                    style={{ width: '100%' }}
                                                    min={0}
                                                    step={1000}
                                                    formatter={(value) =>
                                                        `${value}`.replace(
                                                            /\B(?=(\d{3})+(?!\d))/g,
                                                            ','
                                                        )
                                                    }
                                                    parser={(value) =>
                                                        Number(value!.replace(/\$\s?|(,*)/g, '')) || 0
                                                    }
                                                    addonAfter="đ"
                                                />
                                            </Form.Item>
                                        </div>
                                        <div className="col-span-2 flex items-center h-8 mt-1">
                                            <Text className="text-sm text-blue-600 font-medium">
                                                {rowTotal > 0
                                                    ? `${rowTotal.toLocaleString('vi-VN')} đ`
                                                    : '—'}
                                            </Text>
                                        </div>
                                        <div className="col-span-1 flex justify-center pt-1">
                                            <Button
                                                type="text"
                                                danger
                                                icon={<MinusCircleOutlined />}
                                                onClick={() => remove(name)}
                                                disabled={fields.length <= 1}
                                            />
                                        </div>
                                    </div>
                                );
                            })}

                            <Form.Item>
                                <Button
                                    type="dashed"
                                    onClick={() => add()}
                                    block
                                    icon={<PlusOutlined />}
                                >
                                    Thêm nguyên liệu
                                </Button>
                            </Form.Item>
                        </>
                    )}
                </Form.List>

                {/* Total */}
                <div className="flex justify-end mt-2 pt-3 border-t border-gray-200">
                    <div className="text-right">
                        <Text type="secondary" className="mr-3">
                            Tổng cộng:
                        </Text>
                        <Text strong className="text-lg text-red-600">
                            {totalValue.toLocaleString('vi-VN')} đ
                        </Text>
                    </div>
                </div>
            </Form>
        </Modal>
    );
};

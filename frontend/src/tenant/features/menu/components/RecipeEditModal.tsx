import { useEffect, useMemo } from 'react';
import { Modal, Form, Select, InputNumber, Button, message, Typography } from 'antd';
import { PlusOutlined, MinusCircleOutlined } from '@ant-design/icons';
import type { ItemIngredient } from '@/types';
import { useIngredients, useSyncRecipe } from '../services/menu.hooks';

const { Text } = Typography;

interface RecipeEditModalProps {
    open: boolean;
    onClose: () => void;
    itemId: string;
    currentIngredients: ItemIngredient[];
}

interface RecipeFormRow {
    ingredient_id: string;
    quantity: number;
}

export const RecipeEditModal = ({
    open,
    onClose,
    itemId,
    currentIngredients,
}: RecipeEditModalProps) => {
    const [form] = Form.useForm();
    const { data: ingredientData, isLoading: isLoadingIngredients } = useIngredients();
    const syncRecipeMutation = useSyncRecipe();

    const ingredientOptions = useMemo(() => {
        return (ingredientData?.data || []).map((item) => ({
            value: item.id,
            label: `${item.name} (${item.unit})`,
            cost_price: item.cost_price,
        }));
    }, [ingredientData?.data]);

    // Initialize form when modal opens
    useEffect(() => {
        if (open) {
            if (currentIngredients.length > 0) {
                form.setFieldsValue({
                    ingredients: currentIngredients.map((ing) => ({
                        ingredient_id: ing.id,
                        quantity: parseFloat(ing.pivot.quantity),
                    })),
                });
            } else {
                form.setFieldsValue({
                    ingredients: [{ ingredient_id: undefined, quantity: undefined }],
                });
            }
        }
    }, [open, currentIngredients, form]);

    const handleSubmit = () => {
        form.validateFields().then((values: { ingredients: RecipeFormRow[] }) => {
            // Filter out empty rows
            const validIngredients = (values.ingredients || []).filter(
                (row) => row.ingredient_id && row.quantity
            );

            syncRecipeMutation.mutate(
                {
                    itemId,
                    data: {
                        ingredients: validIngredients.map((row) => ({
                            ingredient_id: row.ingredient_id,
                            quantity: row.quantity,
                        })),
                    },
                },
                {
                    onSuccess: () => {
                        message.success('Cập nhật công thức thành công!');
                        onClose();
                    },
                    onError: (error: unknown) => {
                        message.error(
                            (error as { response?: { data?: { message?: string } } }).response
                                ?.data?.message || 'Có lỗi xảy ra khi cập nhật công thức!'
                        );
                    },
                }
            );
        });
    };

    // Custom validator: check for duplicate ingredients
    const validateNoDuplicate = (_: unknown, value: string) => {
        if (!value) return Promise.resolve();
        const allIngredients: RecipeFormRow[] = form.getFieldValue('ingredients') || [];
        const count = allIngredients.filter((row) => row?.ingredient_id === value).length;
        if (count > 1) {
            return Promise.reject(new Error('Nguyên liệu này đã được chọn!'));
        }
        return Promise.resolve();
    };

    return (
        <Modal
            title="Chỉnh sửa Công thức"
            open={open}
            onOk={handleSubmit}
            onCancel={onClose}
            confirmLoading={syncRecipeMutation.isPending}
            okText="Lưu công thức"
            cancelText="Hủy"
            width={700}
            destroyOnClose
        >
            <div className="mb-4">
                <Text type="secondary">
                    Chọn nguyên liệu và nhập số lượng cần dùng cho công thức này.
                </Text>
            </div>

            <Form form={form} layout="vertical" autoComplete="off">
                <Form.List name="ingredients">
                    {(fields, { add, remove }) => (
                        <>
                            {/* Header labels */}
                            {fields.length > 0 && (
                                <div className="grid grid-cols-12 gap-2 mb-1 px-1">
                                    <div className="col-span-6">
                                        <Text strong className="text-xs text-gray-500">
                                            Nguyên liệu
                                        </Text>
                                    </div>
                                    <div className="col-span-4">
                                        <Text strong className="text-xs text-gray-500">
                                            Số lượng
                                        </Text>
                                    </div>
                                    <div className="col-span-2" />
                                </div>
                            )}

                            {fields.map(({ key, name, ...restField }) => (
                                <div key={key} className="grid grid-cols-12 gap-2 items-start">
                                    <div className="col-span-6">
                                        <Form.Item
                                            {...restField}
                                            name={[name, 'ingredient_id']}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: 'Chọn nguyên liệu',
                                                },
                                                {
                                                    validator: validateNoDuplicate,
                                                },
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
                                                onChange={() => {
                                                    // Re-validate all ingredient fields to check duplicates
                                                    const fields =
                                                        form.getFieldValue('ingredients') || [];
                                                    fields.forEach((_: unknown, idx: number) => {
                                                        form.validateFields([
                                                            ['ingredients', idx, 'ingredient_id'],
                                                        ]);
                                                    });
                                                }}
                                            />
                                        </Form.Item>
                                    </div>
                                    <div className="col-span-4">
                                        <Form.Item
                                            {...restField}
                                            name={[name, 'quantity']}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: 'Nhập SL',
                                                },
                                                {
                                                    type: 'number',
                                                    min: 0.001,
                                                    message: 'Phải > 0',
                                                },
                                            ]}
                                            className="mb-3"
                                        >
                                            <InputNumber
                                                placeholder="0.000"
                                                style={{ width: '100%' }}
                                                min={0.001}
                                                step={0.01}
                                                precision={3}
                                            />
                                        </Form.Item>
                                    </div>
                                    <div className="col-span-2 flex justify-center pt-1">
                                        <Button
                                            type="text"
                                            danger
                                            icon={<MinusCircleOutlined />}
                                            onClick={() => remove(name)}
                                            disabled={fields.length <= 1}
                                        />
                                    </div>
                                </div>
                            ))}

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
            </Form>
        </Modal>
    );
};

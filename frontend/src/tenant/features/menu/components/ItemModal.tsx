import { Modal, Form, Input, InputNumber, Switch, Select, Upload, message, AutoComplete } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import type { UploadFile } from 'antd';
import type { Item, ItemGroup } from '@/types';
import { FeatureCode } from '@/types';
import { useEffect, useState } from 'react';
import { useFeatureFlag } from '@/auth/hooks/useFeatureFlag';

const { TextArea } = Input;
const { Option } = Select;

interface ItemModalProps {
    visible: boolean;
    onClose: () => void;
    onSubmit: (values: Record<string, unknown>) => void;
    initialData?: Item | null;
    isLoading?: boolean;
    itemGroups: ItemGroup[];
    defaultGroupId?: string;
    defaultItemType?: string;
}

export const ItemModal = ({
    visible,
    onClose,
    onSubmit,
    initialData,
    isLoading,
    itemGroups,
    defaultGroupId,
    defaultItemType = 'MENU_ITEM'
}: ItemModalProps) => {
    const [form] = Form.useForm();
    const [fileList, setFileList] = useState<UploadFile[]>([]);
    
    const hasInventoryManagement = useFeatureFlag(FeatureCode.INVENTORY_MANAGEMENT);

    useEffect(() => {
        if (visible) {
            if (initialData) {
                form.setFieldsValue({
                    item_type: initialData.item_type || 'MENU_ITEM',
                    item_group_id: initialData.item_group_id,
                    name: initialData.name,
                    unit: initialData.unit,
                    sale_price: initialData.sale_price,
                    cost_price: initialData.cost_price,
                    description: initialData.description,
                    availability_status: initialData.availability_status,
                    is_active: initialData.is_active,
                });
                
                if (initialData.image_url) {
                    // eslint-disable-next-line react-hooks/set-state-in-effect
                    setFileList([
                        {
                            uid: '-1',
                            name: 'image.jpg',
                            status: 'done',
                            url: initialData.image_url,
                        },
                    ]);
                } else {
                    setFileList([]);
                }
            } else {
                form.resetFields();
                form.setFieldsValue({
                    item_type: defaultItemType,
                    item_group_id: defaultItemType === 'INGREDIENT' ? null : (defaultGroupId || (itemGroups.length > 0 ? itemGroups[0].id : undefined)),
                    availability_status: 'IN_STOCK',
                    is_active: true,
                    sale_price: defaultItemType === 'INGREDIENT' ? null : undefined,
                });
                setFileList([]);
            }
        }
    }, [visible, initialData, form, defaultGroupId, itemGroups, defaultItemType]);

    const handleOk = () => {
        form.validateFields().then((values) => {
            const formData = { ...values };
            
            // Only add image if it's a new file (not the existing URL object)
            if (fileList.length > 0 && fileList[0].originFileObj) {
                formData.image = fileList[0].originFileObj;
            } else if (fileList.length === 0) {
                formData.image = null; // Removed image
            }

            onSubmit(formData);
        });
    };

    const normFile = (e: { fileList: UploadFile[] } | UploadFile[]) => {
        if (Array.isArray(e)) return e;
        return e?.fileList;
    };

    const handleChange = ({ fileList: newFileList }: { fileList: UploadFile[] }) => {
        // Keep only the latest file
        setFileList(newFileList.slice(-1));
    };

    const handleValuesChange = (changedValues: any) => {
        if (changedValues.item_type === 'INGREDIENT') {
            form.setFieldsValue({
                item_group_id: null,
                sale_price: null
            });
        } else if (changedValues.item_type === 'MENU_ITEM') {
            form.setFieldsValue({
                item_group_id: defaultGroupId || (itemGroups.length > 0 ? itemGroups[0].id : undefined)
            });
        }
    };

    const itemType = Form.useWatch('item_type', form);
    const isIngredient = itemType === 'INGREDIENT';

    return (
        <Modal
            title={initialData ? 'Chỉnh sửa Món ăn' : 'Thêm Món ăn mới'}
            open={visible}
            onOk={handleOk}
            onCancel={onClose}
            confirmLoading={isLoading}
            okText={initialData ? 'Cập nhật' : 'Thêm mới'}
            cancelText="Hủy"
            width={600}
        >
            <Form form={form} layout="vertical" onValuesChange={handleValuesChange}>
                <div className="grid grid-cols-2 gap-4">
                    <Form.Item
                        name="item_type"
                        label="Loại"
                        rules={[{ required: true, message: 'Vui lòng chọn loại' }]}
                        className="col-span-2 sm:col-span-1"
                    >
                        <Select>
                            <Option value="MENU_ITEM">Món ăn/Đồ uống (Hàng hóa)</Option>
                            <Option value="INGREDIENT" disabled={!hasInventoryManagement}>
                                Nguyên liệu (Hàng chế biến) {!hasInventoryManagement && '- Cần nâng cấp gói'}
                            </Option>
                        </Select>
                    </Form.Item>

                    {!isIngredient && (
                        <Form.Item
                            name="item_group_id"
                            label="Nhóm món"
                            rules={[{ required: true, message: 'Vui lòng chọn nhóm món' }]}
                            className="col-span-2 sm:col-span-1"
                        >
                            <Select placeholder="Chọn nhóm món">
                                {itemGroups.map((group) => (
                                    <Option key={group.id} value={group.id}>
                                        {group.name}
                                    </Option>
                                ))}
                            </Select>
                        </Form.Item>
                    )}

                    <Form.Item
                        name="name"
                        label="Tên món ăn"
                        rules={[{ required: true, message: 'Vui lòng nhập tên món' }]}
                        className="col-span-2 sm:col-span-1"
                    >
                        <Input placeholder="Ví dụ: Cà phê đen" />
                    </Form.Item>

                    {!isIngredient && (
                        <Form.Item
                            name="sale_price"
                            label="Giá bán"
                            rules={[{ required: true, message: 'Vui lòng nhập giá bán' }]}
                            className="col-span-2 sm:col-span-1"
                        >
                            <InputNumber
                                style={{ width: '100%' }}
                                formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                parser={(value) => value!.replace(/\$\s?|(,*)/g, '')}
                                placeholder="0"
                                addonAfter="VNĐ"
                            />
                        </Form.Item>
                    )}

                    <Form.Item
                        name="unit"
                        label="Đơn vị tính"
                        rules={[{ required: true, message: 'Vui lòng nhập đơn vị' }]}
                    >
                        <AutoComplete
                            options={[
                                { value: 'Phần' },
                                { value: 'Tô' },
                                { value: 'Bát' },
                                { value: 'Đĩa' },
                                { value: 'Ly' },
                                { value: 'Cốc' },
                                { value: 'Chai' },
                                { value: 'Lon' },
                                { value: 'Con' },
                                { value: 'Nồi' },
                                { value: 'Mẹt' },
                                { value: 'Kg' },
                                { value: 'Gram' },
                            ]}
                            placeholder="Ví dụ: Ly, Cốc, Phần"
                            filterOption={(inputValue, option) =>
                                option!.value.toUpperCase().indexOf(inputValue.toUpperCase()) !== -1
                            }
                        />
                    </Form.Item>

                    <Form.Item
                        name="cost_price"
                        label="Giá vốn (Tùy chọn)"
                    >
                        <InputNumber
                            style={{ width: '100%' }}
                            formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                            parser={(value) => value!.replace(/\$\s?|(,*)/g, '')}
                            placeholder="0"
                            addonAfter="VNĐ"
                        />
                    </Form.Item>

                    <Form.Item
                        name="availability_status"
                        label="Trạng thái kho"
                        rules={[{ required: true, message: 'Vui lòng chọn trạng thái kho' }]}
                    >
                        <Select>
                            <Option value="IN_STOCK">Còn hàng</Option>
                            <Option value="OUT_OF_STOCK">Hết hàng</Option>
                            <Option value="SUSPENDED">Ngưng bán</Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="image"
                        label="Hình ảnh món ăn"
                        valuePropName="fileList"
                        getValueFromEvent={normFile}
                        className="col-span-2"
                    >
                        <Upload
                            listType="picture-card"
                            fileList={fileList}
                            onChange={handleChange}
                            beforeUpload={(file) => {
                                const isImage = file.type === 'image/jpeg' || file.type === 'image/png' || file.type === 'image/webp';
                                if (!isImage) {
                                    message.error('Bạn chỉ có thể upload file ảnh (JPG/PNG/WEBP)!');
                                    return Upload.LIST_IGNORE;
                                }
                                const isLt2M = file.size / 1024 / 1024 < 2;
                                if (!isLt2M) {
                                    message.error('Kích thước ảnh phải nhỏ hơn 2MB!');
                                    return Upload.LIST_IGNORE;
                                }
                                return false; // Prevent auto upload
                            }}
                            showUploadList={{ showPreviewIcon: false, showRemoveIcon: true }}
                        >
                            <div>
                                <PlusOutlined />
                                <div style={{ marginTop: 8 }}>Đổi ảnh</div>
                            </div>
                        </Upload>
                    </Form.Item>

                    <Form.Item
                        name="description"
                        label="Mô tả"
                        className="col-span-2"
                    >
                        <TextArea rows={3} placeholder="Mô tả chi tiết về món ăn..." />
                    </Form.Item>

                    <Form.Item
                        name="is_active"
                        label="Trạng thái hiển thị"
                        valuePropName="checked"
                        className="col-span-2"
                    >
                        <Switch checkedChildren="Bật" unCheckedChildren="Tắt" />
                    </Form.Item>
                </div>
            </Form>
        </Modal>
    );
};

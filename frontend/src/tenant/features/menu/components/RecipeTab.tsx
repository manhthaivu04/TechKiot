import { useState, useMemo } from 'react';
import { Table, Button, Typography, Spin, Empty } from 'antd';
import { EditOutlined } from '@ant-design/icons';
import type { ColumnsType } from 'antd/es/table';
import type { ItemIngredient } from '@/types';
import { useItemDetail } from '../services/menu.hooks';
import { RecipeEditModal } from './RecipeEditModal';

const { Text, Title } = Typography;

interface RecipeTabProps {
    itemId: string;
}

export const RecipeTab = ({ itemId }: RecipeTabProps) => {
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const { data: itemDetailData, isLoading } = useItemDetail(itemId);

    const item = itemDetailData?.data;
    const ingredients = useMemo(() => item?.ingredients || [], [item?.ingredients]);

    const totalCost = useMemo(() => {
        return ingredients.reduce((sum, ing) => {
            const qty = parseFloat(ing.pivot.quantity) || 0;
            const price = parseFloat(ing.cost_price) || 0;
            return sum + qty * price;
        }, 0);
    }, [ingredients]);

    const columns: ColumnsType<ItemIngredient> = [
        {
            title: 'Tên nguyên liệu',
            dataIndex: 'name',
            key: 'name',
            render: (name: string) => <Text strong>{name}</Text>,
        },
        {
            title: 'Đơn vị',
            dataIndex: 'unit',
            key: 'unit',
            width: 100,
        },
        {
            title: 'Giá vốn NL',
            dataIndex: 'cost_price',
            key: 'cost_price',
            width: 140,
            align: 'right',
            render: (price: string) => (
                <Text>{Number(price).toLocaleString('vi-VN')} đ</Text>
            ),
        },
        {
            title: 'Số lượng',
            key: 'quantity',
            width: 120,
            align: 'right',
            render: (_, record) => (
                <Text>{parseFloat(record.pivot.quantity).toLocaleString('vi-VN', { maximumFractionDigits: 3 })}</Text>
            ),
        },
        {
            title: 'Thành tiền',
            key: 'subtotal',
            width: 150,
            align: 'right',
            render: (_, record) => {
                const subtotal =
                    parseFloat(record.pivot.quantity) * parseFloat(record.cost_price);
                return (
                    <Text strong className="text-blue-600">
                        {subtotal.toLocaleString('vi-VN')} đ
                    </Text>
                );
            },
        },
    ];

    if (isLoading) {
        return (
            <div className="flex justify-center items-center py-12">
                <Spin size="large" />
            </div>
        );
    }

    return (
        <div className="p-4">
            <div className="flex justify-between items-center mb-4">
                <Title level={5} className="!mb-0">
                    Công thức - Định lượng nguyên liệu
                </Title>
                <Button
                    type="primary"
                    icon={<EditOutlined />}
                    onClick={() => setIsEditModalOpen(true)}
                >
                    Chỉnh sửa công thức
                </Button>
            </div>

            {ingredients.length === 0 ? (
                <Empty
                    description="Chưa có công thức nào được thiết lập"
                    className="py-8"
                >
                    <Button
                        type="primary"
                        onClick={() => setIsEditModalOpen(true)}
                    >
                        Thiết lập công thức
                    </Button>
                </Empty>
            ) : (
                <Table
                    columns={columns}
                    dataSource={ingredients}
                    rowKey="id"
                    pagination={false}
                    size="middle"
                    summary={() => (
                        <Table.Summary fixed>
                            <Table.Summary.Row>
                                <Table.Summary.Cell index={0} colSpan={4} align="right">
                                    <Text strong>Tổng giá vốn:</Text>
                                </Table.Summary.Cell>
                                <Table.Summary.Cell index={1} align="right">
                                    <Text strong className="text-red-600 text-base">
                                        {totalCost.toLocaleString('vi-VN')} đ
                                    </Text>
                                </Table.Summary.Cell>
                            </Table.Summary.Row>
                        </Table.Summary>
                    )}
                />
            )}

            <RecipeEditModal
                open={isEditModalOpen}
                onClose={() => setIsEditModalOpen(false)}
                itemId={itemId}
                currentIngredients={ingredients}
            />
        </div>
    );
};

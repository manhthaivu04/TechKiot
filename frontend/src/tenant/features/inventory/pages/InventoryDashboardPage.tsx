import { useState, useMemo } from 'react';
import { Typography, Tabs, Select, Statistic, Card, Row, Col } from 'antd';
import { DatabaseOutlined, WarningOutlined } from '@ant-design/icons';
import { InventoryTable } from '../components/InventoryTable';
import { TransactionTable } from '../components/TransactionTable';
import { useWarehouses } from '../services/warehouse.hooks';
import { useInventory } from '../services/inventory.hooks';
import { useIngredients } from '@/tenant/features/menu/services/menu.hooks';

const { Title, Text } = Typography;

export const InventoryDashboardPage = () => {
    const [activeTab, setActiveTab] = useState('inventory');
    const [warehouseId, setWarehouseId] = useState<string | undefined>(undefined);
    const [transactionItemId, setTransactionItemId] = useState<string | undefined>(undefined);

    // --- Queries for Filters & Stats ---
    const { data: warehouseData, isLoading: isLoadingWarehouses } = useWarehouses();
    const { data: inventoryData } = useInventory({ warehouse_id: warehouseId });
    const { data: ingredientData, isLoading: isLoadingIngredients } = useIngredients();

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

    // --- Statistics ---
    const totalItems = ingredientOptions.length;
    const outOfStockCount = useMemo(() => {
        if (!inventoryData?.data) return 0;
        return inventoryData.data.filter(item => parseFloat(item.quantity) <= 0).length;
    }, [inventoryData?.data]);

    // --- Handlers ---
    const handleViewHistory = (itemId: string) => {
        setTransactionItemId(itemId);
        setActiveTab('history');
    };

    return (
        <div>
            {/* Page Header */}
            <div className="mb-6">
                <Title level={3} style={{ margin: 0, color: '#1f2937' }}>
                    Tồn kho & Lịch sử biến động
                </Title>
                <Text type="secondary" style={{ fontSize: 14 }}>
                    Quản lý số lượng tồn kho hiện tại và theo dõi sổ kho
                </Text>
            </div>

            {/* Statistics Cards */}
            <Row gutter={16} className="mb-6">
                <Col xs={24} sm={12} md={8}>
                    <Card bordered={false} className="shadow-sm rounded-xl">
                        <Statistic
                            title="Nguyên liệu quản lý"
                            value={totalItems}
                            prefix={<DatabaseOutlined className="text-blue-500 mr-2" />}
                            valueStyle={{ color: '#1f2937', fontWeight: 600 }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} md={8}>
                    <Card bordered={false} className="shadow-sm rounded-xl">
                        <Statistic
                            title="Nguyên liệu hết hàng"
                            value={outOfStockCount}
                            prefix={<WarningOutlined className={outOfStockCount > 0 ? "text-red-500 mr-2" : "text-green-500 mr-2"} />}
                            valueStyle={{ color: outOfStockCount > 0 ? '#ef4444' : '#10b981', fontWeight: 600 }}
                        />
                    </Card>
                </Col>
            </Row>

            {/* Global Filters */}
            <Card bordered={false} className="shadow-sm rounded-xl mb-6 py-2">
                <div className="flex flex-wrap gap-4 items-center">
                    <div className="flex items-center gap-2">
                        <Text strong>Kho chứa:</Text>
                        <Select
                            placeholder="Tất cả kho"
                            allowClear
                            value={warehouseId}
                            onChange={setWarehouseId}
                            loading={isLoadingWarehouses}
                            options={warehouseOptions}
                            style={{ width: 220 }}
                        />
                    </div>

                    {activeTab === 'history' && (
                        <div className="flex items-center gap-2">
                            <Text strong>Nguyên liệu:</Text>
                            <Select
                                placeholder="Tất cả nguyên liệu"
                                allowClear
                                value={transactionItemId}
                                onChange={setTransactionItemId}
                                loading={isLoadingIngredients}
                                options={ingredientOptions}
                                showSearch
                                filterOption={(input, option) =>
                                    (option?.label ?? '')
                                        .toString()
                                        .toLowerCase()
                                        .includes(input.toLowerCase())
                                }
                                style={{ width: 250 }}
                            />
                        </div>
                    )}
                </div>
            </Card>

            {/* Main Tabs */}
            <div className="bg-white rounded-xl shadow-sm p-6">
                <Tabs
                    activeKey={activeTab}
                    onChange={setActiveTab}
                    items={[
                        {
                            key: 'inventory',
                            label: 'Tồn kho hiện tại',
                            children: (
                                <InventoryTable
                                    warehouseId={warehouseId}
                                    onViewHistory={handleViewHistory}
                                />
                            ),
                        },
                        {
                            key: 'history',
                            label: 'Lịch sử biến động',
                            children: (
                                <TransactionTable
                                    warehouseId={warehouseId}
                                    itemId={transactionItemId}
                                />
                            ),
                        },
                    ]}
                />
            </div>
        </div>
    );
};

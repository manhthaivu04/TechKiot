import { Drawer, Tabs } from 'antd';
import { ProductGrid } from './ProductGrid';
import { CartPanel } from './CartPanel';
import { TableMap } from './TableMap';
import { useState } from 'react';
import { usePosCartStore } from '@/store/posCartStore';
import { OrderDetailModal } from './OrderDetailModal';
import type { Order } from '@/types';
import { ShoppingOutlined, AppstoreOutlined } from '@ant-design/icons';
import { useFeatureFlag } from '@/auth/hooks/useFeatureFlag';
import { FeatureCode } from '@/types';

interface Props {
    open: boolean;
    onClose: () => void;
}

export const POSDrawer = ({ open, onClose }: Props) => {
    const [activeTab, setActiveTab] = useState('takeaway');
    const [viewOrder, setViewOrder] = useState<Order | null>(null);
    const hasTableManagement = useFeatureFlag(FeatureCode.TABLE_MANAGEMENT);
    
    const setServiceType = usePosCartStore(s => s.setServiceType);
    const setSelectedTableId = usePosCartStore(s => s.setSelectedTableId);
    const selectedTableId = usePosCartStore(s => s.selectedTableId);
    const clearCart = usePosCartStore(s => s.clearCart);

    const handleClose = () => {
        clearCart();
        onClose();
    };

    const handleSelectTable = (tableId: string) => {
        setSelectedTableId(tableId);
        setActiveTab('menu'); // Chuyển sang tab chọn món
    };

    return (
        <>
            <Drawer
                title={
                    <div className="flex items-center gap-2">
                        <ShoppingOutlined className="text-emerald-600 text-xl" />
                        <span className="font-bold text-lg text-gray-800">
                            Bán hàng (POS)
                        </span>
                    </div>
                }
                placement="right"
                width="100%"
                onClose={handleClose}
                open={open}
                destroyOnClose
                bodyStyle={{ padding: 0, overflow: 'hidden' }}
                headerStyle={{ borderBottom: '1px solid #f0f0f0' }}
            >
                <div className="flex h-full overflow-hidden bg-gray-50">
                    {/* Main content area */}
                    <div className="flex-1 flex flex-col overflow-hidden">
                        <Tabs
                            activeKey={activeTab}
                            onChange={(key) => {
                                setActiveTab(key);
                                if (key === 'takeaway') {
                                    setServiceType('TAKEAWAY');
                                    setSelectedTableId(null);
                                } else if (key === 'dine_in') {
                                    setServiceType('DINE_IN');
                                }
                            }}
                            className="bg-white px-4 pt-2 shadow-sm z-10"
                            items={[
                                {
                                    key: 'takeaway',
                                    label: <span className="font-semibold"><ShoppingOutlined /> Mang đi (Takeaway)</span>,
                                },
                                ...(hasTableManagement ? [{
                                    key: 'dine_in',
                                    label: <span className="font-semibold"><AppstoreOutlined /> Tại bàn (Dine-in)</span>,
                                }] : []),
                                // Hidden tab for ordering when a table is selected
                                ...(selectedTableId && hasTableManagement ? [{
                                    key: 'menu',
                                    label: <span className="font-semibold text-emerald-600">Thực đơn cho bàn</span>,
                                }] : []),
                            ]}
                        />
                        
                        <div className="flex-1 overflow-hidden relative">
                            {activeTab === 'dine_in' && (
                                <TableMap 
                                    onSelectTable={handleSelectTable} 
                                    onViewOrder={setViewOrder} 
                                />
                            )}
                            {(activeTab === 'takeaway' || activeTab === 'menu') && (
                                <ProductGrid />
                            )}
                        </div>
                    </div>

                    {/* Cart panel */}
                    <div className="w-[360px] border-l border-gray-200 bg-white shadow-xl z-20 flex-shrink-0">
                        <CartPanel />
                    </div>
                </div>
            </Drawer>

            {/* Chi tiết đơn hàng khi click vào bàn đang có khách */}
            {viewOrder && (
                <OrderDetailModal
                    open={!!viewOrder}
                    orderId={viewOrder.id}
                    onClose={() => setViewOrder(null)}
                />
            )}
        </>
    );
};

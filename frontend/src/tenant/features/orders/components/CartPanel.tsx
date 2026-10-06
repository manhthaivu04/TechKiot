import { useState } from 'react';
import { Button, Divider, Empty, Input, message, Tooltip } from 'antd';
import {
    DeleteOutlined,
    MinusOutlined,
    PlusOutlined,
    ShoppingCartOutlined,
    ClearOutlined,
    UserOutlined,
    PhoneOutlined,
} from '@ant-design/icons';
import { usePosCartStore } from '@/store/posCartStore';
import { useCreateOrder } from '../services/order.hooks';
import { MOCK_TABLES } from '@/constants/mockTables';

const formatCurrency = (val: number) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);

export const CartPanel = () => {
    const items = usePosCartStore((s) => s.items);
    const subtotalFn = usePosCartStore((s) => s.subtotal);
    const updateQuantity = usePosCartStore((s) => s.updateQuantity);
    const removeItem = usePosCartStore((s) => s.removeItem);
    const clearCart = usePosCartStore((s) => s.clearCart);
    const serviceType = usePosCartStore((s) => s.serviceType);
    const selectedTableId = usePosCartStore((s) => s.selectedTableId);

    const subtotal = subtotalFn();
    const finalAmount = subtotal; // Có thể thêm thuế/giảm giá sau

    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');

    const { mutate: createOrder, isPending } = useCreateOrder();

    const handlePlaceOrder = () => {
        if (items.length === 0) {
            message.warning('Giỏ hàng đang trống. Vui lòng chọn món!');
            return;
        }

        createOrder(
            {
                source_channel: 'cashier',
                service_type: serviceType,
                table_id: selectedTableId || undefined,
                customer_name: customerName || undefined,
                customer_phone: customerPhone || undefined,
                items: items.map((i) => ({
                    item_id: i.item_id,
                    quantity: i.quantity,
                    unit_price: i.sale_price,
                })),
            },
            {
                onSuccess: (res) => {
                    message.success(`Đã tạo đơn ${res.data.order_code} thành công!`);
                    setCustomerName('');
                    setCustomerPhone('');
                    clearCart();
                },
                onError: (error: any) => {
                    const errorMsg = error.response?.data?.message || 'Tạo đơn thất bại. Vui lòng thử lại.';
                    message.error(errorMsg);
                },
            }
        );
    };

    return (
        <div className="flex flex-col h-full bg-white">
            {/* Header */}
            <div className={`px-4 py-3 border-b border-gray-100 flex items-center justify-between flex-shrink-0 text-white ${serviceType === 'DINE_IN' ? 'bg-amber-600' : 'bg-emerald-700'}`}>
                <div className="flex items-center gap-2">
                    <ShoppingCartOutlined className="text-lg" />
                    <span className="font-bold text-base">
                        {serviceType === 'DINE_IN' && selectedTableId
                            ? MOCK_TABLES.find(t => t.id === selectedTableId)?.name || 'Tại bàn'
                            : 'Hoá đơn mang đi'}
                    </span>
                    {items.length > 0 && (
                        <span className={`text-xs font-bold rounded-full px-2 py-0.5 ml-1 bg-white ${serviceType === 'DINE_IN' ? 'text-amber-700' : 'text-emerald-700'}`}>
                            {items.reduce((s, i) => s + i.quantity, 0)}
                        </span>
                    )}
                </div>
                {items.length > 0 && (
                    <Tooltip title="Xoá tất cả">
                        <Button
                            type="text"
                            size="small"
                            icon={<ClearOutlined />}
                            className="!text-white hover:!text-red-300"
                            onClick={clearCart}
                        />
                    </Tooltip>
                )}
            </div>

            {/* Customer info */}
            <div className="px-4 py-3 bg-gray-50 border-b border-gray-100 flex-shrink-0">
                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    Thông tin khách (tuỳ chọn)
                </p>
                <Input
                    prefix={<UserOutlined className="text-gray-400" />}
                    placeholder="Tên khách hàng"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    className="mb-2 rounded-lg"
                    size="small"
                />
                <Input
                    prefix={<PhoneOutlined className="text-gray-400" />}
                    placeholder="Số điện thoại"
                    value={customerPhone}
                    onChange={(e) => setCustomerPhone(e.target.value)}
                    className="rounded-lg"
                    size="small"
                />
            </div>

            {/* Cart items */}
            <div className="flex-1 overflow-y-auto">
                {items.length === 0 ? (
                    <Empty
                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                        description={
                            <span className="text-gray-400 text-sm">
                                Chọn món từ danh sách bên trái
                            </span>
                        }
                        className="mt-10"
                    />
                ) : (
                    <div className="divide-y divide-gray-50">
                        {items.map((item, index) => (
                            <div key={item.item_id} className="px-4 py-3 hover:bg-gray-50 transition-colors">
                                <div className="flex items-start gap-3">
                                    {/* Index */}
                                    <span className="text-xs text-gray-400 font-medium pt-0.5 flex-shrink-0 w-4">
                                        {index + 1}
                                    </span>

                                    {/* Name + Controls */}
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-800 leading-tight line-clamp-2">
                                            {item.name}
                                        </p>
                                        <p className="text-xs text-emerald-600 mt-0.5">
                                            {formatCurrency(item.sale_price)} / {' '}
                                            <span className="text-gray-400">phần</span>
                                        </p>

                                        {/* Quantity controls */}
                                        <div className="flex items-center gap-2 mt-2">
                                            <button
                                                onClick={() =>
                                                    updateQuantity(item.item_id, item.quantity - 1)
                                                }
                                                className="w-6 h-6 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 hover:border-red-400 hover:text-red-500 transition-colors"
                                            >
                                                <MinusOutlined style={{ fontSize: 10 }} />
                                            </button>
                                            <span className="font-bold text-gray-800 min-w-[24px] text-center text-sm">
                                                {item.quantity}
                                            </span>
                                            <button
                                                onClick={() =>
                                                    updateQuantity(item.item_id, item.quantity + 1)
                                                }
                                                className="w-6 h-6 rounded-full border border-emerald-400 flex items-center justify-center text-emerald-600 hover:bg-emerald-50 transition-colors"
                                            >
                                                <PlusOutlined style={{ fontSize: 10 }} />
                                            </button>
                                        </div>
                                    </div>

                                    {/* Subtotal + Delete */}
                                    <div className="flex flex-col items-end gap-2 flex-shrink-0">
                                        <p className="text-sm font-bold text-gray-800">
                                            {formatCurrency(item.sale_price * item.quantity)}
                                        </p>
                                        <button
                                            onClick={() => removeItem(item.item_id)}
                                            className="text-gray-300 hover:text-red-500 transition-colors"
                                        >
                                            <DeleteOutlined style={{ fontSize: 14 }} />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Footer summary + checkout */}
            <div className="px-4 py-4 border-t border-gray-200 flex-shrink-0 bg-white shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
                <div className="space-y-1.5 mb-3">
                    <div className="flex justify-between text-sm text-gray-600">
                        <span>Tạm tính ({items.reduce((s, i) => s + i.quantity, 0)} món)</span>
                        <span>{formatCurrency(subtotal)}</span>
                    </div>
                    <div className="flex justify-between text-sm text-gray-400">
                        <span>Giảm giá</span>
                        <span>0 ₫</span>
                    </div>
                    <Divider className="!my-2" />
                    <div className="flex justify-between items-center">
                        <span className="font-bold text-gray-800">Tổng cộng</span>
                        <span className="text-xl font-extrabold text-emerald-600">
                            {formatCurrency(finalAmount)}
                        </span>
                    </div>
                </div>

                <Button
                    block
                    type="primary"
                    size="large"
                    className={serviceType === 'DINE_IN' 
                        ? "!bg-amber-600 hover:!bg-amber-700 !border-amber-600 !h-12 font-bold !text-base"
                        : "!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600 !h-12 font-bold !text-base"
                    }
                    onClick={handlePlaceOrder}
                    loading={isPending}
                    disabled={items.length === 0 || (serviceType === 'DINE_IN' && !selectedTableId)}
                    icon={<ShoppingCartOutlined />}
                >
                    {isPending ? 'Đang tạo đơn...' : (serviceType === 'DINE_IN' && !selectedTableId ? 'Chưa chọn bàn' : 'Xuất hoá đơn')}
                </Button>
            </div>
        </div>
    );
};

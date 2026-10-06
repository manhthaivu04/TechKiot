import { useState } from 'react';
import { Drawer, Button, Badge, Divider, InputNumber, message, Empty, Spin } from 'antd';
import {
    ShoppingCartOutlined,
    PlusOutlined,
    MinusOutlined,
    DeleteOutlined,
    SendOutlined,
    CloseOutlined,
} from '@ant-design/icons';
import { useItemGroups, useItems } from '@/tenant/features/menu/services/menu.hooks';
import { useAddOrderItems } from '../services/order.hooks';
import type { Item, ItemGroup } from '@/types';
import { Input, Tag } from 'antd';
import { SearchOutlined } from '@ant-design/icons';

// ─── Cart state local (không dùng posCartStore để tránh xung đột với POS) ───

interface CartItem {
    item: Item;
    quantity: number;
    note: string;
}

const formatCurrency = (val: string | number) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(
        typeof val === 'string' ? parseFloat(val) : val
    );

// ─── Mini ProductGrid ─────────────────────────────────────────────────────────

interface MiniGridProps {
    onAddItem: (item: Item) => void;
    cartMap: Record<string, number>;
}

const MiniProductGrid = ({ onAddItem, cartMap }: MiniGridProps) => {
    const [selectedGroup, setSelectedGroup] = useState<string | null>(null);
    const [search, setSearch] = useState('');

    const { data: groupsData, isLoading: groupsLoading } = useItemGroups();
    const { data: itemsData, isLoading: itemsLoading } = useItems();

    const groups: ItemGroup[] = groupsData?.data ?? [];
    const allItems: Item[] = itemsData?.data ?? [];

    const filteredItems = allItems.filter((item) => {
        const matchGroup = selectedGroup ? item.item_group_id === selectedGroup : true;
        const matchSearch = item.name.toLowerCase().includes(search.toLowerCase());
        return matchGroup && matchSearch && item.is_active;
    });

    if (groupsLoading || itemsLoading) {
        return (
            <div className="flex items-center justify-center h-full py-16">
                <Spin tip="Đang tải thực đơn..." />
            </div>
        );
    }

    return (
        <div className="flex flex-col h-full">
            {/* Search */}
            <div className="p-3 border-b border-gray-100">
                <Input
                    prefix={<SearchOutlined className="text-gray-400" />}
                    placeholder="Tìm kiếm món ăn..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    allowClear
                />
            </div>

            {/* Group filter */}
            <div
                className="flex items-center gap-2 px-3 py-2 border-b border-gray-100 overflow-x-auto flex-shrink-0"
                style={{ scrollbarWidth: 'none' }}
            >
                <Tag
                    className={`cursor-pointer select-none rounded-full px-3 py-0.5 text-sm font-medium transition-all ${
                        !selectedGroup
                            ? 'bg-emerald-600 text-white border-emerald-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-400'
                    }`}
                    onClick={() => setSelectedGroup(null)}
                >
                    Tất cả
                </Tag>
                {groups
                    .filter((g) => g.is_active)
                    .map((group) => (
                        <Tag
                            key={group.id}
                            className={`cursor-pointer select-none rounded-full px-3 py-0.5 text-sm font-medium transition-all whitespace-nowrap ${
                                selectedGroup === group.id
                                    ? 'bg-emerald-600 text-white border-emerald-600'
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-400'
                            }`}
                            onClick={() =>
                                setSelectedGroup(selectedGroup === group.id ? null : group.id)
                            }
                        >
                            {group.name}
                        </Tag>
                    ))}
            </div>

            {/* Items grid */}
            <div className="flex-1 overflow-y-auto p-3">
                {filteredItems.length === 0 ? (
                    <Empty description="Không có món nào" className="mt-10" />
                ) : (
                    <div
                        className="grid gap-3"
                        style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(130px, 1fr))' }}
                    >
                        {filteredItems.map((item) => {
                            const qty = cartMap[item.id] ?? 0;
                            const isOutOfStock = item.availability_status !== 'IN_STOCK';
                            return (
                                <div
                                    key={item.id}
                                    className={`relative bg-white rounded-xl overflow-hidden shadow-sm border transition-all duration-200 group ${
                                        isOutOfStock
                                            ? 'opacity-50 cursor-not-allowed'
                                            : 'cursor-pointer hover:shadow-md hover:border-emerald-400 hover:-translate-y-0.5'
                                    }`}
                                    onClick={() => !isOutOfStock && onAddItem(item)}
                                >
                                    {qty > 0 && (
                                        <div className="absolute top-2 right-2 z-10">
                                            <Badge
                                                count={qty}
                                                style={{
                                                    backgroundColor: '#10b981',
                                                    minWidth: 22,
                                                    height: 22,
                                                    lineHeight: '22px',
                                                    fontSize: 13,
                                                    fontWeight: 700,
                                                }}
                                            />
                                        </div>
                                    )}
                                    <div
                                        className="relative bg-gray-50 overflow-hidden"
                                        style={{ height: 100 }}
                                    >
                                        {item.image_url ? (
                                            <img
                                                src={item.image_url}
                                                alt={item.name}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-3xl">
                                                🍽️
                                            </div>
                                        )}
                                        {isOutOfStock && (
                                            <div className="absolute inset-0 bg-black/40 flex items-center justify-center">
                                                <span className="text-white text-xs font-semibold bg-red-500 px-2 py-0.5 rounded-full">
                                                    Hết món
                                                </span>
                                            </div>
                                        )}
                                        {!isOutOfStock && (
                                            <div className="absolute inset-0 bg-emerald-600/0 group-hover:bg-emerald-600/10 flex items-center justify-center">
                                                <div className="opacity-0 group-hover:opacity-100 transition-opacity bg-emerald-600 text-white rounded-full p-1.5 shadow-lg">
                                                    <PlusOutlined style={{ fontSize: 14 }} />
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    <div className="p-2">
                                        <p className="text-sm font-semibold text-gray-800 line-clamp-2 leading-tight mb-1">
                                            {item.name}
                                        </p>
                                        <p className="text-emerald-600 font-bold text-sm">
                                            {formatCurrency(item.sale_price)}
                                        </p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
};

// ─── Main Drawer ──────────────────────────────────────────────────────────────

interface Props {
    open: boolean;
    orderId: string;
    orderCode: string;
    onClose: () => void;
}

export const AddItemsDrawer = ({ open, orderId, orderCode, onClose }: Props) => {
    const [cart, setCart] = useState<CartItem[]>([]);
    const { mutate: addItems, isPending } = useAddOrderItems();

    const cartMap = Object.fromEntries(cart.map((c) => [c.item.id, c.quantity]));
    const total = cart.reduce(
        (sum, c) => sum + parseFloat(c.item.sale_price as string) * c.quantity,
        0
    );

    const handleAddItem = (item: Item) => {
        setCart((prev) => {
            const existing = prev.find((c) => c.item.id === item.id);
            if (existing) {
                return prev.map((c) =>
                    c.item.id === item.id ? { ...c, quantity: c.quantity + 1 } : c
                );
            }
            return [...prev, { item, quantity: 1, note: '' }];
        });
    };

    const handleQtyChange = (itemId: string, qty: number | null) => {
        if (!qty || qty <= 0) {
            setCart((prev) => prev.filter((c) => c.item.id !== itemId));
        } else {
            setCart((prev) =>
                prev.map((c) => (c.item.id === itemId ? { ...c, quantity: qty } : c))
            );
        }
    };

    const handleNoteChange = (itemId: string, note: string) => {
        setCart((prev) => prev.map((c) => (c.item.id === itemId ? { ...c, note } : c)));
    };

    const handleRemove = (itemId: string) => {
        setCart((prev) => prev.filter((c) => c.item.id !== itemId));
    };

    const handleSubmit = () => {
        if (cart.length === 0) return;
        addItems(
            {
                orderId,
                items: cart.map((c) => ({
                    item_id: c.item.id,
                    quantity: c.quantity,
                    note: c.note || undefined,
                })),
            },
            {
                onSuccess: () => {
                    message.success('Gọi thêm món thành công!');
                    setCart([]);
                    onClose();
                },
                onError: (error: any) => {
                    const errorMsg = error.response?.data?.message || 'Không thể gọi thêm món. Vui lòng thử lại.';
                    message.error(errorMsg);
                },
            }
        );
    };

    const handleClose = () => {
        setCart([]);
        onClose();
    };

    return (
        <Drawer
            open={open}
            onClose={handleClose}
            width="100%"
            placement="right"
            destroyOnClose
            bodyStyle={{ padding: 0, display: 'flex', flexDirection: 'column', overflow: 'hidden' }}
            title={
                <div className="flex items-center gap-2">
                    <PlusOutlined className="text-emerald-600" />
                    <span className="font-bold">
                        Gọi thêm món — Đơn #{orderCode}
                    </span>
                    <Badge
                        count={cart.length}
                        style={{ backgroundColor: '#10b981' }}
                        className="ml-1"
                    />
                </div>
            }
            extra={
                <Button
                    type="text"
                    icon={<CloseOutlined />}
                    onClick={handleClose}
                />
            }
        >
            <div className="flex h-full overflow-hidden">
                {/* Product selection (left) */}
                <div className="flex-1 flex flex-col overflow-hidden border-r border-gray-200">
                    <MiniProductGrid onAddItem={handleAddItem} cartMap={cartMap} />
                </div>

                {/* Cart panel (right) */}
                <div className="w-[320px] flex flex-col bg-white shadow-xl">
                    <div className="flex items-center gap-2 px-4 py-3 border-b border-gray-100 bg-gray-50">
                        <ShoppingCartOutlined className="text-emerald-600 text-lg" />
                        <span className="font-bold text-gray-700">
                            Món đã chọn ({cart.length})
                        </span>
                    </div>

                    {cart.length === 0 ? (
                        <div className="flex-1 flex items-center justify-center">
                            <Empty
                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                                description="Chọn món từ danh sách"
                            />
                        </div>
                    ) : (
                        <div className="flex-1 overflow-y-auto p-3 space-y-3">
                            {cart.map((cartItem) => (
                                <div
                                    key={cartItem.item.id}
                                    className="bg-white rounded-xl border border-gray-100 shadow-sm p-3 space-y-2"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="flex-1 min-w-0">
                                            <p className="font-semibold text-gray-800 text-sm leading-tight line-clamp-2">
                                                {cartItem.item.name}
                                            </p>
                                            <p className="text-emerald-600 font-bold text-sm">
                                                {formatCurrency(cartItem.item.sale_price)}
                                            </p>
                                        </div>
                                        <Button
                                            type="text"
                                            danger
                                            size="small"
                                            icon={<DeleteOutlined />}
                                            onClick={() => handleRemove(cartItem.item.id)}
                                        />
                                    </div>

                                    {/* Quantity controls */}
                                    <div className="flex items-center gap-2">
                                        <Button
                                            size="small"
                                            icon={<MinusOutlined />}
                                            onClick={() =>
                                                handleQtyChange(
                                                    cartItem.item.id,
                                                    cartItem.quantity - 1
                                                )
                                            }
                                        />
                                        <InputNumber
                                            size="small"
                                            min={1}
                                            max={99}
                                            value={cartItem.quantity}
                                            onChange={(v) =>
                                                handleQtyChange(cartItem.item.id, v)
                                            }
                                            className="w-14 text-center"
                                            controls={false}
                                        />
                                        <Button
                                            size="small"
                                            icon={<PlusOutlined />}
                                            onClick={() =>
                                                handleQtyChange(
                                                    cartItem.item.id,
                                                    cartItem.quantity + 1
                                                )
                                            }
                                        />
                                        <span className="ml-auto text-sm font-bold text-gray-700">
                                            {formatCurrency(
                                                parseFloat(cartItem.item.sale_price as string) *
                                                    cartItem.quantity
                                            )}
                                        </span>
                                    </div>

                                    {/* Note */}
                                    <Input
                                        size="small"
                                        placeholder="Ghi chú cho bếp..."
                                        value={cartItem.note}
                                        onChange={(e) =>
                                            handleNoteChange(cartItem.item.id, e.target.value)
                                        }
                                    />
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Footer */}
                    {cart.length > 0 && (
                        <div className="border-t border-gray-200 p-4 bg-gray-50 space-y-3">
                            <Divider className="!my-0" />
                            <div className="flex justify-between items-center">
                                <span className="text-gray-600 font-medium">Tổng cộng</span>
                                <span className="text-xl font-black text-emerald-600">
                                    {formatCurrency(total)}
                                </span>
                            </div>
                            <Button
                                type="primary"
                                size="large"
                                block
                                icon={<SendOutlined />}
                                loading={isPending}
                                onClick={handleSubmit}
                                className="!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600 !font-bold !h-12"
                            >
                                Gửi Bếp ({cart.length} món)
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </Drawer>
    );
};

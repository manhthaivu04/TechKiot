import { useState } from 'react';
import { Input, Spin, Empty, Badge, Tag } from 'antd';
import { SearchOutlined, PlusOutlined } from '@ant-design/icons';
import { useItemGroups, useItems } from '@/tenant/features/menu/services/menu.hooks';
import { usePosCartStore } from '@/store/posCartStore';
import type { Item, ItemGroup } from '@/types';

const formatCurrency = (val: string | number) => {
    const num = typeof val === 'string' ? parseFloat(val) : val;
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(num);
};

const ItemCard = ({ item }: { item: Item }) => {
    const addItem = usePosCartStore((s) => s.addItem);
    const cartItems = usePosCartStore((s) => s.items);
    const cartQty = cartItems.find((i) => i.item_id === item.id)?.quantity ?? 0;
    const isOutOfStock = item.availability_status !== 'IN_STOCK';

    return (
        <div
            className={`relative bg-white rounded-xl overflow-hidden shadow-sm border transition-all duration-200 group ${
                isOutOfStock
                    ? 'opacity-50 cursor-not-allowed'
                    : 'cursor-pointer hover:shadow-md hover:border-emerald-400 hover:-translate-y-0.5'
            }`}
            onClick={() => !isOutOfStock && addItem(item)}
        >
            {/* Quantity badge */}
            {cartQty > 0 && (
                <div className="absolute top-2 right-2 z-10">
                    <Badge
                        count={cartQty}
                        style={{ backgroundColor: '#10b981', minWidth: 22, height: 22, lineHeight: '22px', fontSize: 13, fontWeight: 700 }}
                    />
                </div>
            )}

            {/* Image */}
            <div className="relative bg-gray-50 overflow-hidden" style={{ height: 110 }}>
                {item.image_url ? (
                    <img
                        src={item.image_url}
                        alt={item.name}
                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-4xl">
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
                {/* Hover overlay */}
                {!isOutOfStock && (
                    <div className="absolute inset-0 bg-emerald-600/0 group-hover:bg-emerald-600/10 transition-colors duration-200 flex items-center justify-center">
                        <div className="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-emerald-600 text-white rounded-full p-1.5 shadow-lg">
                            <PlusOutlined style={{ fontSize: 14 }} />
                        </div>
                    </div>
                )}
            </div>

            {/* Info */}
            <div className="p-2.5">
                <p className="text-sm font-semibold text-gray-800 line-clamp-2 leading-tight mb-1">
                    {item.name}
                </p>
                <p className="text-emerald-600 font-bold text-sm">
                    {formatCurrency(item.sale_price)}
                </p>
            </div>
        </div>
    );
};

export const ProductGrid = () => {
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
            <div className="flex items-center justify-center h-full">
                <Spin size="large" tip="Đang tải thực đơn..." />
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
                    className="rounded-lg"
                    allowClear
                />
            </div>

            {/* Group filter tabs */}
            <div className="flex items-center gap-2 px-3 py-2 border-b border-gray-100 overflow-x-auto flex-shrink-0" style={{ scrollbarWidth: 'none' }}>
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
                {groups.filter((g) => g.is_active).map((group) => (
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

            {/* Grid */}
            <div className="flex-1 overflow-y-auto p-3">
                {filteredItems.length === 0 ? (
                    <Empty description="Không có món nào" className="mt-10" />
                ) : (
                    <div
                        className="grid gap-3"
                        style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(140px, 1fr))' }}
                    >
                        {filteredItems.map((item) => (
                            <ItemCard key={item.id} item={item} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

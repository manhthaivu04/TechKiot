import { useState, useEffect, useCallback } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import { MapPin, X, UtensilsCrossed } from 'lucide-react';

import { fetchPublicMenu, placeOrder, fetchOrderStatus, addOrderItems } from '../services/menuService';
import { useCartStore } from '@/store/cartStore';
import { useQrSessionStore } from '@/store/useQrSessionStore';
import type { PublicItem, QrType } from '@/types/public-menu';

import { SplashScreen, MenuItemSkeleton } from '../components/SplashScreen';
import { CategoryNav } from '../components/CategoryNav';
import { MenuItemCard } from '../components/MenuItemCard';
import { AddToCartModal } from '../components/AddToCartModal';
import { CartSheet } from '../components/CartSheet';
import { FloatingCartBar } from '../components/FloatingCartBar';

export const MenuPage = () => {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const public_token = searchParams.get('public_token') || '';
    const qrType = (searchParams.get('type') || 'qr_static') as QrType;
    const isAddingToOrder = searchParams.get('action') === 'add_items';

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [restaurantName, setRestaurantName] = useState<string | undefined>();
    const [restaurantData, setRestaurantData] = useState<{ name?: string; address?: string; banner_url?: string | null } | undefined>();
    const [itemGroups, setItemGroups] = useState<{ group_id: string; group_name: string; display_order: number; items: PublicItem[] }[]>([]);

    const [activeGroupId, setActiveGroupId] = useState<string | null>(null);
    const [selectedItem, setSelectedItem] = useState<PublicItem | null>(null);
    const [cartOpen, setCartOpen] = useState(false);
    const [isPlacingOrder, setIsPlacingOrder] = useState(false);

    const { items: cartItems, clearCart, setRestaurantId } = useCartStore();

    // QR Session store — lưu table info + active_order
    const { table, activeOrder, setSession, setActiveOrder } = useQrSessionStore();
    const isTableOrder = qrType === 'qr_table';

    // ---- Load menu ----
    useEffect(() => {
        if (!public_token) {
            setError('Mã QR không hợp lệ. Vui lòng quét lại.');
            setLoading(false);
            return;
        }

        const load = async () => {
            // Với qr_static: logic cũ — anti-spam (redirect nếu có active_order_id)
            if (qrType === 'qr_static') {
                const activeOrderId = localStorage.getItem('active_order_id');
                if (activeOrderId && !isAddingToOrder) {
                    try {
                        const order = await fetchOrderStatus(activeOrderId);
                        const terminalStatuses = ['COMPLETED', 'CANCELLED', 'paid', 'cancelled'];
                        const isActive = !terminalStatuses.includes(order.status);
                        if (isActive) {
                            localStorage.setItem('active_order_data', JSON.stringify(order));
                            navigate(`/menu/order-tracking/${activeOrderId}?public_token=${public_token}&type=${qrType}`);
                            return;
                        } else {
                            localStorage.removeItem('active_order_id');
                            localStorage.removeItem('active_order_data');
                        }
                    } catch {
                        localStorage.removeItem('active_order_id');
                        localStorage.removeItem('active_order_data');
                    }
                }
            }

            try {
                const data = await fetchPublicMenu(public_token, qrType);

                setItemGroups(data.item_groups);
                setRestaurantData(data.restaurant);
                setRestaurantName(data.restaurant?.name);

                if (data.restaurant?.id) {
                    setRestaurantId(data.restaurant.id);
                }

                // Lưu session bàn + active_order vào store
                setSession({
                    publicToken: public_token,
                    qrType,
                    table: data.table,
                    activeOrder: data.active_order,
                });

            } catch (err: any) {
                if (err.response?.status === 403) {
                    setError(err.response.data?.message || 'Nhà hàng chưa đăng ký hoặc đã hết hạn tính năng gọi món tại bàn.');
                } else {
                    setError('Không thể tải thực đơn. Vui lòng thử lại.');
                }
            } finally {
                setLoading(false);
            }
        };

        const minSplash = new Promise((resolve) => setTimeout(resolve, 1200));
        Promise.all([load(), minSplash]).then(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [public_token, qrType]);

    // ---- Filter toggle ----
    const handleCategorySelect = useCallback((groupId: string) => {
        setActiveGroupId((prev) => (prev === groupId ? null : groupId));
        window.scrollTo({ top: 180, behavior: 'smooth' });
    }, []);

    // ---- Place order / Add items ----
    const handlePlaceOrder = async (customerName: string, customerPhone: string, note: string) => {
        if (cartItems.length === 0) return;
        setIsPlacingOrder(true);

        try {
            let orderId = '';

            if (isAddingToOrder) {
                // Chế độ gọi thêm qr_static (legacy flow)
                const activeOrderId = localStorage.getItem('active_order_id');
                if (activeOrderId) {
                    const order = await addOrderItems(activeOrderId, {
                        items: cartItems.map((i) => ({ item_id: i.item_id, quantity: i.quantity, note: i.note })),
                    });
                    orderId = order.id;
                }
            } else if (isTableOrder && activeOrder) {
                // ==========================================================
                // Bàn đang có đơn → GỌI THÊM MÓN (Collaborative Ordering)
                // ==========================================================
                const updatedOrder = await addOrderItems(activeOrder.id, {
                    items: cartItems.map((i) => ({ item_id: i.item_id, quantity: i.quantity, note: i.note })),
                });
                orderId = updatedOrder.id;
                // Cập nhật active_order trong store
                setActiveOrder(updatedOrder);
            } else {
                // ==========================================================
                // Tạo đơn mới (bàn mới hoặc qr_static lần đầu)
                // ==========================================================
                const order = await placeOrder({
                    public_token,
                    source_channel: qrType,
                    customer_name: customerName || undefined,
                    customer_phone: customerPhone || undefined,
                    note: note || undefined,
                    items: cartItems.map((i) => ({
                        item_id: i.item_id,
                        quantity: i.quantity,
                        note: i.note,
                    })),
                });
                orderId = order.id;

                if (qrType === 'qr_static') {
                    localStorage.setItem('active_order_id', order.id);
                } else {
                    // qr_table: lưu vào store
                    setActiveOrder(order);
                }
            }

            clearCart();
            setCartOpen(false);
            navigate(`/menu/order-tracking/${orderId}?public_token=${public_token}&type=${qrType}`);
        } catch (err: any) {
            console.error('[MenuPage] Lỗi đặt đơn:', err);
            const errorMsg = err.response?.data?.message || 'Không thể gửi đơn. Vui lòng thử lại.';
            alert(errorMsg);
        } finally {
            setIsPlacingOrder(false);
        }
    };

    // ---- States ----
    if (loading) return <SplashScreen name={restaurantName} />;

    if (error) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center text-center p-8 bg-gray-50">
                <span className="text-6xl mb-4">😕</span>
                <h2 className="text-xl font-bold text-gray-700 mb-2">Ôi, có lỗi xảy ra!</h2>
                <p className="text-gray-500 text-sm">{error}</p>
            </div>
        );
    }

    // isAddingToOrder cho chế độ gọi thêm
    const effectivelyAddingToOrder = isAddingToOrder || (isTableOrder && !!activeOrder);

    return (
        <motion.div
            initial={isAddingToOrder ? { y: '100%' } : { opacity: 0 }}
            animate={isAddingToOrder ? { y: 0 } : { opacity: 1 }}
            exit={isAddingToOrder ? { y: '100%' } : { opacity: 0 }}
            transition={{ type: 'spring', damping: 25, stiffness: 200 }}
            className={`min-h-screen bg-gray-50 pb-32 ${isAddingToOrder ? 'fixed inset-0 z-50 overflow-y-auto' : ''}`}
        >
            {/* ---- Close button for Add Items mode ---- */}
            {isAddingToOrder && (
                <button
                    onClick={() => navigate(-1)}
                    className="absolute top-4 right-4 z-50 w-10 h-10 bg-black/40 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-black/60 transition-colors"
                >
                    <X size={20} />
                </button>
            )}

            {/* ---- Table Banner (chỉ hiển thị khi qr_table và có thông tin bàn) ---- */}
            <AnimatePresence>
                {isTableOrder && table && (
                    <motion.div
                        initial={{ opacity: 0, y: -20 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -20 }}
                        className="sticky top-0 z-40 bg-amber-500 text-white px-4 py-2 flex items-center justify-center gap-2 shadow-md text-sm font-semibold"
                    >
                        <UtensilsCrossed size={15} />
                        <span>Bàn: <strong>{table.name}</strong></span>
                        {table.area && (
                            <span className="opacity-80">· {table.area.name}</span>
                        )}
                        {activeOrder && (
                            <span className="ml-2 bg-white/20 rounded-full px-2 py-0.5 text-xs">
                                Đang có đơn #{activeOrder.id?.slice(-6).toUpperCase()}
                            </span>
                        )}
                    </motion.div>
                )}
            </AnimatePresence>

            {/* ---- Hero Banner ---- */}
            <div className="relative h-52 overflow-hidden">
                {restaurantData?.banner_url ? (
                    <img
                        src={restaurantData.banner_url}
                        alt={restaurantData.name}
                        className="w-full h-full object-cover"
                    />
                ) : (
                    <div className="w-full h-full bg-gradient-to-br from-orange-400 via-orange-500 to-red-600" />
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />

                {/* Restaurant info overlay */}
                <div className="absolute bottom-5 left-4 right-4 text-white">
                    <motion.h1
                        initial={{ y: 20, opacity: 0 }}
                        animate={{ y: 0, opacity: 1 }}
                        className="text-2xl font-black tracking-tight drop-shadow-lg"
                    >
                        {restaurantData?.name || 'Nhà hàng KiotTay'}
                    </motion.h1>
                    {restaurantData?.address && (
                        <motion.p
                            initial={{ y: 15, opacity: 0 }}
                            animate={{ y: 0, opacity: 1 }}
                            transition={{ delay: 0.1 }}
                            className="text-white/80 text-sm flex items-center gap-1 mt-0.5"
                        >
                            <MapPin size={12} />
                            {restaurantData.address}
                        </motion.p>
                    )}
                </div>
            </div>

            {/* ---- Sticky Category Nav ---- */}
            <div className="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-100 shadow-sm py-3">
                <CategoryNav
                    groups={itemGroups}
                    activeGroupId={activeGroupId}
                    onSelect={handleCategorySelect}
                />
            </div>

            {/* ---- Menu Groups ---- */}
            <div className="space-y-8 pt-4 px-4">
                {itemGroups
                    ?.filter((group) => activeGroupId === null || group.group_id === activeGroupId)
                    .map((group) => (
                    <div
                        key={group.group_id}
                        id={`group-${group.group_id}`}
                    >
                        {/* Group Header */}
                        <div className="flex items-center gap-3 mb-4">
                            <h2 className="text-lg font-black text-gray-800">{group.group_name}</h2>
                            <div className="flex-1 h-px bg-gradient-to-r from-orange-200 to-transparent" />
                            <span className="text-xs text-gray-400 font-medium bg-gray-100 px-2 py-1 rounded-full">
                                {group.items.length} món
                            </span>
                        </div>

                        {/* Items Grid */}
                        <div className="grid grid-cols-1 gap-3">
                            {group.items
                                .map((item) => (
                                    <MenuItemCard
                                        key={item.id}
                                        item={item}
                                        onAdd={(i) => setSelectedItem(i)}
                                    />
                                ))}

                            {group.items.length === 0 && (
                                <p className="text-gray-400 text-sm py-4 text-center">
                                    Không có món nào trong danh mục này
                                </p>
                            )}
                        </div>
                    </div>
                ))}

                {/* Loading Skeleton */}
                {loading && (
                    <div className="space-y-3">
                        {Array.from({ length: 4 }).map((_, i) => (
                            <MenuItemSkeleton key={i} />
                        ))}
                    </div>
                )}
            </div>

            {/* ---- Modals & Sheets ---- */}
            <AddToCartModal
                item={selectedItem}
                onClose={() => setSelectedItem(null)}
            />

            <CartSheet
                open={cartOpen}
                onClose={() => setCartOpen(false)}
                onPlaceOrder={handlePlaceOrder}
                isPlacingOrder={isPlacingOrder}
                isAddingToOrder={effectivelyAddingToOrder}
                activeOrder={activeOrder}
            />

            <FloatingCartBar
                onClick={() => setCartOpen(true)}
            />
        </motion.div>
    );
};

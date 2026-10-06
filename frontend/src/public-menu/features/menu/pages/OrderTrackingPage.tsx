import { useState, useEffect } from 'react';
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { CheckCircle2, Clock, ChefHat, Utensils, Plus, RefreshCw, Home } from 'lucide-react';
import { getEcho } from '@/lib/echoClient';
import { fetchOrderStatus } from '../services/menuService';
import { formatCurrency } from '@/lib/formatters';
import type { Order } from '@/types/public-menu';
import type { OrderStatus } from '@/types/order';

// Cập nhật status enum theo backend mới (lowercase)
const ORDER_STEPS: { status: OrderStatus; label: string; icon: React.ReactNode; color: string }[] = [
    { status: 'open',    label: 'Chờ xác nhận', icon: <Clock size={20} />,       color: 'text-yellow-500' },
    { status: 'cooking', label: 'Đang nấu',     icon: <ChefHat size={20} />,     color: 'text-orange-500' },
    { status: 'served',  label: 'Sẵn sàng',     icon: <Utensils size={20} />,    color: 'text-green-500'  },
    { status: 'paid',    label: 'Hoàn thành',   icon: <CheckCircle2 size={20} />, color: 'text-emerald-600'},
];

const getStepIndex = (status: OrderStatus): number => {
    // Hỗ trợ cả legacy status cho tương thích ngược nếu cần
    if (status === 'PENDING' || status === 'CONFIRMED') return 0;
    if (status === 'PREPARING') return 1;
    if (status === 'READY') return 2;
    if (status === 'COMPLETED') return 3;

    return ORDER_STEPS.findIndex((s) => s.status === status);
};

export const OrderTrackingPage = () => {
    const { orderId } = useParams<{ orderId: string }>();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const public_token = searchParams.get('public_token') || '';
    const qrType = searchParams.get('type') || 'qr_static';

    const [order, setOrder] = useState<Order | null>(() => {
        try {
            const cached = localStorage.getItem('active_order_data');
            if (cached) {
                const parsed = JSON.parse(cached) as Order;
                // Only pre-populate if it matches the current orderId from URL
                if (parsed.id === orderId) return parsed;
            }
        } catch { /* ignore */ }
        return null;
    });
    const [loading, setLoading] = useState(order === null); // skip skeleton if we have cache
    const [lastRefreshed, setLastRefreshed] = useState(new Date());

    // FIX: Dùng đúng status enum UPPERCASE của backend
    const TERMINAL_STATUSES: OrderStatus[] = ['COMPLETED', 'CANCELLED', 'paid', 'cancelled'];

    const clearActiveOrder = () => {
        localStorage.removeItem('active_order_id');
        localStorage.removeItem('active_order_data');
    };

    const persistOrder = (data: Order) => {
        if (TERMINAL_STATUSES.includes(data.status)) {
            clearActiveOrder();
        } else {
            localStorage.setItem('active_order_id', data.id);
            localStorage.setItem('active_order_data', JSON.stringify(data));
        }
    };

    const loadOrder = async () => {
        if (!orderId) return;
        try {
            const data = await fetchOrderStatus(orderId);
            setOrder(data);
            persistOrder(data);
            setLastRefreshed(new Date());
        } catch {
            // If 404 or error, order no longer valid - clear it
            clearActiveOrder();
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadOrder();

        if (!orderId) return;

        // Real-time WebSocket Subscription via Laravel Echo (dùng singleton getEcho)
        const channelName = `order.${orderId}`;
        const echo = getEcho();
        const channel = echo.channel(channelName);

        // Lắng nghe thay đổi trạng thái
        channel.listen('.OrderStatusTransitioned', (e: { order: Order; from: OrderStatus; to: OrderStatus }) => {
            console.log('[OrderTracking] OrderStatusTransitioned:', e);
            if (!e.to) return;
            setOrder((prev) => {
                if (!prev) return prev;
                const updated = { ...prev, status: e.to, ...e.order };
                persistOrder(updated);
                return updated;
            });
            setLastRefreshed(new Date());
        });

        // Lắng nghe khi có món mới được thêm (khách gọi thêm từ thiết bị khác)
        channel.listen('.OrderItemsAdded', (e: { order: Order }) => {
            console.log('[OrderTracking] OrderItemsAdded:', e);
            setOrder((prev) => {
                if (!prev) return prev;
                const updated = { ...prev, ...e.order };
                persistOrder(updated);
                return updated;
            });
            setLastRefreshed(new Date());
        });

        return () => {
            channel.stopListening('.OrderStatusTransitioned');
            channel.stopListening('.OrderItemsAdded');
            echo.leave(channelName);
        };
    }, [orderId]);

    const isTerminal = order ? TERMINAL_STATUSES.includes(order.status) : false;

    const activeStepIdx = order ? getStepIndex(order.status) : 0;

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            {/* Header */}
            <div className="bg-gradient-to-br from-orange-500 to-red-600 text-white px-5 pt-12 pb-8">
                <motion.div
                    initial={{ y: 20, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    className="text-center"
                >
                    <div className="text-5xl mb-3">🎉</div>
                    <h1 className="text-2xl font-black">Đơn đã được gửi!</h1>
                    <p className="text-white/80 text-sm mt-1">Bếp đang chuẩn bị món cho bạn</p>
                    {order && (
                        <div className="mt-3 bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2 inline-block">
                            <span className="text-sm font-semibold">Mã đơn #{order.id.slice(0, 8).toUpperCase()}</span>
                        </div>
                    )}
                </motion.div>
            </div>

            <div className="flex-1 px-4 py-6 space-y-5 max-w-lg mx-auto w-full">
                {/* Order Status Stepper */}
                <motion.div
                    initial={{ y: 20, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{ delay: 0.1 }}
                    className="bg-white rounded-3xl p-5 shadow-sm border border-gray-100"
                >
                    <div className="flex items-center justify-between mb-5">
                        <h2 className="font-black text-gray-800 text-lg">Trạng thái đơn hàng</h2>
                        <button
                            onClick={loadOrder}
                            className="flex items-center gap-1.5 text-xs text-orange-500 font-semibold bg-orange-50 px-3 py-1.5 rounded-full hover:bg-orange-100 transition-colors"
                        >
                            <RefreshCw size={12} />
                            Làm mới
                        </button>
                    </div>

                    {/* Timeline */}
                    <div className="space-y-0">
                        {ORDER_STEPS.map((step, idx) => {
                            const isDone = idx < activeStepIdx;
                            const isActive = idx === activeStepIdx;
                            const isPending = idx > activeStepIdx;

                            return (
                                <div key={step.status} className="flex gap-4">
                                    {/* Connector line + dot */}
                                    <div className="flex flex-col items-center">
                                        <motion.div
                                            initial={false}
                                            animate={{
                                                backgroundColor: isDone
                                                    ? '#22c55e'
                                                    : isActive
                                                    ? '#f97316'
                                                    : '#e5e7eb',
                                                scale: isActive ? 1.2 : 1,
                                            }}
                                            className={`w-9 h-9 rounded-full flex items-center justify-center text-white shrink-0 transition-all`}
                                        >
                                            {isDone ? (
                                                <CheckCircle2 size={18} />
                                            ) : (
                                                <span className={isPending ? 'text-gray-400' : ''}>{step.icon}</span>
                                            )}
                                        </motion.div>
                                        {idx < ORDER_STEPS.length - 1 && (
                                            <div
                                                className={`w-0.5 h-8 transition-all duration-500 ${
                                                    isDone ? 'bg-green-400' : 'bg-gray-200'
                                                }`}
                                            />
                                        )}
                                    </div>

                                    {/* Label */}
                                    <div className="pb-8 pt-1.5">
                                        <p
                                            className={`font-semibold text-sm leading-snug ${
                                                isDone
                                                    ? 'text-green-600'
                                                    : isActive
                                                    ? 'text-orange-600'
                                                    : 'text-gray-400'
                                            }`}
                                        >
                                            {step.label}
                                        </p>
                                        {isActive && (
                                            <motion.p
                                                initial={{ opacity: 0 }}
                                                animate={{ opacity: 1 }}
                                                className="text-xs text-gray-400 mt-0.5"
                                            >
                                                Đang xử lý...
                                            </motion.p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    <p className="text-xs text-gray-400 text-right mt-2">
                        Cập nhật lúc {lastRefreshed.toLocaleTimeString('vi-VN')}
                    </p>
                </motion.div>

                {/* Order Summary */}
                {order && (
                    <motion.div
                        initial={{ y: 20, opacity: 0 }}
                        animate={{ y: 0, opacity: 1 }}
                        transition={{ delay: 0.2 }}
                        className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden"
                    >
                        <div className="px-5 py-4 border-b border-gray-50">
                            <h3 className="font-bold text-gray-800">Chi tiết đơn hàng</h3>
                        </div>
                        <div className="divide-y divide-gray-50">
                            {(order.items || []).map((item) => (
                                <div key={item.id} className="flex justify-between px-5 py-3 text-sm">
                                    <span className="text-gray-700">
                                        {item.item_name || item.name || 'Món ăn'}
                                        {item.note && (
                                            <span className="text-gray-400 text-xs ml-1">({item.note})</span>
                                        )}
                                    </span>
                                    <span className="text-gray-500 ml-4 shrink-0">x{item.quantity}</span>
                                </div>
                            ))}
                            {(!order.items || order.items.length === 0) && (
                                <div className="px-5 py-4 text-sm text-gray-400 text-center">
                                    Đang tải chi tiết món...
                                </div>
                            )}
                        </div>
                        <div className="flex justify-between px-5 py-4 bg-gray-50 font-bold">
                            <span className="text-gray-700">Tổng cộng</span>
                            <span className="text-orange-600 text-lg">
                                {formatCurrency(order.final_amount ?? order.total_amount ?? 0)}
                            </span>
                        </div>
                    </motion.div>
                )}

                {/* Skeleton */}
                {loading && (
                    <div className="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 animate-pulse space-y-4">
                        <div className="h-5 w-40 bg-gray-200 rounded-full" />
                        {[1, 2, 3, 4].map((i) => (
                            <div key={i} className="flex gap-3">
                                <div className="w-9 h-9 rounded-full bg-gray-200 shrink-0" />
                                <div className="flex-1 h-4 bg-gray-100 rounded-full mt-2.5" />
                            </div>
                        ))}
                    </div>
                )}

                {/* Action Buttons */}
                <motion.div
                    initial={{ y: 20, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{ delay: 0.3 }}
                    className="space-y-3"
                >
                    {isTerminal ? (
                        <button
                            onClick={() => navigate(`/menu?public_token=${public_token}&type=${qrType}`)}
                            className="w-full bg-gray-800 hover:bg-gray-900 text-white rounded-2xl py-4 font-bold flex items-center justify-center gap-2 transition-colors shadow-lg shadow-gray-200"
                        >
                            <Home size={20} />
                            Quay lại Menu đặt đơn mới
                        </button>
                    ) : (
                        <button
                            onClick={() => navigate(`/menu?public_token=${public_token}&type=${qrType}&action=add_items`)}
                            className="w-full bg-orange-500 hover:bg-orange-600 text-white rounded-2xl py-4 font-bold flex items-center justify-center gap-2 transition-colors shadow-lg shadow-orange-200"
                        >
                            <Plus size={20} />
                            Gọi thêm món
                        </button>
                    )}
                    <p className="text-center text-xs text-gray-400 flex items-center justify-center gap-1.5">
                        <span className="relative flex h-2 w-2">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Trạng thái được cập nhật theo thời gian thực (Live)
                    </p>
                </motion.div>
            </div>
        </div>
    );
};

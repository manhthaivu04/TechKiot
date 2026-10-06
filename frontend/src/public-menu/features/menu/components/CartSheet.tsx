import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Trash2, Plus, Minus, ChefHat, ShoppingCart, User, Phone, FileText, ClipboardList } from 'lucide-react';
import { formatCurrency } from '@/lib/formatters';
import { useCartStore } from '@/store/cartStore';
import type { Order } from '@/types/public-menu';

interface CartSheetProps {
    open: boolean;
    onClose: () => void;
    onPlaceOrder: (name: string, phone: string, note: string) => void;
    isPlacingOrder: boolean;
    isAddingToOrder?: boolean;
    /** Đơn hàng đang mở của bàn (Collaborative Ordering) */
    activeOrder?: Order | null;
}

export const CartSheet = ({ open, onClose, onPlaceOrder, isPlacingOrder, isAddingToOrder = false, activeOrder }: CartSheetProps) => {
    const { items, totalPrice, updateQuantity } = useCartStore();

    const [customerName, setCustomerName] = useState('');
    const [customerPhone, setCustomerPhone] = useState('');
    const [orderNote, setOrderNote] = useState('');

    return (
        <AnimatePresence>
            {open && (
                <>
                    {/* Backdrop */}
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50"
                        onClick={onClose}
                    />

                    {/* Sheet */}
                    <motion.div
                        initial={{ y: '100%' }}
                        animate={{ y: 0 }}
                        exit={{ y: '100%' }}
                        transition={{ type: 'spring', stiffness: 280, damping: 28 }}
                        className="fixed bottom-0 left-0 right-0 z-50 max-w-lg mx-auto max-h-[85vh] flex flex-col"
                    >
                        {/* Glassmorphism card */}
                        <div className="bg-white/95 backdrop-blur-xl rounded-t-3xl shadow-2xl border border-white/40 flex flex-col overflow-hidden">
                            {/* Handle */}
                            <div className="flex justify-center pt-3 pb-1">
                                <div className="w-10 h-1 rounded-full bg-gray-300" />
                            </div>

                            {/* Header */}
                            <div className="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                                <div className="flex items-center gap-2">
                                    <ShoppingCart size={20} className="text-orange-500" />
                                    <h3 className="font-bold text-gray-800 text-lg">Giỏ hàng của bạn</h3>
                                </div>
                                <button
                                    onClick={onClose}
                                    className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors"
                                >
                                    <X size={16} className="text-gray-600" />
                                </button>
                            </div>

                            {/* Items list */}
                            <div className="flex-1 overflow-y-auto divide-y divide-gray-50">
                                {items.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-12 text-gray-400 gap-3">
                                        <ShoppingCart size={48} className="opacity-30" />
                                        <p className="text-sm font-medium">Giỏ hàng trống</p>
                                    </div>
                                ) : (
                                    <AnimatePresence>
                                        {items.map((item) => (
                                            <motion.div
                                                key={`${item.item_id}-${item.note}`}
                                                initial={{ opacity: 0, x: -20 }}
                                                animate={{ opacity: 1, x: 0 }}
                                                exit={{ opacity: 0, x: 20, height: 0 }}
                                                className="flex gap-3 px-5 py-4 items-center"
                                            >
                                                {/* Thumbnail */}
                                                <div className="w-14 h-14 rounded-xl overflow-hidden shrink-0 bg-gray-100">
                                                    {item.image_url ? (
                                                        <img
                                                            src={item.image_url}
                                                            alt={item.name}
                                                            className="w-full h-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center text-2xl">
                                                            🍽️
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Info */}
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-semibold text-gray-800 text-sm truncate">{item.name}</p>
                                                    {item.note && (
                                                        <p className="text-xs text-gray-400 truncate mt-0.5 italic">📝 {item.note}</p>
                                                    )}
                                                    <p className="text-orange-500 font-bold text-sm mt-1">
                                                        {formatCurrency(item.sale_price * item.quantity)}
                                                    </p>
                                                </div>

                                                {/* Qty Controls */}
                                                <div className="flex items-center gap-2 shrink-0">
                                                    <motion.button
                                                        whileTap={{ scale: 0.85 }}
                                                        onClick={() => updateQuantity(item.item_id, item.quantity - 1)}
                                                        className="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition-colors"
                                                    >
                                                        {item.quantity === 1 ? <Trash2 size={13} /> : <Minus size={13} />}
                                                    </motion.button>
                                                    <span className="w-5 text-center font-bold text-gray-800 text-sm">{item.quantity}</span>
                                                    <motion.button
                                                        whileTap={{ scale: 0.85 }}
                                                        onClick={() => updateQuantity(item.item_id, item.quantity + 1)}
                                                        className="w-7 h-7 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center hover:bg-orange-500 hover:text-white transition-colors"
                                                    >
                                                        <Plus size={13} />
                                                    </motion.button>
                                                </div>
                                            </motion.div>
                                        ))}
                                    </AnimatePresence>
                                )}
                            </div>

                            {/* Footer */}
                            {items.length > 0 && (
                                <div className="border-t border-gray-100 px-5 py-5 space-y-4 bg-white/80 backdrop-blur-md">
                                    {/* Active order info banner (Table Ordering) */}
                                    {activeOrder && (
                                        <div className="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                                            <ClipboardList size={15} className="text-amber-500 shrink-0" />
                                            <p className="text-xs text-amber-700">
                                                <strong>Đang gọi thêm</strong> cho đơn #{activeOrder.id?.slice(-6).toUpperCase()}
                                            </p>
                                        </div>
                                    )}

                                    <div className="space-y-3 mb-4">
                                        {/* Ẩn form tên/SĐT nếu đang gọi thêm */}
                                        {!isAddingToOrder && !activeOrder && (
                                            <>
                                                <div className="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2 border border-gray-100">
                                                    <User size={16} className="text-gray-400" />
                                                    <input
                                                        type="text"
                                                        placeholder="Tên của bạn (Tùy chọn)"
                                                        className="bg-transparent border-none outline-none w-full text-sm text-gray-700"
                                                        value={customerName}
                                                        onChange={(e) => setCustomerName(e.target.value)}
                                                    />
                                                </div>
                                                <div className="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2 border border-gray-100">
                                                    <Phone size={16} className="text-gray-400" />
                                                    <input
                                                        type="tel"
                                                        placeholder="Số điện thoại (Tùy chọn)"
                                                        className="bg-transparent border-none outline-none w-full text-sm text-gray-700"
                                                        value={customerPhone}
                                                        onChange={(e) => setCustomerPhone(e.target.value)}
                                                    />
                                                </div>
                                            </>
                                        )}
                                        <div className="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2 border border-gray-100">
                                            <FileText size={16} className="text-gray-400" />
                                            <input
                                                type="text"
                                                placeholder="Ghi chú cho bếp (Tùy chọn)"
                                                className="bg-transparent border-none outline-none w-full text-sm text-gray-700"
                                                value={orderNote}
                                                onChange={(e) => setOrderNote(e.target.value)}
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between">
                                        <span className="text-gray-500 font-medium">Tổng cộng</span>
                                        <span className="text-xl font-bold text-gray-900">{formatCurrency(totalPrice())}</span>
                                    </div>
                                    <motion.button
                                        whileTap={{ scale: 0.97 }}
                                        onClick={() => onPlaceOrder(customerName, customerPhone, orderNote)}
                                        disabled={isPlacingOrder}
                                        className="w-full bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-2xl py-4 font-bold text-base flex items-center justify-center gap-3 transition-all shadow-lg shadow-orange-200 disabled:opacity-70 disabled:cursor-not-allowed"
                                    >
                                        <ChefHat size={20} />
                                        {isPlacingOrder
                                            ? 'Đang gửi bếp...'
                                            : (isAddingToOrder || activeOrder ? 'Xác nhận gọi thêm 🔥' : '🔥 Gửi Bếp (Đặt món)')}
                                    </motion.button>
                                </div>
                            )}
                        </div>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
};

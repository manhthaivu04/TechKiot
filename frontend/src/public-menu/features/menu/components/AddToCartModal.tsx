import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Plus, Minus, ShoppingBag, StickyNote } from 'lucide-react';
import { formatCurrency } from '@/lib/formatters';
import { useCartStore } from '@/store/cartStore';
import type { PublicItem } from '@/types/public-menu';

interface AddToCartModalProps {
    item: PublicItem | null;
    onClose: () => void;
}

export const AddToCartModal = ({ item, onClose }: AddToCartModalProps) => {
    const [quantity, setQuantity] = useState(1);
    const [note, setNote] = useState('');
    const addItem = useCartStore((s) => s.addItem);

    if (!item) return null;

    const handleAdd = () => {
        addItem({
            item_id: item.id,
            name: item.name,
            image_url: item.image_url,
            sale_price: parseFloat(String(item.sale_price)),
            quantity,
            note: note.trim(),
        });
        onClose();
    };

    return (
        <AnimatePresence>
            {item && (
                <>
                    {/* Backdrop */}
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50"
                        onClick={onClose}
                    />

                    {/* Bottom Sheet */}
                    <motion.div
                        initial={{ y: '100%' }}
                        animate={{ y: 0 }}
                        exit={{ y: '100%' }}
                        transition={{ type: 'spring', stiffness: 300, damping: 30 }}
                        className="fixed bottom-0 left-0 right-0 z-50 max-w-lg mx-auto"
                    >
                        <div className="bg-white rounded-t-3xl shadow-2xl overflow-hidden">
                            {/* Item Image */}
                            <div className="relative h-48 bg-gray-100">
                                {item.image_url ? (
                                    <img
                                        src={item.image_url}
                                        alt={item.name}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-6xl bg-orange-50">
                                        🍽️
                                    </div>
                                )}
                                <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />

                                {/* Close Button */}
                                <button
                                    onClick={onClose}
                                    className="absolute top-4 right-4 w-9 h-9 rounded-full bg-black/40 backdrop-blur-sm text-white flex items-center justify-center hover:bg-black/60 transition-colors"
                                >
                                    <X size={18} />
                                </button>

                                {/* Item name overlay */}
                                <div className="absolute bottom-4 left-4 right-4">
                                    <h2 className="text-xl font-bold text-white leading-tight">{item.name}</h2>
                                    <p className="text-white/80 text-sm mt-0.5">{item.description}</p>
                                </div>
                            </div>

                            {/* Content */}
                            <div className="p-5 space-y-5">
                                {/* Price */}
                                <div className="flex items-center justify-between">
                                    <span className="text-2xl font-bold text-orange-500">
                                        {formatCurrency(item.sale_price)}
                                    </span>
                                    <span className="text-sm text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
                                        / {1} phần
                                    </span>
                                </div>

                                {/* Note input */}
                                <div className="space-y-2">
                                    <label className="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                        <StickyNote size={15} className="text-orange-400" />
                                        Ghi chú (tuỳ chọn)
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ví dụ: Ít cay, không hành..."
                                        value={note}
                                        onChange={(e) => setNote(e.target.value)}
                                        className="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-400 bg-gray-50 transition-all"
                                    />
                                </div>

                                {/* Quantity selector */}
                                <div className="flex items-center justify-between bg-gray-50 rounded-2xl p-3">
                                    <span className="font-semibold text-gray-700 text-sm">Số lượng</span>
                                    <div className="flex items-center gap-4">
                                        <motion.button
                                            whileTap={{ scale: 0.85 }}
                                            onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                                            className="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:border-orange-400 hover:text-orange-500 transition-colors shadow-sm"
                                        >
                                            <Minus size={16} />
                                        </motion.button>
                                        <span className="w-8 text-center font-bold text-lg text-gray-800">
                                            {quantity}
                                        </span>
                                        <motion.button
                                            whileTap={{ scale: 0.85 }}
                                            onClick={() => setQuantity((q) => q + 1)}
                                            className="w-9 h-9 rounded-full bg-orange-500 text-white flex items-center justify-center hover:bg-orange-600 transition-colors shadow-sm shadow-orange-200"
                                        >
                                            <Plus size={16} />
                                        </motion.button>
                                    </div>
                                </div>

                                {/* Add Button */}
                                <motion.button
                                    whileTap={{ scale: 0.97 }}
                                    onClick={handleAdd}
                                    className="w-full bg-orange-500 hover:bg-orange-600 text-white rounded-2xl py-4 font-bold text-base flex items-center justify-center gap-3 transition-colors shadow-lg shadow-orange-200"
                                >
                                    <ShoppingBag size={20} />
                                    Thêm vào giỏ —{' '}
                                    {formatCurrency(parseFloat(String(item.sale_price)) * quantity)}
                                </motion.button>
                            </div>
                        </div>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
};

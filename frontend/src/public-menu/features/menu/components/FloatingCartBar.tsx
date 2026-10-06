import { motion, AnimatePresence } from 'framer-motion';
import { ShoppingCart, ChevronRight } from 'lucide-react';
import { formatCurrency } from '@/lib/formatters';
import { useCartStore } from '@/store/cartStore';

interface FloatingCartBarProps {
    onClick: () => void;
}

export const FloatingCartBar = ({ onClick }: FloatingCartBarProps) => {
    const { totalItems, totalPrice } = useCartStore();
    const count = totalItems();

    return (
        <AnimatePresence>
            {count > 0 && (
                <motion.div
                    initial={{ y: 100, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    exit={{ y: 100, opacity: 0 }}
                    transition={{ type: 'spring', stiffness: 300, damping: 25 }}
                    className="fixed bottom-0 left-0 right-0 z-40 px-4 pb-safe-bottom pb-4"
                    style={{ paddingBottom: 'max(env(safe-area-inset-bottom), 16px)' }}
                >
                    <div className="max-w-lg mx-auto">
                        <motion.button
                            whileTap={{ scale: 0.97 }}
                            onClick={onClick}
                            className="w-full bg-gray-900 text-white rounded-2xl px-5 py-4 flex items-center gap-4 shadow-2xl shadow-gray-900/40 hover:bg-gray-800 transition-colors"
                        >
                            {/* Cart icon with badge */}
                            <div className="relative">
                                <div className="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                                    <ShoppingCart size={20} />
                                </div>
                                <motion.span
                                    key={count}
                                    initial={{ scale: 0.5 }}
                                    animate={{ scale: 1 }}
                                    className="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center"
                                >
                                    {count}
                                </motion.span>
                            </div>

                            {/* Text */}
                            <div className="flex-1 text-left">
                                <div className="font-bold text-sm">
                                    {count} {count === 1 ? 'món' : 'món'}
                                </div>
                                <div className="text-gray-400 text-xs">{formatCurrency(totalPrice())}</div>
                            </div>

                            {/* CTA */}
                            <div className="flex items-center gap-1 text-orange-400 font-bold text-sm">
                                Xem giỏ hàng
                                <ChevronRight size={16} />
                            </div>
                        </motion.button>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
};

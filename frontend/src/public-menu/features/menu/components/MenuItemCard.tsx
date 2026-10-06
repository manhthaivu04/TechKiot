import { motion } from 'framer-motion';
import { Plus, Flame } from 'lucide-react';
import { formatCurrency } from '@/lib/formatters';
import type { PublicItem } from '@/types/public-menu';

interface MenuItemCardProps {
    item: PublicItem;
    onAdd: (item: PublicItem) => void;
}

export const MenuItemCard = ({ item, onAdd }: MenuItemCardProps) => {
    const isUnavailable =
        item.availability_status === 'OUT_OF_STOCK' || item.availability_status === 'SUSPENDED';

    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            whileHover={{ y: -2 }}
            transition={{ duration: 0.2 }}
            className={`bg-white rounded-2xl p-3 flex gap-3 shadow-sm border border-gray-100 transition-shadow hover:shadow-md ${isUnavailable ? 'opacity-60' : ''}`}
        >
            {/* Image */}
            <div className="w-[90px] h-[90px] rounded-xl overflow-hidden shrink-0 bg-gray-100 relative">
                {item.image_url ? (
                    <img
                        src={item.image_url}
                        alt={item.name}
                        className="w-full h-full object-cover"
                        loading="lazy"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-3xl bg-orange-50">
                        🍽️
                    </div>
                )}

                {/* Hot badge */}
                {item.availability_status === 'IN_STOCK' && (
                    <div className="absolute top-1.5 left-1.5">
                        <div className="bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-md flex items-center gap-0.5">
                            <Flame size={8} />
                            HOT
                        </div>
                    </div>
                )}

                {/* Out of stock overlay */}
                {isUnavailable && (
                    <div className="absolute inset-0 bg-gray-900/50 flex items-center justify-center rounded-xl">
                        <span className="text-white text-[10px] font-bold bg-gray-800 px-2 py-1 rounded-lg">
                            Hết món
                        </span>
                    </div>
                )}
            </div>

            {/* Info */}
            <div className="flex-1 flex flex-col justify-between py-0.5 min-w-0">
                <div>
                    <h3 className="font-bold text-gray-800 text-sm leading-snug line-clamp-2 mb-1">
                        {item.name}
                    </h3>
                    {item.description && (
                        <p className="text-gray-400 text-xs line-clamp-2 leading-relaxed">
                            {item.description}
                        </p>
                    )}
                </div>

                <div className="flex items-center justify-between mt-2">
                    <span className="font-bold text-orange-500 text-base">
                        {formatCurrency(item.sale_price)}
                    </span>

                    <motion.button
                        whileTap={{ scale: 0.8 }}
                        onClick={() => !isUnavailable && onAdd(item)}
                        disabled={isUnavailable}
                        className={`
                            w-9 h-9 rounded-full flex items-center justify-center transition-all shadow-sm
                            ${isUnavailable
                                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                : 'bg-orange-500 text-white hover:bg-orange-600 shadow-orange-200 hover:shadow-md active:scale-90'
                            }
                        `}
                    >
                        <Plus size={18} />
                    </motion.button>
                </div>
            </div>
        </motion.div>
    );
};

import { motion } from 'framer-motion';

export const SplashScreen = ({ name }: { name?: string }) => (
    <div className="fixed inset-0 bg-gradient-to-br from-orange-500 via-orange-600 to-red-700 flex flex-col items-center justify-center z-50">
        <motion.div
            initial={{ scale: 0.5, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ type: 'spring', stiffness: 200, damping: 15 }}
            className="flex flex-col items-center gap-6"
        >
            {/* Logo */}
            <div className="w-24 h-24 rounded-3xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-2xl">
                <span className="text-5xl">🍽️</span>
            </div>

            <div className="text-center">
                <motion.h1
                    initial={{ y: 20, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{ delay: 0.2 }}
                    className="text-3xl font-bold text-white tracking-tight"
                >
                    {name || 'KiotTay'}
                </motion.h1>
                <motion.p
                    initial={{ y: 20, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{ delay: 0.35 }}
                    className="text-white/70 mt-1 text-sm"
                >
                    Đang tải thực đơn...
                </motion.p>
            </div>

            {/* Loading dots */}
            <div className="flex gap-2">
                {[0, 1, 2].map((i) => (
                    <motion.div
                        key={i}
                        className="w-2.5 h-2.5 rounded-full bg-white/60"
                        animate={{ scale: [1, 1.5, 1], opacity: [0.5, 1, 0.5] }}
                        transition={{ duration: 1, repeat: Infinity, delay: i * 0.2 }}
                    />
                ))}
            </div>
        </motion.div>
    </div>
);

export const MenuItemSkeleton = () => (
    <div className="bg-white rounded-2xl p-3 flex gap-4 border border-gray-100 animate-pulse">
        <div className="w-24 h-24 rounded-xl bg-gray-200 shrink-0" />
        <div className="flex-1 flex flex-col gap-2 py-1">
            <div className="h-4 bg-gray-200 rounded-full w-3/4" />
            <div className="h-3 bg-gray-100 rounded-full w-full" />
            <div className="h-3 bg-gray-100 rounded-full w-2/3" />
            <div className="flex justify-between mt-auto">
                <div className="h-4 bg-gray-200 rounded-full w-20" />
                <div className="w-8 h-8 rounded-full bg-gray-200" />
            </div>
        </div>
    </div>
);

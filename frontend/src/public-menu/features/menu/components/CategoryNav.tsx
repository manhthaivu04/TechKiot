import { useRef } from 'react';
import { motion } from 'framer-motion';
import type { PublicItemGroup } from '@/types/public-menu';

interface CategoryNavProps {
    groups: PublicItemGroup[];
    activeGroupId: string | null;
    onSelect: (groupId: string) => void;
}

export const CategoryNav = ({ groups, activeGroupId, onSelect }: CategoryNavProps) => {
    const scrollRef = useRef<HTMLDivElement>(null);

    const handleClick = (groupId: string) => {
        onSelect(groupId);
        // Auto-scroll the pill into view
        const pill = document.getElementById(`cat-pill-${groupId}`);
        pill?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    };

    return (
        <div
            ref={scrollRef}
            className="flex gap-2 overflow-x-auto scrollbar-hide pb-1 px-4"
        >
            {groups?.map((group) => {
                const isActive = activeGroupId === group.group_id;
                return (
                    <motion.button
                        id={`cat-pill-${group.group_id}`}
                        key={group.group_id}
                        onClick={() => handleClick(group.group_id)}
                        whileTap={{ scale: 0.93 }}
                        className={`
                            px-5 py-2 rounded-full whitespace-nowrap font-semibold text-sm transition-all 
                            flex-shrink-0 border
                            ${isActive
                                ? 'bg-orange-500 text-white border-orange-500 shadow-md shadow-orange-200'
                                : 'bg-white text-gray-600 border-gray-200 hover:border-orange-300 hover:text-orange-500'
                            }
                        `}
                    >
                        {group.group_name}
                    </motion.button>
                );
            })}
        </div>
    );
};

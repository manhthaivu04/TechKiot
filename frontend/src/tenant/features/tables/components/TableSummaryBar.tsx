import { motion } from 'framer-motion';
import type { RestaurantTable, TableStatus } from '@/types';
import { TABLE_STATUS_OPTIONS } from '@/types';

interface TableSummaryBarProps {
    tables: RestaurantTable[];
    total: number;
}

interface StatCardProps {
    label: string;
    count: number;
    color: string;
    bgColor: string;
    delay: number;
}

const StatCard = ({ label, count, color, bgColor, delay }: StatCardProps) => (
    <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.3, delay }}
        style={{
            flex: 1,
            minWidth: 140,
            background: bgColor,
            borderRadius: 12,
            padding: '16px 20px',
            display: 'flex',
            flexDirection: 'column',
            gap: 6,
            border: `1px solid ${color}20`,
        }}
    >
        <div style={{ fontSize: 13, color: '#6b7280', fontWeight: 500 }}>
            {label}
        </div>
        <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.5, delay: delay + 0.15 }}
            style={{
                fontSize: 28,
                fontWeight: 700,
                color,
                lineHeight: 1,
            }}
        >
            {count}
        </motion.div>
    </motion.div>
);

export const TableSummaryBar = ({ tables, total }: TableSummaryBarProps) => {
    const countByStatus = (status: TableStatus) =>
        tables.filter((t) => t.status === status).length;

    const stats = [
        {
            label: 'Tổng số bàn',
            count: total,
            color: '#3b82f6',
            bgColor: '#eff6ff',
        },
        ...TABLE_STATUS_OPTIONS.map((opt) => ({
            label: opt.label,
            count: countByStatus(opt.value),
            color: opt.color,
            bgColor:
                opt.value === 'available' ? '#f0fdf4' :
                opt.value === 'occupied' ? '#fef2f2' :
                opt.value === 'reserved' ? '#fffbeb' :
                '#f9fafb',
        })),
    ];

    return (
        <div style={{
            display: 'flex',
            gap: 12,
            flexWrap: 'wrap',
            marginBottom: 20,
        }}>
            {stats.map((stat, index) => (
                <StatCard key={stat.label} {...stat} delay={index * 0.06} />
            ))}
        </div>
    );
};

import { Card, Empty, Skeleton } from 'antd';
import {
    PieChart,
    Pie,
    Cell,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import type { PaymentMethodBreakdown } from '../types/analytics.types';

interface PaymentBreakdownProps {
    data?: PaymentMethodBreakdown[];
    loading: boolean;
}

const COLORS = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444'];

const formatVND = (value: number) =>
    `${value.toLocaleString('vi-VN')} ₫`;

const CustomTooltip = ({ active, payload }: any) => {
    if (active && payload && payload.length) {
        const item = payload[0];
        return (
            <div className="bg-white border border-gray-100 rounded-xl shadow-lg px-4 py-3">
                <p className="text-xs text-gray-500 mb-1">{item.name}</p>
                <p className="text-sm font-bold" style={{ color: item.payload.fill }}>
                    {formatVND(item.value)}
                </p>
                <p className="text-xs text-gray-400">{item.payload.count} giao dịch</p>
            </div>
        );
    }
    return null;
};

/**
 * PaymentBreakdown — Biểu đồ tròn phân bổ doanh thu theo phương thức thanh toán.
 */
export const PaymentBreakdown = ({ data, loading }: PaymentBreakdownProps) => {
    const hasData = data && data.length > 0 && data.some((d) => d.revenue > 0);

    return (
        <Card
            title={
                <span className="font-semibold text-gray-800">
                    💳 Phương thức thanh toán
                </span>
            }
            className="shadow-sm h-full"
        >
            {loading ? (
                <Skeleton active paragraph={{ rows: 5 }} />
            ) : !hasData ? (
                <Empty description="Chưa có dữ liệu" className="py-8" />
            ) : (
                <ResponsiveContainer width="100%" height={260}>
                    <PieChart>
                        <Pie
                            data={data}
                            dataKey="revenue"
                            nameKey="label"
                            cx="50%"
                            cy="45%"
                            outerRadius={85}
                            innerRadius={50}
                            paddingAngle={3}
                        >
                            {data!.map((_, index) => (
                                <Cell
                                    key={`cell-${index}`}
                                    fill={COLORS[index % COLORS.length]}
                                />
                            ))}
                        </Pie>
                        <Tooltip content={<CustomTooltip />} />
                        <Legend
                            iconType="circle"
                            iconSize={8}
                            formatter={(value) => (
                                <span className="text-xs text-gray-600">{value}</span>
                            )}
                        />
                    </PieChart>
                </ResponsiveContainer>
            )}
        </Card>
    );
};

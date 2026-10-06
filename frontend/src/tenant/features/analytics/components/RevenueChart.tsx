import { Card, Empty, Skeleton } from 'antd';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from 'recharts';
import type { ChartDataPoint } from '../types/analytics.types';

interface RevenueChartProps {
    data?: ChartDataPoint[];
    loading: boolean;
    period: string;
}

/** Format tooltip giá trị doanh thu */
const formatTooltipValue = (value: number) =>
    `${value.toLocaleString('vi-VN')} ₫`;

/** Format trục Y ngắn gọn */
const formatYAxis = (value: number) => {
    if (value >= 1_000_000) return `${(value / 1_000_000).toFixed(0)}tr`;
    if (value >= 1_000) return `${(value / 1_000).toFixed(0)}k`;
    return String(value);
};

const CustomTooltip = ({ active, payload, label }: any) => {
    if (active && payload && payload.length) {
        return (
            <div className="bg-white border border-gray-100 rounded-xl shadow-lg px-4 py-3">
                <p className="text-xs text-gray-500 mb-1">{label}</p>
                <p className="text-base font-bold text-emerald-600">
                    {formatTooltipValue(payload[0].value)}
                </p>
                {payload[1] && (
                    <p className="text-xs text-blue-500 mt-0.5">
                        {payload[1].value} đơn
                    </p>
                )}
            </div>
        );
    }
    return null;
};

/**
 * RevenueChart — Biểu đồ Area doanh thu theo thời gian.
 * Tự động lọc bỏ tick trục X nếu data quá nhiều (month view).
 */
export const RevenueChart = ({ data, loading, period }: RevenueChartProps) => {
    const hasData = data && data.some((d) => d.revenue > 0);

    // today=mỗi 2h | week=cả 7 thứ | month=mỗi 5 ngày | custom=auto
    const tickInterval =
        period === 'today'  ? 1  :  // hiện thị 00,02,04...22 (mỗi 2)
        period === 'week'   ? 0  :  // 7 điểm → hiện tất cả
        period === 'month'  ? 4  :  // mỗi 5 ngày tránh dày
        0;                           // custom: auto

    return (
        <Card
            title={
                <span className="font-semibold text-gray-800">
                    📈 Doanh thu theo thời gian
                </span>
            }
            className="shadow-sm"
        >
            {loading ? (
                <Skeleton active paragraph={{ rows: 6 }} />
            ) : !hasData ? (
                <Empty
                    description="Chưa có doanh thu trong khoảng thời gian này"
                    className="py-12"
                />
            ) : (
                <ResponsiveContainer width="100%" height={300}>
                    <AreaChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                        <defs>
                            <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%"  stopColor="#10b981" stopOpacity={0.25} />
                                <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                        <XAxis
                            dataKey="label"
                            tick={{ fontSize: 11, fill: '#9ca3af' }}
                            interval={tickInterval}
                            tickLine={false}
                            axisLine={false}
                        />
                        <YAxis
                            tickFormatter={formatYAxis}
                            tick={{ fontSize: 11, fill: '#9ca3af' }}
                            tickLine={false}
                            axisLine={false}
                            width={48}
                        />
                        <Tooltip content={<CustomTooltip />} />
                        <Area
                            type="monotone"
                            dataKey="revenue"
                            name="Doanh thu"
                            stroke="#10b981"
                            strokeWidth={2.5}
                            fill="url(#colorRevenue)"
                            dot={false}
                            activeDot={{ r: 5, fill: '#10b981', stroke: '#fff', strokeWidth: 2 }}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            )}
        </Card>
    );
};

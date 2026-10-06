import { Row, Col, Card, Statistic, Skeleton } from 'antd';
import {
    DollarOutlined,
    ShoppingCartOutlined,
    CloseCircleOutlined,
    RiseOutlined,
} from '@ant-design/icons';
import type { OverviewStats } from '../types/analytics.types';

interface OverviewCardsProps {
    data?: OverviewStats;
    loading: boolean;
}

/** Format số tiền VNĐ ngắn gọn */
const formatVND = (value: number) => {
    if (value >= 1_000_000) {
        return `${(value / 1_000_000).toFixed(1)}tr`;
    }
    if (value >= 1_000) {
        return `${(value / 1_000).toFixed(0)}k`;
    }
    return value.toLocaleString('vi-VN');
};

/** Badge % thay đổi so kỳ trước */
const ChangeBadge = ({ pct }: { pct: number | null }) => {
    if (pct === null) return null;
    const isUp = pct >= 0;
    return (
        <span
            className={`text-xs font-semibold px-1.5 py-0.5 rounded-full ${
                isUp
                    ? 'bg-green-50 text-green-600'
                    : 'bg-red-50 text-red-500'
            }`}
        >
            {isUp ? '▲' : '▼'} {Math.abs(pct)}%
        </span>
    );
};

/**
 * OverviewCards — 4 thẻ tổng quan doanh thu.
 */
export const OverviewCards = ({ data, loading }: OverviewCardsProps) => {
    const cards = [
        {
            title: 'Doanh thu',
            value: data?.total_revenue ?? 0,
            formatter: (v: number | string) => `${formatVND(Number(v))} ₫`,
            icon: <DollarOutlined className="text-emerald-500 text-xl" />,
            pct: data?.revenue_change_pct ?? null,
            colorClass: 'border-l-emerald-400',
        },
        {
            title: 'Số đơn hàng',
            value: data?.total_orders ?? 0,
            formatter: (v: number | string) => `${v} đơn`,
            icon: <ShoppingCartOutlined className="text-blue-500 text-xl" />,
            pct: data?.orders_change_pct ?? null,
            colorClass: 'border-l-blue-400',
        },
        {
            title: 'Đơn bị hủy',
            value: data?.cancelled_orders ?? 0,
            formatter: (v: number | string) => `${v} đơn`,
            icon: <CloseCircleOutlined className="text-red-400 text-xl" />,
            pct: null,
            colorClass: 'border-l-red-300',
        },
        {
            title: 'Giá trị TB / đơn',
            value: data?.avg_order_value ?? 0,
            formatter: (v: number | string) => `${formatVND(Number(v))} ₫`,
            icon: <RiseOutlined className="text-purple-500 text-xl" />,
            pct: null,
            colorClass: 'border-l-purple-400',
        },
    ];

    return (
        <Row gutter={[16, 16]}>
            {cards.map((card) => (
                <Col xs={24} sm={12} xl={6} key={card.title}>
                    <Card
                        className={`border-l-4 ${card.colorClass} hover:shadow-md transition-shadow duration-200`}
                        bodyStyle={{ padding: '20px 24px' }}
                    >
                        {loading ? (
                            <Skeleton active paragraph={{ rows: 1 }} />
                        ) : (
                            <div className="flex items-start justify-between">
                                <div>
                                    <Statistic
                                        title={
                                            <span className="text-gray-500 text-sm font-medium">
                                                {card.title}
                                            </span>
                                        }
                                        value={card.value}
                                        formatter={card.formatter as any}
                                        valueStyle={{ fontSize: '1.375rem', fontWeight: 700, color: '#1f2937' }}
                                    />
                                    {card.pct !== null && (
                                        <div className="mt-2">
                                            <ChangeBadge pct={card.pct} />
                                            <span className="text-xs text-gray-400 ml-1">so kỳ trước</span>
                                        </div>
                                    )}
                                </div>
                                <div className="opacity-80">{card.icon}</div>
                            </div>
                        )}
                    </Card>
                </Col>
            ))}
        </Row>
    );
};

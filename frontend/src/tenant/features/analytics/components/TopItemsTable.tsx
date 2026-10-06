import { Card, Table, Empty, Skeleton, Avatar, Progress } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import type { TopItem } from '../types/analytics.types';

interface TopItemsTableProps {
    data?: TopItem[];
    loading: boolean;
}

const formatVND = (value: number) => value.toLocaleString('vi-VN') + ' ₫';

/**
 * TopItemsTable — Bảng Top 5 món ăn bán chạy nhất.
 */
export const TopItemsTable = ({ data, loading }: TopItemsTableProps) => {
    const maxRevenue = data && data.length > 0 ? Math.max(...data.map((d) => d.total_revenue)) : 1;

    const columns: ColumnsType<TopItem> = [
        {
            title: '#',
            key: 'rank',
            width: 40,
            render: (_: unknown, __: TopItem, index: number) => (
                <span
                    className={`font-bold text-sm ${
                        index === 0
                            ? 'text-yellow-500'
                            : index === 1
                            ? 'text-gray-400'
                            : index === 2
                            ? 'text-amber-600'
                            : 'text-gray-300'
                    }`}
                >
                    {index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}`}
                </span>
            ),
        },
        {
            title: 'Món ăn',
            key: 'name',
            render: (_, record) => (
                <div className="flex items-center gap-3">
                    <Avatar
                        src={record.image_url}
                        size={36}
                        shape="square"
                        style={{ borderRadius: 8, background: '#f3f4f6', color: '#9ca3af' }}
                    >
                        {record.name.charAt(0)}
                    </Avatar>
                    <span className="font-medium text-gray-800 text-sm">{record.name}</span>
                </div>
            ),
        },
        {
            title: 'Đã bán',
            dataIndex: 'total_sold',
            key: 'total_sold',
            align: 'center',
            render: (v: number) => (
                <span className="font-semibold text-blue-600">{v}</span>
            ),
        },
        {
            title: 'Doanh thu',
            key: 'total_revenue',
            render: (_, record) => (
                <div>
                    <div className="text-sm font-semibold text-gray-700 mb-1">
                        {formatVND(record.total_revenue)}
                    </div>
                    <Progress
                        percent={Math.round((record.total_revenue / maxRevenue) * 100)}
                        showInfo={false}
                        size="small"
                        strokeColor="#10b981"
                        trailColor="#f3f4f6"
                    />
                </div>
            ),
        },
    ];

    return (
        <Card
            title={
                <span className="font-semibold text-gray-800">
                    🏆 Top món bán chạy
                </span>
            }
            className="shadow-sm h-full"
        >
            {loading ? (
                <Skeleton active paragraph={{ rows: 5 }} />
            ) : !data || data.length === 0 ? (
                <Empty description="Chưa có dữ liệu" className="py-8" />
            ) : (
                <Table
                    columns={columns}
                    dataSource={data}
                    rowKey="item_id"
                    pagination={false}
                    size="small"
                    showHeader={true}
                />
            )}
        </Card>
    );
};

import { useMemo } from 'react';
import { useOrders } from '../services/order.hooks';
import { useRestaurantTables } from '@/tenant/features/tables/services/table.hooks';
import type { Order } from '@/types';
import { Spin, Tag, Tooltip, Empty } from 'antd';
import { usePosCartStore } from '@/store/posCartStore';
import { UserOutlined, ClockCircleOutlined } from '@ant-design/icons';

const ACTIVE_STATUSES = new Set(['open', 'cooking', 'served']);

interface Props {
    onSelectTable: (tableId: string) => void;
    onViewOrder: (order: Order) => void;
}

export const TableMap = ({ onSelectTable, onViewOrder }: Props) => {
    // Lấy bàn từ API (không dùng MOCK_TABLES nữa)
    const { data: tableData, isLoading: isLoadingTables } = useRestaurantTables({ per_page: 100 });
    // Lấy tất cả đơn hàng đang mở
    const { data: orderData, isLoading: isLoadingOrders } = useOrders({ per_page: 200 });
    const setServiceType = usePosCartStore((s) => s.setServiceType);

    const isLoading = isLoadingTables || isLoadingOrders;

    // Map table_id → active_order
    const activeOrderByTableId = useMemo(() => {
        const map = new Map<string, Order>();
        if (!orderData?.data) return map;
        for (const order of orderData.data) {
            if (!order.table_id) continue;
            if (!ACTIVE_STATUSES.has(order.status as string)) continue;
            // Kiểm tra chưa thanh toán đủ
            const totalPaid = (order.payments || []).reduce(
                (sum, p) => sum + parseFloat(p.amount),
                0
            );
            const isFullyPaid =
                totalPaid >= parseFloat(order.final_amount) && totalPaid > 0;
            if (isFullyPaid) continue;
            // Mỗi bàn chỉ giữ 1 đơn gần nhất
            if (!map.has(order.table_id)) {
                map.set(order.table_id, order);
            }
        }
        return map;
    }, [orderData?.data]);

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center p-16 gap-3 text-gray-400">
                <Spin size="large" />
                <span className="text-sm">Đang tải sơ đồ bàn…</span>
            </div>
        );
    }

    const tables = tableData?.data || [];

    if (tables.length === 0) {
        return (
            <div className="flex items-center justify-center p-16">
                <Empty
                    description="Chưa có bàn nào. Vui lòng thêm bàn trong phần Quản lý bàn."
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                />
            </div>
        );
    }

    return (
        <div className="p-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4 overflow-y-auto">
            {tables.map((table) => {
                const activeOrder = activeOrderByTableId.get(table.id);
                const isOccupied = !!activeOrder;

                // Format thời gian ngắn gọn
                const elapsedLabel = activeOrder
                    ? (() => {
                          const diff = Math.floor(
                              (Date.now() - new Date(activeOrder.created_at).getTime()) / 60000
                          );
                          if (diff < 60) return `${diff} phút`;
                          return `${Math.floor(diff / 60)}h${diff % 60 > 0 ? diff % 60 + 'm' : ''}`;
                      })()
                    : null;

                return (
                    <Tooltip
                        key={table.id}
                        title={
                            isOccupied
                                ? `${activeOrder!.order_code} · ${elapsedLabel}`
                                : `Bàn trống · Sức chứa: ${table.capacity} người`
                        }
                        placement="top"
                    >
                        <div
                            onClick={() => {
                                if (isOccupied) {
                                    onViewOrder(activeOrder!);
                                } else {
                                    setServiceType('DINE_IN');
                                    onSelectTable(table.id);
                                }
                            }}
                            className={`
                                relative flex flex-col items-center justify-center p-3 rounded-2xl cursor-pointer
                                border-2 transition-all duration-200 aspect-square shadow-sm select-none
                                ${isOccupied
                                    ? 'bg-amber-50 border-amber-400 hover:bg-amber-100 hover:shadow-md'
                                    : 'bg-white border-gray-200 hover:border-emerald-400 hover:shadow-md'}
                            `}
                        >
                            {/* Table icon */}
                            <div
                                className={`text-2xl mb-1 ${isOccupied ? 'grayscale-0' : 'opacity-40'}`}
                            >
                                🍽️
                            </div>

                            {/* Table name */}
                            <span
                                className={`text-sm font-extrabold text-center leading-tight ${
                                    isOccupied ? 'text-amber-800' : 'text-gray-700'
                                }`}
                            >
                                {table.name}
                            </span>

                            {/* Area label */}
                            {table.area && (
                                <span className="text-[10px] text-gray-400 mt-0.5">
                                    {table.area.name}
                                </span>
                            )}

                            {/* Status section */}
                            {isOccupied ? (
                                <div className="flex flex-col items-center mt-2 gap-1">
                                    <Tag
                                        color="warning"
                                        className="m-0 text-[10px] flex items-center gap-1 !rounded-full !px-2"
                                    >
                                        <UserOutlined style={{ fontSize: 10 }} /> Có khách
                                    </Tag>
                                    <span className="flex items-center gap-1 text-[10px] text-amber-600 font-semibold">
                                        <ClockCircleOutlined style={{ fontSize: 9 }} />
                                        {elapsedLabel}
                                    </span>
                                </div>
                            ) : (
                                <span className="text-[10px] text-emerald-500 font-semibold mt-2">
                                    Trống
                                </span>
                            )}

                            {/* Active dot indicator */}
                            {isOccupied && (
                                <span className="absolute top-2 right-2 w-2.5 h-2.5 bg-amber-500 rounded-full animate-pulse" />
                            )}
                        </div>
                    </Tooltip>
                );
            })}
        </div>
    );
};

import { useMemo } from 'react';
import { useRestaurantTables } from '@/tenant/features/tables/services/table.hooks';
import type { TableNameMap } from '../utils/orderDisplay';

/** Map table_id → tên bàn để hiển thị trên đơn hàng */
export function useTableNameMap(): TableNameMap {
    const { data } = useRestaurantTables({ per_page: 200 });

    return useMemo(() => {
        const map: TableNameMap = {};
        for (const table of data?.data ?? []) {
            map[table.id] = table.name;
        }
        return map;
    }, [data?.data]);
}

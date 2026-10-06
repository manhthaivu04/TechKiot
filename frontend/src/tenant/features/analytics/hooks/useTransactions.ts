import { useQuery } from '@tanstack/react-query';
import { transactionsService, type TransactionParams } from '../services/transactions.service';

export const TRANSACTIONS_QUERY_KEY = 'analytics-transactions';

/**
 * Hook tra cứu hóa đơn đã thanh toán.
 * Tự động refetch khi params thay đổi.
 */
export const useTransactions = (params: TransactionParams) => {
    return useQuery({
        queryKey: [TRANSACTIONS_QUERY_KEY, params],
        queryFn:  () => transactionsService.getTransactions(params),
        placeholderData: (prev) => prev, // giữ data cũ khi đang load trang mới
    });
};

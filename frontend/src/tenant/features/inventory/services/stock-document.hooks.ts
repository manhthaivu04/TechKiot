import { useMutation, useQuery } from '@tanstack/react-query';
import { stockDocumentService } from './stock-document.service';
import type { CreateStockDocumentRequest } from '@/types';
import { queryClient } from '@/api/query-client';

const QUERY_KEYS = {
    stockDocuments: 'tenant_stock_documents',
};

export const useStockDocuments = () => {
    return useQuery({
        queryKey: [QUERY_KEYS.stockDocuments],
        queryFn: stockDocumentService.getAll,
    });
};

export const useCreateStockDocument = () => {
    return useMutation({
        mutationFn: (data: CreateStockDocumentRequest) => stockDocumentService.create(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.stockDocuments] });
        },
    });
};

export const useConfirmStockDocument = () => {
    return useMutation({
        mutationFn: (id: string) => stockDocumentService.confirm(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.stockDocuments] });
        },
    });
};

export const useCancelStockDocument = () => {
    return useMutation({
        mutationFn: (id: string) => stockDocumentService.cancel(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.stockDocuments] });
        },
    });
};

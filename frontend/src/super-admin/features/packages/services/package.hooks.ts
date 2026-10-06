import { useMutation, useQuery } from '@tanstack/react-query';
import { packageService } from './package.service';
import type {
    CreatePackageRequest,
    UpdatePackageRequest,
    PackageListParams,
    SyncPackageFeaturesRequest,
} from '@/types';
import { queryClient } from '@/api/query-client';

const packageQueryKey = 'packages';

export const usePackages = (params: PackageListParams) => {
    return useQuery({
        queryKey: [packageQueryKey, 'list', params],
        queryFn: () => packageService.getPackages(params),
    });
};

export const usePackage = (id: string | undefined) => {
    return useQuery({
        queryKey: [packageQueryKey, 'detail', id],
        queryFn: () => packageService.getPackage(id!),
        enabled: !!id,
    });
};

export const useCreatePackage = () => {
    return useMutation({
        mutationFn: (data: CreatePackageRequest) =>
            packageService.createPackage(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [packageQueryKey] });
        },
    });
};

export const useUpdatePackage = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdatePackageRequest) =>
            packageService.updatePackage(id, data),
        onSuccess: (data) => {
            queryClient.setQueryData([packageQueryKey, 'detail', id], data);
            queryClient.invalidateQueries({ queryKey: [packageQueryKey] });
        },
    });
};

export const useTogglePackage = () => {
    return useMutation({
        mutationFn: (id: string) => packageService.togglePackage(id),
        onSuccess: (data, id) => {
            queryClient.setQueryData([packageQueryKey, 'detail', id], data.package);
            queryClient.invalidateQueries({ queryKey: [packageQueryKey] });
        },
    });
};

export const useSyncPackageFeatures = (id: string) => {
    return useMutation({
        mutationFn: (data: SyncPackageFeaturesRequest) =>
            packageService.syncFeatures(id, data),
        onSuccess: (data) => {
            queryClient.setQueryData([packageQueryKey, 'detail', id], data.package);
            queryClient.invalidateQueries({ queryKey: [packageQueryKey] });
        },
    });
};

export const useDeletePackage = () => {
    return useMutation({
        mutationFn: (id: string) => packageService.deletePackage(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [packageQueryKey] });
        },
    });
};

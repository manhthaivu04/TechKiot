import { useMutation, useQuery } from '@tanstack/react-query';
import { featureService } from './feature.service';
import type { CreateFeatureRequest, UpdateFeatureRequest, FeatureListParams } from '@/types';
import { queryClient } from '@/api/query-client';

const featureQueryKey = 'features';

export const useFeatures = (params: FeatureListParams) => {
    return useQuery({
        queryKey: [featureQueryKey, 'list', params],
        queryFn: () => featureService.getFeatures(params),
    });
};

export const useFeature = (id: string | undefined) => {
    return useQuery({
        queryKey: [featureQueryKey, 'detail', id],
        queryFn: () => featureService.getFeature(id!),
        enabled: !!id,
    });
};

export const useCreateFeature = () => {
    return useMutation({
        mutationFn: (data: CreateFeatureRequest) =>
            featureService.createFeature(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [featureQueryKey] });
        },
    });
};

export const useUpdateFeature = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateFeatureRequest) =>
            featureService.updateFeature(id, data),
        onSuccess: (data) => {
            queryClient.setQueryData([featureQueryKey, 'detail', id], data);
            queryClient.invalidateQueries({ queryKey: [featureQueryKey] });
        },
    });
};

export const useToggleFeature = () => {
    return useMutation({
        mutationFn: (id: string) => featureService.toggleFeature(id),
        onSuccess: (data, id) => {
            queryClient.setQueryData([featureQueryKey, 'detail', id], data.feature);
            queryClient.invalidateQueries({ queryKey: [featureQueryKey] });
        },
    });
};

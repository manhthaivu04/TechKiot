import { useMutation, useQuery } from '@tanstack/react-query';
import { subscriptionService } from './subscription.service';
import type { AssignPackageRequest } from '@/types';
import { queryClient } from '@/api/query-client';

const subscriptionQueryKey = 'subscriptions';

export const useRestaurantSubscriptions = (restaurantId: string | undefined) => {
    return useQuery({
        queryKey: [subscriptionQueryKey, 'list', restaurantId],
        queryFn: () => subscriptionService.getRestaurantSubscriptions(restaurantId!),
        enabled: !!restaurantId,
    });
};

export const useActiveSubscription = (restaurantId: string | undefined) => {
    return useQuery({
        queryKey: [subscriptionQueryKey, 'active', restaurantId],
        queryFn: () => subscriptionService.getActiveSubscription(restaurantId!),
        enabled: !!restaurantId,
    });
};

export const useAssignPackage = (restaurantId: string) => {
    return useMutation({
        mutationFn: (data: AssignPackageRequest) =>
            subscriptionService.assignPackage(restaurantId, data),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: [subscriptionQueryKey, 'list', restaurantId],
            });
            queryClient.invalidateQueries({
                queryKey: [subscriptionQueryKey, 'active', restaurantId],
            });
            queryClient.invalidateQueries({ queryKey: ['restaurants'] });
        },
    });
};

export const useCancelSubscription = () => {
    return useMutation({
        mutationFn: (subscriptionId: string) =>
            subscriptionService.cancelSubscription(subscriptionId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [subscriptionQueryKey] });
            queryClient.invalidateQueries({ queryKey: ['restaurants'] });
        },
    });
};

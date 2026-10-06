import { useMutation, useQuery } from '@tanstack/react-query';
import { restaurantService } from './restaurant.service';
import type {
    RestaurantOnboardRequest,
    UpdateRestaurantRequest,
    RestaurantListParams,
} from '@/types';
import { queryClient } from '@/api/query-client';

const restaurantQueryKey = 'restaurants';

export const useRestaurants = (params: RestaurantListParams) => {
    return useQuery({
        queryKey: [restaurantQueryKey, 'list', params],
        queryFn: () => restaurantService.getRestaurants(params),
    });
};

export const useRestaurant = (id: string | undefined) => {
    return useQuery({
        queryKey: [restaurantQueryKey, 'detail', id],
        queryFn: () => restaurantService.getRestaurant(id!),
        enabled: !!id,
    });
};

export const useCreateRestaurant = () => {
    return useMutation({
        mutationFn: (data: RestaurantOnboardRequest) =>
            restaurantService.createRestaurant(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [restaurantQueryKey] });
        },
    });
};

export const useUpdateRestaurant = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateRestaurantRequest) =>
            restaurantService.updateRestaurant(id, data),
        onSuccess: (data) => {
            queryClient.setQueryData([restaurantQueryKey, 'detail', id], data);
            queryClient.invalidateQueries({ queryKey: [restaurantQueryKey] });
        },
    });
};

export const useLockRestaurant = (id: string) => {
    return useMutation({
        mutationFn: () => restaurantService.lockRestaurant(id),
        onSuccess: (data) => {
            queryClient.setQueryData(
                [restaurantQueryKey, 'detail', id],
                data.restaurant
            );
            queryClient.invalidateQueries({ queryKey: [restaurantQueryKey] });
        },
    });
};

export const useUnlockRestaurant = (id: string) => {
    return useMutation({
        mutationFn: () => restaurantService.unlockRestaurant(id),
        onSuccess: (data) => {
            queryClient.setQueryData(
                [restaurantQueryKey, 'detail', id],
                data.restaurant
            );
            queryClient.invalidateQueries({ queryKey: [restaurantQueryKey] });
        },
    });
};

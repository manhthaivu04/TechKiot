import apiClient from '@/api/http';
import type {
    Restaurant,
    RestaurantOnboardRequest,
    UpdateRestaurantRequest,
    RestaurantListParams,
    LockRestaurantResponse,
    UnlockRestaurantResponse,
    PaginatedResponse,
} from '@/types';

export const restaurantService = {
    // List
    getRestaurants: (params: RestaurantListParams) =>
        apiClient
            .get<PaginatedResponse<Restaurant>>('/admin/restaurants', { params })
            .then((res) => res.data),

    // Get single
    getRestaurant: (id: string) =>
        apiClient
            .get<Restaurant>(`/admin/restaurants/${id}`)
            .then((res) => res.data),

    // Onboard (Create)
    createRestaurant: (data: RestaurantOnboardRequest) =>
        apiClient
            .post<{ restaurant: Restaurant; subscription: unknown; owner: unknown }>('/admin/restaurants/onboard', data)
            .then((res) => res.data),

    // Update
    updateRestaurant: (id: string, data: UpdateRestaurantRequest) =>
        apiClient
            .put<Restaurant>(`/admin/restaurants/${id}`, data)
            .then((res) => res.data),

    // Lock
    lockRestaurant: (id: string) =>
        apiClient
            .patch<LockRestaurantResponse>(`/admin/restaurants/${id}/lock`)
            .then((res) => res.data),

    // Unlock
    unlockRestaurant: (id: string) =>
        apiClient
            .patch<UnlockRestaurantResponse>(`/admin/restaurants/${id}/unlock`)
            .then((res) => res.data),
};

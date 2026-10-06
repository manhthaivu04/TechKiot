import apiClient from '@/api/http';
import type {
    Subscription,
    AssignPackageRequest,
    AssignPackageResponse,
    CancelSubscriptionResponse,
} from '@/types';

export const subscriptionService = {
    // Get subscriptions for a restaurant
    getRestaurantSubscriptions: (restaurantId: string) =>
        apiClient
            .get<Subscription[]>(`/admin/restaurants/${restaurantId}/subscriptions`)
            .then((res) => res.data),

    // Get active subscription for a restaurant
    getActiveSubscription: (restaurantId: string) =>
        apiClient
            .get<Subscription>(
                `/admin/restaurants/${restaurantId}/subscriptions/active`
            )
            .then((res) => res.data)
            .catch((error) => {
                // 404 means no active subscription
                if (error.response?.status === 404) {
                    return null;
                }
                throw error;
            }),

    // Assign package to restaurant
    assignPackage: (restaurantId: string, data: AssignPackageRequest) =>
        apiClient
            .post<AssignPackageResponse>(
                `/admin/restaurants/${restaurantId}/subscriptions`,
                data
            )
            .then((res) => res.data),

    // Cancel subscription
    cancelSubscription: (subscriptionId: string) =>
        apiClient
            .patch<CancelSubscriptionResponse>(
                `/admin/subscriptions/${subscriptionId}/cancel`
            )
            .then((res) => res.data),
};

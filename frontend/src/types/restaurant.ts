import type { Subscription } from './subscription';

export interface Restaurant {
    id: string;
    name: string;
    address: string | null;
    phone: string | null;
    public_order_token: string | null;
    status: string;
    status_label: string;
    active_subscription: Subscription | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface RestaurantOnboardRequest {
    restaurant: {
        name: string;
        address?: string | null;
        phone?: string | null;
    };
    package_id: string;
    owner: {
        name: string;
        email: string;
        password?: string;
        is_active: boolean;
    };
}

export interface UpdateRestaurantRequest {
    name: string;
    address?: string | null;
    phone?: string | null;
}

export interface RestaurantListParams {
    status?: string;
    search?: string;
    per_page?: number;
    page?: number;
}

export interface LockRestaurantResponse {
    message: string;
    restaurant: Restaurant;
}

export interface UnlockRestaurantResponse {
    message: string;
    restaurant: Restaurant;
}

import type { Package, PackagePrice } from './package';

export interface Subscription {
    id: string;
    restaurant_id: string;
    package: Package;
    package_price?: PackagePrice;
    start_date: string | null;
    end_date: string | null;
    status: string;
    status_label: string;
    activated_at: string | null;
    cancelled_at: string | null;
    created_at: string | null;
}

export interface AssignPackageRequest {
    package_id: string;
    package_price_id?: string;
}

export interface AssignPackageResponse {
    message: string;
    subscription: Subscription;
}

export interface CancelSubscriptionResponse {
    message: string;
    subscription: Subscription;
}

export interface NoActiveSubscriptionResponse {
    message: string;
}

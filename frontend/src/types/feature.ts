export enum FeatureCode {
    // Basic
    MENU_MANAGEMENT = 'MENU_MANAGEMENT',
    POS_QUICK_ORDER = 'POS_QUICK_ORDER',
    QR_STATIC_ORDER = 'QR_STATIC_ORDER',
    DAILY_REVENUE = 'DAILY_REVENUE',

    // Pro
    TABLE_MANAGEMENT = 'TABLE_MANAGEMENT',
    STAFF_MANAGEMENT = 'STAFF_MANAGEMENT',
    QR_TABLE_ORDER = 'QR_TABLE_ORDER',
    DETAIL_REPORT = 'DETAIL_REPORT',

    // Premium
    INVENTORY_MANAGEMENT = 'INVENTORY_MANAGEMENT',
    RECIPE_MANAGEMENT = 'RECIPE_MANAGEMENT',
    STOCK_AUDIT = 'STOCK_AUDIT',
}

export interface Feature {
    id: string;
    code: string;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface CreateFeatureRequest {
    code: string;
    name: string;
    description?: string | null;
    is_active?: boolean;
}

export interface UpdateFeatureRequest {
    code: string;
    name: string;
    description?: string | null;
    is_active?: boolean;
}

export interface FeatureListParams {
    is_active?: boolean;
    search?: string;
    per_page?: number;
    page?: number;
}

export interface ToggleFeatureResponse {
    message: string;
    feature: Feature;
}

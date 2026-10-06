export enum UserRole {
    SUPER_ADMIN = 'SUPER_ADMIN',
    OWNER = 'OWNER',
    MANAGER = 'MANAGER',
    WAITER = 'WAITER',
    KITCHEN = 'KITCHEN',
    CASHIER = 'CASHIER',
}

export interface User {
    id: string;
    name: string;
    email: string;
    role: UserRole | string;
    role_label: string;
    is_active: boolean;
    restaurant_id: string | null;
    features: string[]; // List of active feature codes based on subscription
    last_login_at: string | null;
    created_at: string | null;
}

export interface LoginRequest {
    email: string;
    password: string;
}

export interface LoginResponse {
    message: string;
    token: string;
    user: User;
}

export interface LogoutResponse {
    message: string;
}

export type AuthMeResponse = User;

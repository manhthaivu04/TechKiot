import { User, UserRole } from './auth';
import { PaginatedResponse, ApiResponse } from './api';

export interface StaffListParams {
    q?: string;
    role?: UserRole | string;
    is_active?: boolean;
    per_page?: number;
    page?: number;
}

export interface CreateStaffRequest {
    name: string;
    email: string;
    password?: string;
    role: UserRole | string;
    is_active: boolean;
}

export interface UpdateStaffRequest {
    name?: string;
    email?: string;
    password?: string;
    role?: UserRole | string;
    is_active?: boolean;
}

export type StaffResponse = ApiResponse<User>;
export type StaffListResponse = PaginatedResponse<User>;

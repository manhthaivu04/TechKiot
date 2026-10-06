import apiClient from '@/api/http';
import type { LoginRequest, LoginResponse, AuthMeResponse, LogoutResponse } from '@/types';

export const authService = {
    login: (data: LoginRequest) =>
        apiClient.post<LoginResponse>('/auth/login', data).then((res) => res.data),

    logout: () =>
        apiClient.post<LogoutResponse>('/auth/logout').then((res) => res.data),

    getMe: () =>
        apiClient.get<AuthMeResponse>('/auth/me').then((res) => res.data),
};

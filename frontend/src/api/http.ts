import axios, { AxiosError, AxiosInstance } from 'axios';
import { useAuthStore } from '@/store/auth.store';

export interface ApiErrorResponse {
    message: string;
    errors?: Record<string, string[]>;
}

const apiClient: AxiosInstance = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 30000,
});

// Request interceptor
apiClient.interceptors.request.use(
    (config) => {
        const token = useAuthStore.getState().token;
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor
apiClient.interceptors.response.use(
    (response) => response,
    (error: AxiosError<ApiErrorResponse>) => {
        if (error.response?.status === 401) {
            const authStore = useAuthStore.getState();
            authStore.logout();
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default apiClient;

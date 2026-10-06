import { useEffect } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { authService } from './auth.service';
import type { LoginRequest } from '@/types';
import { useAuthStore } from '@/store/auth.store';
import { queryClient } from '@/api/query-client';

export const useLogin = () => {
    const setAuth = useAuthStore((state) => state.setAuth);

    return useMutation({
        mutationFn: (data: LoginRequest) => authService.login(data),
        onSuccess: (data) => {
            setAuth(data.token, data.user);
            queryClient.setQueryData(['auth', 'me'], data.user);
        },
    });
};

export const useLogout = () => {
    const logout = useAuthStore((state) => state.logout);

    return useMutation({
        mutationFn: () => authService.logout(),
        onSuccess: () => {
            logout();
            queryClient.invalidateQueries();
        },
    });
};

export const useAuthMe = () => {
    const setUser = useAuthStore((state) => state.setUser);
    const token = useAuthStore((state) => state.token);

    const query = useQuery({
        queryKey: ['auth', 'me'],
        queryFn: () => authService.getMe(),
        enabled: !!token,
    });

    useEffect(() => {
        if (query.data) setUser(query.data);
    }, [query.data, setUser]);

    return query;
};

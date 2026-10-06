import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User } from '@/types';

interface AuthStore {
    user: User | null;
    token: string | null;
    isAuthenticated: boolean;
    setAuth: (token: string, user: User) => void;
    setUser: (user: User) => void;
    logout: () => void;
}

export const useAuthStore = create<AuthStore>()(
    persist(
        (set) => ({
            user: null,
            token: null,
            isAuthenticated: false,
            setAuth: (token, user) =>
                set({
                    token,
                    user,
                    isAuthenticated: !!user,
                }),
            setUser: (user) =>
                set({
                    user,
                    isAuthenticated: !!user,
                }),
            logout: () =>
                set({
                    user: null,
                    token: null,
                    isAuthenticated: false,
                }),
        }),
        {
            name: 'auth-store',
            partialize: (state) => ({
                token: state.token,
                user: state.user,
            }),
        }
    )
);

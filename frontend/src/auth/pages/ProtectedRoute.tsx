import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Spin } from 'antd';
import { useAuthStore } from '@/store/auth.store';
import { useAuthMe } from '../services/auth.hooks';
import { UserRole } from '@/types/auth';

interface ProtectedRouteProps {
    children: React.ReactNode;
    allowedRoles: UserRole[];
}

export const ProtectedRoute = ({ children, allowedRoles }: ProtectedRouteProps) => {
    const navigate = useNavigate();
    const token = useAuthStore((state) => state.token);
    const user = useAuthStore((state) => state.user);
    const { isLoading } = useAuthMe();

    useEffect(() => {
        if (!token) {
            navigate('/login', { replace: true });
            return;
        }

        if (user && !allowedRoles.includes(user.role as UserRole)) {
            // Nếu là Super Admin mà vào nhầm trang Tenant, cho về Dashboard Admin
            if (user.role === UserRole.SUPER_ADMIN) {
                navigate('/super-admin', { replace: true });
            } 
            // Nếu là Tenant mà vào nhầm trang Admin, cho về Portal
            else if ([UserRole.OWNER, UserRole.MANAGER, UserRole.WAITER, UserRole.KITCHEN, UserRole.CASHIER].includes(user.role as UserRole)) {
                navigate('/portal', { replace: true });
            } else {
                navigate('/unauthorized', { replace: true });
            }
        }
    }, [token, user, navigate, allowedRoles]);

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <Spin />
            </div>
        );
    }

    if (!token || (user && !allowedRoles.includes(user.role as UserRole))) {
        return null;
    }

    return <>{children}</>;
};

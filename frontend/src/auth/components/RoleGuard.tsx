import React from 'react';
import { useAuthStore } from '@/store/auth.store';
import { UserRole } from '@/types/auth';
import { UnauthorizedPage } from '@/pages/errors/UnauthorizedPage';

interface RoleGuardProps {
    children: React.ReactNode;
    allowedRoles: UserRole[];
    fallback?: React.ReactNode;
}

/**
 * Conditionally renders children if the current user has the required role.
 */
export const RoleGuard = ({
    children,
    allowedRoles,
    fallback = <UnauthorizedPage />,
}: RoleGuardProps) => {
    const user = useAuthStore((state) => state.user);

    if (!user || !allowedRoles.includes(user.role as UserRole)) {
        return <>{fallback}</>;
    }

    return <>{children}</>;
};

import React from 'react';
import { useFeatureFlag, useAnyFeatureFlag, useAllFeatureFlags } from '../hooks/useFeatureFlag';
import { FeatureCode } from '@/types';

interface FeatureGuardProps {
    children: React.ReactNode;
    fallback?: React.ReactNode;
    feature?: FeatureCode;
    anyFeatures?: FeatureCode[];
    allFeatures?: FeatureCode[];
}

/**
 * Conditionally renders children if the current user's subscription includes the required feature(s).
 *
 * Usage:
 * <FeatureGuard feature={FeatureCode.TABLE_MANAGEMENT}>
 *    <Button>Quản lý bàn</Button>
 * </FeatureGuard>
 */
export const FeatureGuard = ({
    children,
    fallback = null,
    feature,
    anyFeatures,
    allFeatures,
}: FeatureGuardProps) => {
    let hasAccess = false;

    // We need to call the hooks, but conditionally using the return value.
    // To respect Rules of Hooks, we always call them but with fallback arrays if empty.
    const hasSingle = useFeatureFlag(feature || FeatureCode.MENU_MANAGEMENT);
    const hasAny = useAnyFeatureFlag(anyFeatures || []);
    const hasAll = useAllFeatureFlags(allFeatures || []);

    if (feature) {
        hasAccess = hasSingle;
    } else if (anyFeatures && anyFeatures.length > 0) {
        hasAccess = hasAny;
    } else if (allFeatures && allFeatures.length > 0) {
        hasAccess = hasAll;
    } else {
        // If no feature requirement is provided, grant access by default
        hasAccess = true;
    }

    if (!hasAccess) {
        return <>{fallback}</>;
    }

    return <>{children}</>;
};

import { useAuthStore } from '@/store/auth.store';
import { FeatureCode, UserRole } from '@/types';

export const useFeatureFlag = (featureCode: FeatureCode): boolean => {
    const { user } = useAuthStore();

    // Super Admin has access to everything
    if (user?.role === UserRole.SUPER_ADMIN) {
        return true;
    }

    // Check if the feature code exists in the user's features array
    return !!user?.features?.includes(featureCode);
};

export const useAnyFeatureFlag = (featureCodes: FeatureCode[]): boolean => {
    const { user } = useAuthStore();

    if (user?.role === UserRole.SUPER_ADMIN) {
        return true;
    }

    return featureCodes.some((code) => user?.features?.includes(code));
};

export const useAllFeatureFlags = (featureCodes: FeatureCode[]): boolean => {
    const { user } = useAuthStore();

    if (user?.role === UserRole.SUPER_ADMIN) {
        return true;
    }

    return featureCodes.every((code) => user?.features?.includes(code));
};

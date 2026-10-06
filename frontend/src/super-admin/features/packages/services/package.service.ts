import apiClient from '@/api/http';
import type {
    Package,
    CreatePackageRequest,
    UpdatePackageRequest,
    PackageListParams,
    TogglePackageResponse,
    SyncPackageFeaturesRequest,
    SyncPackageFeaturesResponse,
    PaginatedResponse,
} from '@/types';

export const packageService = {
    // List
    getPackages: (params: PackageListParams) =>
        apiClient
            .get<PaginatedResponse<Package>>('/admin/packages', { params })
            .then((res) => res.data),

    // Get single
    getPackage: (id: string) =>
        apiClient
            .get<Package>(`/admin/packages/${id}`)
            .then((res) => res.data),

    // Create
    createPackage: (data: CreatePackageRequest) =>
        apiClient
            .post<Package>('/admin/packages', data)
            .then((res) => res.data),

    // Update
    updatePackage: (id: string, data: UpdatePackageRequest) =>
        apiClient
            .put<Package>(`/admin/packages/${id}`, data)
            .then((res) => res.data),

    // Toggle
    togglePackage: (id: string) =>
        apiClient
            .patch<TogglePackageResponse>(`/admin/packages/${id}/toggle`)
            .then((res) => res.data),

    // Sync features
    syncFeatures: (id: string, data: SyncPackageFeaturesRequest) =>
        apiClient
            .put<SyncPackageFeaturesResponse>(`/admin/packages/${id}/features`, data)
            .then((res) => res.data),

    // Delete
    deletePackage: (id: string) =>
        apiClient
            .delete(`/admin/packages/${id}`)
            .then((res) => res.data),
};

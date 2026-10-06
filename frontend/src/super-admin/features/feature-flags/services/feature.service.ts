import apiClient from '@/api/http';
import type {
    Feature,
    CreateFeatureRequest,
    UpdateFeatureRequest,
    FeatureListParams,
    ToggleFeatureResponse,
    PaginatedResponse,
} from '@/types';

export const featureService = {
    // List
    getFeatures: (params: FeatureListParams) =>
        apiClient
            .get<PaginatedResponse<Feature>>('/admin/features', { params })
            .then((res) => res.data),

    // Get single
    getFeature: (id: string) =>
        apiClient
            .get<Feature>(`/admin/features/${id}`)
            .then((res) => res.data),

    // Create
    createFeature: (data: CreateFeatureRequest) =>
        apiClient
            .post<Feature>('/admin/features', data)
            .then((res) => res.data),

    // Update
    updateFeature: (id: string, data: UpdateFeatureRequest) =>
        apiClient
            .put<Feature>(`/admin/features/${id}`, data)
            .then((res) => res.data),

    // Toggle
    toggleFeature: (id: string) =>
        apiClient
            .patch<ToggleFeatureResponse>(`/admin/features/${id}/toggle`)
            .then((res) => res.data),
};

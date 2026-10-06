import apiClient from '@/api/http';
import {
    StaffListParams,
    StaffListResponse,
    StaffResponse,
    CreateStaffRequest,
    UpdateStaffRequest,
} from '@/types/staff';

export const staffService = {
    getStaffs: async (params?: StaffListParams): Promise<StaffListResponse> => {
        const { data } = await apiClient.get('/tenant/staff', { params });
        return data;
    },

    getStaff: async (id: string): Promise<StaffResponse> => {
        const { data } = await apiClient.get(`/tenant/staff/${id}`);
        return data;
    },

    createStaff: async (payload: CreateStaffRequest): Promise<StaffResponse> => {
        const { data } = await apiClient.post('/tenant/staff', payload);
        return data;
    },

    updateStaff: async (id: string, payload: UpdateStaffRequest): Promise<StaffResponse> => {
        const { data } = await apiClient.put(`/tenant/staff/${id}`, payload);
        return data;
    },

    deactivateStaff: async (id: string): Promise<StaffResponse> => {
        const { data } = await apiClient.delete(`/tenant/staff/${id}`);
        return data;
    },
};

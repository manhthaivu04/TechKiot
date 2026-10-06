import { useMutation, useQuery } from '@tanstack/react-query';
import { staffService } from './staff.service';
import type { CreateStaffRequest, UpdateStaffRequest, StaffListParams } from '@/types';
import { queryClient } from '@/api/query-client';

const QUERY_KEYS = {
    staff: 'tenant_staff',
};

export const useStaffList = (params?: StaffListParams) => {
    return useQuery({
        queryKey: [QUERY_KEYS.staff, params],
        queryFn: () => staffService.getStaffs(params),
    });
};

export const useStaff = (id: string) => {
    return useQuery({
        queryKey: [QUERY_KEYS.staff, id],
        queryFn: () => staffService.getStaff(id),
        enabled: !!id,
    });
};

export const useCreateStaff = () => {
    return useMutation({
        mutationFn: (data: CreateStaffRequest) => staffService.createStaff(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.staff] });
        },
    });
};

export const useUpdateStaff = (id: string) => {
    return useMutation({
        mutationFn: (data: UpdateStaffRequest) => staffService.updateStaff(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.staff] });
        },
    });
};

export const useDeactivateStaff = () => {
    return useMutation({
        mutationFn: (id: string) => staffService.deactivateStaff(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.staff] });
        },
    });
};

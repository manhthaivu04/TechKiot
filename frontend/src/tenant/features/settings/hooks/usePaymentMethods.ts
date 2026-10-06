import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { notification } from 'antd';
import {
  getPaymentMethodSettings,
  togglePaymentMethod,
  updatePaymentMethod,
  uploadPaymentQR,
  deletePaymentQR,
} from '../api/payment-methods.api';
import { PaymentMethodEnum } from '../types/payment-methods';
import { ApiErrorResponse } from '@/api/http';
import { AxiosError } from 'axios';

export const PAYMENT_METHODS_QUERY_KEY = ['paymentMethodSettings'];

// Utility to handle API errors
const handleError = (error: unknown, defaultMessage: string) => {
  const axiosError = error as AxiosError<ApiErrorResponse>;
  if (axiosError.response?.status === 403) {
    notification.error({
      message: 'Lỗi phân quyền',
      description: 'Bạn không có quyền thay đổi cấu hình thanh toán.',
    });
  } else {
    notification.error({
      message: 'Lỗi',
      description: axiosError.response?.data?.message || defaultMessage,
    });
  }
};

export const usePaymentMethods = () => {
  return useQuery({
    queryKey: PAYMENT_METHODS_QUERY_KEY,
    queryFn: getPaymentMethodSettings,
  });
};

export const useTogglePaymentMethod = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: togglePaymentMethod,
    onSuccess: () => {
      notification.success({ message: 'Thành công', description: 'Đã cập nhật trạng thái.' });
      queryClient.invalidateQueries({ queryKey: PAYMENT_METHODS_QUERY_KEY });
    },
    onError: (error) => handleError(error, 'Không thể thay đổi trạng thái phương thức thanh toán.'),
  });
};

export const useUpdatePaymentMethod = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ method, displayName }: { method: PaymentMethodEnum; displayName: string | null }) =>
      updatePaymentMethod(method, { display_name: displayName }),
    onSuccess: () => {
      notification.success({ message: 'Thành công', description: 'Đã cập nhật tên hiển thị.' });
      queryClient.invalidateQueries({ queryKey: PAYMENT_METHODS_QUERY_KEY });
    },
    onError: (error) => handleError(error, 'Không thể cập nhật tên hiển thị.'),
  });
};

export const useUploadPaymentQR = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: uploadPaymentQR,
    onSuccess: () => {
      notification.success({ message: 'Thành công', description: 'Đã tải lên mã QR.' });
      queryClient.invalidateQueries({ queryKey: PAYMENT_METHODS_QUERY_KEY });
    },
    onError: (error) => handleError(error, 'Không thể tải lên mã QR. Vui lòng kiểm tra lại định dạng và dung lượng ảnh.'),
  });
};

export const useDeletePaymentQR = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: deletePaymentQR,
    onSuccess: () => {
      notification.success({ message: 'Thành công', description: 'Đã xoá mã QR.' });
      queryClient.invalidateQueries({ queryKey: PAYMENT_METHODS_QUERY_KEY });
    },
    onError: (error) => handleError(error, 'Không thể xoá mã QR.'),
  });
};

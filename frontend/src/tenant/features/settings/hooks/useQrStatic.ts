import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { notification } from 'antd';
import { getQrStatic } from '../api/qr-static.api';
import { ApiErrorResponse } from '@/api/http';
import { AxiosError } from 'axios';

export const QR_STATIC_QUERY_KEY = ['qrStaticSettings'];

const handleError = (error: unknown, defaultMessage: string) => {
  const axiosError = error as AxiosError<ApiErrorResponse>;
  if (axiosError.response?.status === 403) {
    notification.error({
      message: 'Lỗi phân quyền',
      description: 'Bạn không có quyền quản lý QR Menu.',
    });
  } else {
    notification.error({
      message: 'Lỗi',
      description: axiosError.response?.data?.message || defaultMessage,
    });
  }
};

export const useQrStatic = () => {
  return useQuery({
    queryKey: QR_STATIC_QUERY_KEY,
    queryFn: () => getQrStatic(false),
  });
};

export const useRegenerateQrStatic = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => getQrStatic(true),
    onSuccess: (data) => {
      notification.success({
        message: 'Thành công',
        description: 'Đã tạo mới và cập nhật mã QR Menu tĩnh thành công.',
      });
      queryClient.setQueryData(QR_STATIC_QUERY_KEY, data);
    },
    onError: (error) => handleError(error, 'Không thể tạo mới mã QR Menu.'),
  });
};

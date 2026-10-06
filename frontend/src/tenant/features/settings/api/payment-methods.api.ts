import apiClient from '@/api/http';
import {
  PaymentMethodSetting,
  TogglePaymentMethodResponse,
  UpdatePaymentMethodRequest,
  PaymentMethodEnum,
} from '../types/payment-methods';

const BASE_URL = '/tenant/payment-method-settings';

export const getPaymentMethodSettings = async (): Promise<PaymentMethodSetting[]> => {
  const response = await apiClient.get<{ data: PaymentMethodSetting[] }>(BASE_URL);
  return response.data.data;
};

export const togglePaymentMethod = async (method: PaymentMethodEnum): Promise<PaymentMethodSetting> => {
  const response = await apiClient.patch<TogglePaymentMethodResponse>(`${BASE_URL}/${method}/toggle`);
  return response.data.data;
};

export const updatePaymentMethod = async (
  method: PaymentMethodEnum,
  data: UpdatePaymentMethodRequest
): Promise<PaymentMethodSetting> => {
  const response = await apiClient.patch<{ data: PaymentMethodSetting }>(`${BASE_URL}/${method}`, data);
  return response.data.data;
};

export const uploadPaymentQR = async (file: File): Promise<PaymentMethodSetting> => {
  const formData = new FormData();
  formData.append('qr_image', file);

  // We override the default application/json header for FormData
  const response = await apiClient.post<{ data: PaymentMethodSetting }>(`${BASE_URL}/transfer/qr`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return response.data.data;
};

export const deletePaymentQR = async (): Promise<PaymentMethodSetting> => {
  const response = await apiClient.delete<{ data: PaymentMethodSetting }>(`${BASE_URL}/transfer/qr`);
  return response.data.data;
};

import apiClient from '@/api/http';
import { QrStaticData } from '../types/qr-static';

export const getQrStatic = async (forceRegenerate?: boolean): Promise<QrStaticData> => {
  const params: Record<string, string> = {};
  if (forceRegenerate) {
    params.force_regenerate = '1';
  }
  const response = await apiClient.get<{ data: QrStaticData }>('/tenant/restaurant/qr-code', { params });
  return response.data.data;
};

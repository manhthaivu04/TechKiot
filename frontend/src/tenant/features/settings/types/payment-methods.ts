export type PaymentMethodEnum = 'cash' | 'card' | 'transfer' | 'ewallet';

export interface PaymentMethodSetting {
  id: string;
  payment_method: PaymentMethodEnum;
  method_label: string;
  is_active: boolean;
  display_name: string | null;
  qr_code_url: string | null;
  updated_at: string;
}

export interface TogglePaymentMethodResponse {
  success: boolean;
  data: PaymentMethodSetting;
  message?: string;
}

export interface UpdatePaymentMethodRequest {
  display_name: string | null;
}

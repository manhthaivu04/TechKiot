export interface QrStaticData {
  url: string;
  qr_code_url: string;
  qr_code: string;
}

export interface QrStaticResponse {
  code: string;
  message: string;
  data: QrStaticData;
}

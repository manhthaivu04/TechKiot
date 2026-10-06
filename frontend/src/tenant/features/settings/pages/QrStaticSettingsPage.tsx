import React from 'react';
import { Typography, Card, Button, Spin, Alert, Tooltip, message } from 'antd';
import {
  QrcodeOutlined,
  DownloadOutlined,
  ReloadOutlined,
  CopyOutlined,
  LinkOutlined,
} from '@ant-design/icons';
import { useQrStatic, useRegenerateQrStatic } from '../hooks/useQrStatic';

const { Title, Text } = Typography;

export const QrStaticSettingsPage: React.FC = () => {
  const { data, isLoading, isError, error } = useQrStatic();
  const regenerateMutation = useRegenerateQrStatic();

  const handleDownload = () => {
    if (!data?.qr_code) return;
    
    // Create an anchor element to trigger download from base64
    const link = document.createElement('a');
    link.href = data.qr_code;
    link.download = 'qr-menu-tinh.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success('Đang tải xuống hình ảnh QR Code...');
  };

  const handleRegenerate = () => {
    regenerateMutation.mutate();
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Spin size="large" tip="Đang tải cấu hình QR Menu tĩnh..." />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="p-6">
        <Alert
          message="Lỗi tải dữ liệu"
          description={error?.message || 'Không thể lấy cấu hình QR Menu tĩnh.'}
          type="error"
          showIcon
        />
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      <div>
        <Title level={3} className="!mb-1 text-emerald-900">
          Mã QR Menu Tĩnh
        </Title>
        <Text type="secondary">
          Mã QR chung dành cho toàn bộ nhà hàng, dẫn trực tiếp khách hàng tới trang thực đơn trực tuyến công khai.
        </Text>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-5 gap-6">
        {/* QR Code Display Card */}
        <Card
          className="md:col-span-2 flex flex-col items-center justify-center p-6 border-emerald-50 rounded-2xl shadow-sm text-center bg-white"
          bodyStyle={{ display: 'flex', flexDirection: 'column', alignItems: 'center', width: '100%' }}
        >
          <div className="bg-emerald-50/50 p-6 rounded-2xl border border-emerald-100/50 mb-4 inline-block">
            {data?.qr_code ? (
              <img
                src={data.qr_code}
                alt="Static QR Code"
                className="w-48 h-48 object-contain rounded-lg shadow-sm border border-emerald-100"
              />
            ) : (
              <div className="w-48 h-48 flex items-center justify-center bg-gray-100 rounded-lg">
                <QrcodeOutlined className="text-4xl text-gray-300" />
              </div>
            )}
          </div>
          
          <Button
            type="primary"
            icon={<DownloadOutlined />}
            size="large"
            onClick={handleDownload}
            className="w-full bg-emerald-600 hover:bg-emerald-500 border-none rounded-lg"
          >
            Tải xuống PNG
          </Button>
        </Card>

        {/* Info & Settings Card */}
        <Card className="md:col-span-3 border-emerald-50 rounded-2xl shadow-sm bg-white">
          <div className="space-y-6">
            <div>
              <Title level={4} className="!mb-2 text-emerald-800 flex items-center gap-2">
                <LinkOutlined />
                Đường dẫn Menu công khai
              </Title>
              <div className="text-gray-600 bg-gray-50 p-3 rounded-lg border border-gray-100 break-all select-all font-mono text-sm flex items-center justify-between gap-2">
                <span>{data?.url}</span>
                <Tooltip title="Sao chép liên kết">
                  <Button
                    type="text"
                    icon={<CopyOutlined />}
                    onClick={() => {
                      if (data?.url) {
                        navigator.clipboard.writeText(data.url);
                        message.success('Đã sao chép đường dẫn menu!');
                      }
                    }}
                    className="hover:text-emerald-600 text-gray-400"
                  />
                </Tooltip>
              </div>
            </div>

            <div className="border-t border-gray-100 pt-6">
              <Title level={4} className="!mb-2 text-emerald-800">
                Hướng dẫn sử dụng
              </Title>
              <ul className="list-disc pl-5 space-y-2 text-gray-600 text-sm">
                <li>
                  <strong className="text-gray-800">In & Dán:</strong> Tải mã QR về máy, in và dán tại quầy lễ tân, bàn ăn hoặc khu vực dễ quan sát để khách hàng dễ dàng quét bằng camera điện thoại.
                </li>
                <li>
                  <strong className="text-gray-800">Tự động đồng bộ:</strong> Thực đơn hiển thị cho khách hàng luôn được đồng bộ trực tiếp với danh sách món đang hiển thị trên hệ thống.
                </li>
                <li>
                  <strong className="text-gray-800">Mã QR tĩnh:</strong> Đây là liên kết cố định của nhà hàng, bạn không cần phải in lại mã QR mỗi khi thay đổi thực đơn.
                </li>
              </ul>
            </div>

            <div className="border-t border-gray-100 pt-6 flex items-center justify-between">
              <div>
                <span className="block text-sm font-semibold text-gray-700">Tạo lại mã QR</span>
                <span className="text-xs text-gray-500 block">
                  Bắt buộc hệ thống cập nhật lại dữ liệu và tải lại ảnh lên Cloudinary
                </span>
              </div>
              <Button
                icon={<ReloadOutlined />}
                loading={regenerateMutation.isPending}
                onClick={handleRegenerate}
                className="border-emerald-200 text-emerald-700 hover:text-emerald-600 hover:border-emerald-400 rounded-lg"
              >
                Cập nhật mới
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
};

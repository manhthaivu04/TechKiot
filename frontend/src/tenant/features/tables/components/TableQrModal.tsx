import React from 'react';
import { Modal, Button, Spin, Alert, Typography, Tooltip, message } from 'antd';
import {
  DownloadOutlined,
  ReloadOutlined,
  CopyOutlined,
  LinkOutlined,
  QrcodeOutlined
} from '@ant-design/icons';
import { useTableQrCode, useRegenerateTableQrCode } from '../services/table.hooks';
import type { RestaurantTable } from '@/types';

const { Text } = Typography;

interface TableQrModalProps {
  visible: boolean;
  table: RestaurantTable | null;
  onClose: () => void;
}

export const TableQrModal: React.FC<TableQrModalProps> = ({ visible, table, onClose }) => {
  const tableId = table?.id || null;
  const { data, isLoading, isError, error } = useTableQrCode(tableId);
  const regenerateMutation = useRegenerateTableQrCode(tableId);

  const qrData = data?.data;

  const handleDownload = () => {
    if (!qrData?.qr_code) return;
    
    const link = document.createElement('a');
    link.href = qrData.qr_code;
    link.download = `qr-ban-${table?.name || 'table'}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success('Đang tải xuống hình ảnh QR Code...');
  };

  const handleRegenerate = () => {
    regenerateMutation.mutate(undefined, {
      onSuccess: () => {
        message.success('Đã tạo mới mã QR cho bàn!');
      }
    });
  };

  return (
    <Modal
      title={
        <div className="text-emerald-950 font-bold text-lg flex items-center gap-2">
          <QrcodeOutlined className="text-emerald-600" />
          Mã QR Bàn: {table?.name}
        </div>
      }
      open={visible}
      onCancel={onClose}
      footer={[
        <Button key="close" onClick={onClose} className="rounded-lg">
          Đóng
        </Button>,
      ]}
      width={450}
      centered
      destroyOnClose
    >
      <div className="py-4 flex flex-col items-center justify-center space-y-6">
        {isLoading ? (
          <div className="h-64 flex flex-col items-center justify-center space-y-2">
            <Spin size="large" />
            <Text type="secondary">Đang tải mã QR của bàn...</Text>
          </div>
        ) : isError ? (
          <Alert
            message="Lỗi tải QR Code"
            description={error?.message || 'Không thể lấy thông tin QR của bàn.'}
            type="error"
            showIcon
            className="w-full"
          />
        ) : (
          <>
            {/* QR Code Frame */}
            <div className="bg-emerald-50/40 p-5 rounded-2xl border border-emerald-100/50 text-center">
              {qrData?.qr_code ? (
                <img
                  src={qrData.qr_code}
                  alt={`QR Code ${table?.name}`}
                  className="w-48 h-48 object-contain rounded-lg shadow-sm border border-emerald-100 bg-white"
                />
              ) : (
                <div className="w-48 h-48 flex items-center justify-center bg-gray-100 rounded-lg">
                  <QrcodeOutlined className="text-4xl text-gray-300" />
                </div>
              )}
            </div>

            {/* Public Link Copy Section */}
            <div className="w-full space-y-1">
              <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1">
                <LinkOutlined />
                Đường dẫn gọi món tại bàn
              </span>
              <div className="text-gray-600 bg-gray-50 p-2 rounded-lg border border-gray-100 break-all select-all font-mono text-xs flex items-center justify-between gap-2">
                <span className="truncate max-w-[280px]">{qrData?.url}</span>
                <Tooltip title="Sao chép liên kết">
                  <Button
                    type="text"
                    size="small"
                    icon={<CopyOutlined />}
                    onClick={() => {
                      if (qrData?.url) {
                        navigator.clipboard.writeText(qrData.url);
                        message.success('Đã sao chép đường dẫn gọi món!');
                      }
                    }}
                    className="hover:text-emerald-600 text-gray-400 animate-pulse"
                  />
                </Tooltip>
              </div>
            </div>

            {/* Actions */}
            <div className="w-full grid grid-cols-2 gap-3 pt-2">
              <Button
                type="primary"
                icon={<DownloadOutlined />}
                onClick={handleDownload}
                className="bg-emerald-600 hover:bg-emerald-500 border-none rounded-lg"
              >
                Tải xuống
              </Button>
              
              <Button
                icon={<ReloadOutlined />}
                loading={regenerateMutation.isPending}
                onClick={handleRegenerate}
                className="border-emerald-200 text-emerald-700 hover:text-emerald-600 hover:border-emerald-400 rounded-lg"
              >
                Tạo lại mã
              </Button>
            </div>
          </>
        )}
      </div>
    </Modal>
  );
};

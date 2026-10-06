import React, { useState, useRef } from 'react';
import { Card, Switch, Button, Typography, Input, Space, Alert, Modal, Spin, notification } from 'antd';
import { EditOutlined, CheckOutlined, CloseOutlined, DeleteOutlined, UploadOutlined } from '@ant-design/icons';
import { Banknote, CreditCard, Landmark, Smartphone } from 'lucide-react';
import { PaymentMethodSetting } from '../types/payment-methods';
import {
  useTogglePaymentMethod,
  useUpdatePaymentMethod,
  useUploadPaymentQR,
  useDeletePaymentQR,
} from '../hooks/usePaymentMethods';

const { Text, Title } = Typography;

interface PaymentMethodItemProps {
  setting: PaymentMethodSetting;
}

const methodIcons: Record<string, React.ReactNode> = {
  cash: <Banknote size={24} className="text-emerald-600" />,
  card: <CreditCard size={24} className="text-blue-600" />,
  transfer: <Landmark size={24} className="text-indigo-600" />,
  ewallet: <Smartphone size={24} className="text-purple-600" />,
};

export const PaymentMethodItem: React.FC<PaymentMethodItemProps> = ({ setting }) => {
  const [isEditing, setIsEditing] = useState(false);
  const [editName, setEditName] = useState(setting.display_name || setting.method_label);

  const fileInputRef = useRef<HTMLInputElement>(null);

  const toggleMutation = useTogglePaymentMethod();
  const updateMutation = useUpdatePaymentMethod();
  const uploadMutation = useUploadPaymentQR();
  const deleteMutation = useDeletePaymentQR();

  const handleToggle = () => {
    toggleMutation.mutate(setting.payment_method);
  };

  const handleSaveName = () => {
    if (editName.length > 100) {
      notification.error({ message: 'Tên quá dài (tối đa 100 ký tự)' });
      return;
    }
    updateMutation.mutate(
      { method: setting.payment_method, displayName: editName || null },
      {
        onSuccess: () => setIsEditing(false),
      }
    );
  };

  const handleCancelEdit = () => {
    setEditName(setting.display_name || setting.method_label);
    setIsEditing(false);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate size (2MB max) and type
    if (file.size > 2 * 1024 * 1024) {
      notification.error({ message: 'Lỗi', description: 'Dung lượng ảnh phải <= 2MB' });
      return;
    }
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      notification.error({ message: 'Lỗi', description: 'Định dạng ảnh không hợp lệ (chỉ hỗ trợ jpeg, png, webp)' });
      return;
    }

    uploadMutation.mutate(file);
    // Reset file input
    if (fileInputRef.current) fileInputRef.current.value = '';
  };

  const handleDeleteQR = () => {
    Modal.confirm({
      title: 'Bạn có chắc muốn xoá ảnh QR này?',
      content: 'Hành động này không thể hoàn tác.',
      okText: 'Xoá',
      okType: 'danger',
      cancelText: 'Huỷ',
      onOk: () => {
        deleteMutation.mutate();
      },
    });
  };

  const displayName = setting.display_name || setting.method_label;

  return (
    <Card className="h-full shadow-sm hover:shadow-md transition-shadow">
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-gray-50 rounded-lg border border-gray-100">
            {methodIcons[setting.payment_method]}
          </div>
          <div>
            {isEditing ? (
              <Space>
                <Input
                  value={editName}
                  onChange={(e) => setEditName(e.target.value)}
                  maxLength={100}
                  onPressEnter={handleSaveName}
                  status={editName.length > 100 ? 'error' : ''}
                />
                <Button 
                  icon={<CheckOutlined />} 
                  type="text" 
                  className="text-green-600" 
                  onClick={handleSaveName}
                  loading={updateMutation.isPending}
                />
                <Button 
                  icon={<CloseOutlined />} 
                  type="text" 
                  className="text-gray-400" 
                  onClick={handleCancelEdit} 
                  disabled={updateMutation.isPending}
                />
              </Space>
            ) : (
              <div className="flex items-center gap-2">
                <Title level={5} className="!mb-0">{displayName}</Title>
                <Button 
                  type="text" 
                  icon={<EditOutlined className="text-gray-400 hover:text-gray-600" />} 
                  size="small"
                  onClick={() => setIsEditing(true)}
                />
              </div>
            )}
            <Text type="secondary" className="text-sm">
              {setting.payment_method.toUpperCase()}
            </Text>
          </div>
        </div>
        <Switch 
          checked={setting.is_active} 
          onChange={handleToggle}
          loading={toggleMutation.isPending}
          className={setting.is_active ? 'bg-emerald-500' : 'bg-gray-300'}
        />
      </div>

      {setting.payment_method === 'transfer' && (
        <div className="mt-4 pt-4 border-t border-gray-100">
          <Title level={5} className="!mb-3 !text-sm">Mã QR Chuyển Khoản</Title>
          
          {setting.is_active && !setting.qr_code_url && (
            <Alert
              message="Bạn chưa tải lên mã QR ngân hàng. Thu ngân sẽ không thể cho khách quét mã."
              type="warning"
              showIcon
              className="mb-3 text-xs"
            />
          )}

          <div className="flex flex-col items-start gap-3">
            {setting.qr_code_url ? (
              <div className="relative group">
                <img 
                  src={setting.qr_code_url} 
                  alt="QR Code" 
                  className="w-32 h-32 object-cover rounded-lg border border-gray-200 shadow-sm"
                />
                <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all rounded-lg flex items-center justify-center">
                  <Button 
                    danger 
                    type="primary" 
                    icon={<DeleteOutlined />} 
                    className="opacity-0 group-hover:opacity-100 transition-opacity"
                    onClick={handleDeleteQR}
                    loading={deleteMutation.isPending}
                  >
                    Xoá
                  </Button>
                </div>
              </div>
            ) : (
              <div className="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center bg-gray-50">
                {uploadMutation.isPending ? (
                  <Spin />
                ) : (
                  <>
                    <Landmark className="text-gray-400 mb-2" size={24} />
                    <Text type="secondary" className="text-xs">Chưa có QR</Text>
                  </>
                )}
              </div>
            )}

            <input 
              type="file" 
              accept="image/jpeg, image/png, image/webp"
              className="hidden" 
              ref={fileInputRef}
              onChange={handleFileChange}
            />
            
            {!setting.qr_code_url && (
              <Button 
                icon={<UploadOutlined />} 
                onClick={() => fileInputRef.current?.click()}
                loading={uploadMutation.isPending}
                type="dashed"
              >
                Tải lên QR
              </Button>
            )}
          </div>
        </div>
      )}
    </Card>
  );
};

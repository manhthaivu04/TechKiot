import React from 'react';
import { Typography, Row, Col, Spin, Alert } from 'antd';
import { usePaymentMethods } from '../hooks/usePaymentMethods';
import { PaymentMethodItem } from '../components/PaymentMethodItem';

const { Title, Text } = Typography;

export const PaymentMethodSettingsPage: React.FC = () => {
  const { data: methods, isLoading, isError, error } = usePaymentMethods();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Spin size="large" tip="Đang tải cấu hình thanh toán..." />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="p-6">
        <Alert
          message="Lỗi tải dữ liệu"
          description={error?.message || 'Không thể lấy cấu hình phương thức thanh toán.'}
          type="error"
          showIcon
        />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <Title level={3} className="!mb-1 text-emerald-900">
            Cấu hình thanh toán
          </Title>
          <Text type="secondary">Quản lý các phương thức thanh toán áp dụng tại nhà hàng</Text>
        </div>
      </div>

      <div className="bg-white p-6 rounded-xl shadow-sm border border-emerald-50/50">
        <Row gutter={[24, 24]}>
          {methods?.map((setting) => (
            <Col xs={24} sm={12} lg={6} key={setting.id}>
              <PaymentMethodItem setting={setting} />
            </Col>
          ))}
        </Row>
      </div>
    </div>
  );
};

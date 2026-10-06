import { useState, useMemo, useEffect } from 'react';
import { Modal, Button, Divider, message, Spin, InputNumber, Input, List, Tag, Space, notification } from 'antd';
import { WalletOutlined, CheckCircleFilled, CheckOutlined } from '@ant-design/icons';
import { Banknote, CreditCard, Landmark, Smartphone, History } from 'lucide-react';
import { useCreatePayment, useOrderPayments } from '../services/order.hooks';
import { usePaymentMethods } from '@/tenant/features/settings/hooks/usePaymentMethods';
import { usePosCartStore } from '@/store/posCartStore';
import { PaymentMethodSetting } from '@/tenant/features/settings/types/payment-methods';
import { AxiosError } from 'axios';
import dayjs from 'dayjs';


const formatCurrency = (val: number) =>
    new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);

interface PaymentModalProps {
    open: boolean;
    orderId: string;
    orderCode: string;
    finalAmount: number;
    onSuccess: () => void;
    onCancel: () => void;
}

const methodIcons: Record<string, React.ReactNode> = {
    cash: <Banknote size={20} className="text-emerald-600" />,
    card: <CreditCard size={20} className="text-blue-600" />,
    transfer: <Landmark size={20} className="text-indigo-600" />,
    ewallet: <Smartphone size={20} className="text-purple-600" />,
};

export const PaymentModal = ({
    open,
    orderId,
    orderCode,
    finalAmount,
    onSuccess,
    onCancel,
}: PaymentModalProps) => {
    const [selectedMethod, setSelectedMethod] = useState<PaymentMethodSetting | null>(null);
    const [inputAmount, setInputAmount] = useState<number | null>(null);
    const [referenceNo, setReferenceNo] = useState<string>('');
    const [paid, setPaid] = useState(false);
    
    const clearCart = usePosCartStore((s) => s.clearCart);
    const { mutate: createPayment, isPending } = useCreatePayment();
    
    const { data: settings, isLoading: isLoadingSettings } = usePaymentMethods();
    const { data: payments, isLoading: isLoadingPayments } = useOrderPayments(orderId, open);

    const activeMethods = useMemo(() => {
        return settings?.filter(s => s.is_active) || [];
    }, [settings]);

    const paidAmount = useMemo(() => {
        return payments?.reduce((acc, p) => acc + Number(p.amount), 0) || 0;
    }, [payments]);

    const remainingAmount = useMemo(() => {
        return Math.max(0, finalAmount - paidAmount);
    }, [finalAmount, paidAmount]);

    const isFullyPaid = remainingAmount === 0;

    useEffect(() => {
        if (open && activeMethods.length > 0 && !selectedMethod) {
            setSelectedMethod(activeMethods[0]);
        }
    }, [open, activeMethods]);

    useEffect(() => {
        if (open) {
            setInputAmount(remainingAmount);
            setReferenceNo('');
            setPaid(false);
        }
    }, [open, remainingAmount]);

    const handleConfirm = () => {
        if (!selectedMethod || isPending) return;
        
        const isRefRequired = ['card', 'transfer', 'ewallet'].includes(selectedMethod.payment_method);
        if (isRefRequired && !referenceNo.trim()) {
            message.error('Vui lòng nhập mã giao dịch (Reference No).');
            return;
        }

        const amt = inputAmount || remainingAmount;
        if (amt <= 0) {
            message.error('Số tiền thanh toán không hợp lệ.');
            return;
        }
        if (amt > remainingAmount) {
            message.error('Số tiền thanh toán vượt quá số tiền cần phải thu.');
            return;
        }

        createPayment(
            {
                orderId,
                data: {
                    payment_method: selectedMethod.payment_method,
                    amount: amt,
                    reference_no: isRefRequired ? referenceNo.trim() : undefined,
                },
            },
            {
                onSuccess: () => {
                    message.success('Ghi nhận thanh toán thành công!');
                    setReferenceNo('');
                    
                    // If fully paid after this transaction, show success and close
                    if (amt >= remainingAmount) {
                        setPaid(true);
                        setTimeout(() => {
                            setPaid(false);
                            clearCart();
                            onSuccess();
                        }, 1800);
                    }
                },
                onError: (error) => {
                    const axiosError = error as AxiosError<any>;
                    const msg = axiosError.response?.data?.message || 'Ghi nhận thanh toán thất bại. Vui lòng thử lại.';
                    notification.error({
                        message: 'Lỗi',
                        description: msg,
                    });
                },
            }
        );
    };

    const handleClose = () => {
        if (!isPending) onCancel();
    };

    return (
        <Modal
            open={open}
            onCancel={handleClose}
            footer={null}
            title={
                <div className="flex items-center gap-2">
                    <WalletOutlined className="text-emerald-600" />
                    <span className="font-bold text-gray-800">Thanh toán đơn hàng</span>
                </div>
            }
            width={520}
            centered
            maskClosable={!isPending}
        >
            {paid ? (
                /* ✅ Success State */
                <div className="flex flex-col items-center justify-center py-8 gap-4">
                    <CheckCircleFilled className="text-6xl text-emerald-500" />
                    <p className="text-xl font-bold text-gray-800">Đơn hàng đã thanh toán đủ!</p>
                    <p className="text-gray-500 text-sm">Đơn hàng #{orderCode} đã được ghi nhận.</p>
                </div>
            ) : (
                <>
                    {/* Order summary */}
                    <div className="bg-emerald-50 rounded-xl p-4 mb-5 border border-emerald-100">
                        <div className="flex justify-between items-center mb-1">
                            <span className="text-gray-600 text-sm">Mã đơn:</span>
                            <span className="font-bold text-gray-800">#{orderCode}</span>
                        </div>
                        <div className="flex justify-between items-center">
                            <span className="text-gray-600 text-sm">Tổng cộng:</span>
                            <span className="font-bold text-gray-800">{formatCurrency(finalAmount)}</span>
                        </div>
                        {paidAmount > 0 && (
                            <div className="flex justify-between items-center mt-1">
                                <span className="text-gray-600 text-sm">Đã thu:</span>
                                <span className="font-bold text-blue-600">{formatCurrency(paidAmount)}</span>
                            </div>
                        )}
                        <Divider className="my-2" />
                        <div className="flex justify-between items-center">
                            <span className="text-gray-700 font-medium text-lg">Cần thu thêm:</span>
                            <span className="text-2xl font-extrabold text-emerald-600">
                                {formatCurrency(remainingAmount)}
                            </span>
                        </div>
                    </div>

                    {(isLoadingSettings || isLoadingPayments) ? (
                        <div className="flex justify-center p-6"><Spin /></div>
                    ) : (
                        <>
                            {!isFullyPaid && (
                                <div className="mb-6 border border-gray-200 p-4 rounded-xl">
                                    <p className="text-sm font-semibold text-gray-700 mb-3">
                                        Phương thức thanh toán:
                                    </p>
                                    <div className="grid grid-cols-2 gap-3 mb-4">
                                        {activeMethods.map((method) => (
                                            <div
                                                key={method.id}
                                                className={`flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all ${
                                                    selectedMethod?.id === method.id
                                                        ? 'border-emerald-500 bg-emerald-50'
                                                        : 'border-gray-200 hover:border-emerald-300'
                                                }`}
                                                onClick={() => setSelectedMethod(method)}
                                            >
                                                {methodIcons[method.payment_method]}
                                                <span className="font-semibold text-sm text-gray-700 truncate">
                                                    {method.display_name || method.method_label}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                    
                                    {activeMethods.length === 0 && (
                                        <p className="text-red-500 text-sm">Chưa có phương thức thanh toán nào được bật.</p>
                                    )}

                                    {selectedMethod && (
                                        <div className="flex flex-col gap-3">
                                            <div>
                                                <div className="text-sm text-gray-600 mb-1">Số tiền thanh toán:</div>
                                                <Space.Compact style={{ width: '100%' }}>
                                                    <InputNumber 
                                                        className="w-full"
                                                        size="large"
                                                        value={inputAmount}
                                                        onChange={(val) => setInputAmount(val !== null ? Number(val) : null)}
                                                        min={0}
                                                        max={remainingAmount}
                                                        formatter={(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                                                        parser={(value) => value!.replace(/\$\s?|(,*)/g, '') as unknown as number}
                                                    />
                                                    <Button size="large" type="default" disabled className="bg-gray-50 text-gray-500 cursor-default">VND</Button>
                                                </Space.Compact>
                                            </div>
                                            
                                            {['card', 'transfer', 'ewallet'].includes(selectedMethod.payment_method) && (
                                                <div>
                                                    <div className="text-sm text-gray-600 mb-1">Mã giao dịch / Reference No <span className="text-red-500">*</span>:</div>
                                                    <Input 
                                                        size="large"
                                                        placeholder="Nhập mã giao dịch..."
                                                        value={referenceNo}
                                                        onChange={(e) => setReferenceNo(e.target.value)}
                                                    />
                                                </div>
                                            )}

                                            {selectedMethod.payment_method === 'transfer' && selectedMethod.qr_code_url && (
                                                <div className="mt-2 flex flex-col items-center">
                                                    <p className="text-sm font-medium mb-2">Quét mã QR để chuyển khoản:</p>
                                                    <img src={selectedMethod.qr_code_url} alt="QR Code" className="w-40 h-40 object-cover border rounded-lg shadow-sm" />
                                                </div>
                                            )}

                                            <Button
                                                type="primary"
                                                size="large"
                                                className="!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600 font-bold mt-2"
                                                onClick={handleConfirm}
                                                loading={isPending}
                                                disabled={activeMethods.length === 0 || remainingAmount === 0}
                                            >
                                                Ghi nhận thanh toán
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Lịch sử thanh toán */}
                            {payments && payments.length > 0 && (
                                <div className="mt-4">
                                    <div className="flex items-center gap-2 mb-3">
                                        <History size={18} className="text-gray-500" />
                                        <span className="font-semibold text-gray-700">Lịch sử thanh toán</span>
                                    </div>
                                    <List
                                        size="small"
                                        dataSource={payments}
                                        renderItem={(p) => (
                                            <List.Item className="!py-3 bg-gray-50 mb-2 rounded-lg border border-gray-100">
                                                <div className="flex w-full justify-between items-start">
                                                    <div>
                                                        <Space>
                                                            <span className="font-semibold text-gray-800">{p.method_label}</span>
                                                            <Tag color="success" icon={<CheckOutlined />}>Thành công</Tag>
                                                        </Space>
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            {dayjs(p.paid_at).format('DD/MM/YYYY HH:mm')}
                                                        </div>
                                                        {p.reference_no && (
                                                            <div className="text-xs text-gray-500 mt-1">
                                                                Ref: <span className="font-medium text-gray-700">{p.reference_no}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <span className="font-bold text-emerald-600">
                                                        {formatCurrency(p.amount)}
                                                    </span>
                                                </div>
                                            </List.Item>
                                        )}
                                    />
                                </div>
                            )}

                            {isFullyPaid && (
                                <Button block size="large" onClick={handleClose} type="default">
                                    Đóng
                                </Button>
                            )}
                        </>
                    )}
                </>
            )}
        </Modal>
    );
};

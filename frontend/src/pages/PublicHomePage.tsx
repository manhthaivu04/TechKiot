import { useState } from 'react';
import { Card, Space, Button, Typography, Tag, Modal, Table, Spin, Radio } from 'antd';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { CheckCircleOutlined, ThunderboltOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import apiClient from '@/api/http';
import { formatCurrency } from '@/lib/formatters';
import type { Package, PackagePrice } from '@/types';

const { Title, Paragraph, Text } = Typography;

export const PublicHomePage = () => {
    // 30, 90, 365
    const [selectedDuration, setSelectedDuration] = useState<number>(30);
    const [selectedPackage, setSelectedPackage] = useState<Package | null>(null);
    const [isModalOpen, setIsModalOpen] = useState<boolean>(false);

    const { data: packages = [], isLoading } = useQuery({
        queryKey: ['publicPackages'],
        queryFn: () => apiClient.get<{ data: Package[] }>('/public/packages').then((res) => res.data.data),
    });

    const handleOpenModal = (pkg: Package) => {
        setSelectedPackage(pkg);
        setIsModalOpen(true);
    };

    const getPriceForDuration = (pkg: Package, duration: number): { price: number | string; originalPrice?: number | string | null } => {
        if (pkg.prices && pkg.prices.length > 0) {
            const found = pkg.prices.find((p) => p.duration_days === duration);
            if (found) {
                return { price: found.price, originalPrice: found.original_price };
            }
        }
        // Fallback to default base price scaled by duration if not explicitly set
        const basePrice = Number(pkg.price);
        if (duration === 90) return { price: basePrice * 3 };
        if (duration === 365) return { price: basePrice * 12 };
        return { price: basePrice };
    };

    return (
        <div className="min-h-screen bg-gradient-to-b from-blue-50 via-white to-gray-50 py-12 px-4 sm:px-6 lg:px-8">
            {/* HERO SECTION */}
            <div className="max-w-5xl mx-auto text-center mb-16">
                <div className="inline-flex items-center gap-2 bg-blue-100 text-blue-700 px-4 py-1.5 rounded-full text-sm font-semibold mb-6 shadow-sm">
                    <ThunderboltOutlined /> Nền tảng quản lý bán hàng Multi-Tenant hàng đầu
                </div>
                <Title level={1} className="text-5xl font-extrabold tracking-tight text-gray-900 mb-6">
                    Giải Pháp Quản Lý Bán Hàng <span className="text-blue-600">KiotTay</span>
                </Title>
                <Paragraph className="text-xl text-gray-600 max-w-3xl mx-auto mb-8 leading-relaxed">
                    Tối ưu hóa quy trình bán hàng, quản lý bàn, đơn hàng, nhân viên và kho hàng chuyên nghiệp. 
                    Lựa chọn gói dịch vụ phù hợp với quy mô kinh doanh của bạn.
                </Paragraph>
                <Space size="middle">
                    <Button type="primary" size="large" className="h-12 px-8 text-lg font-medium shadow-lg hover:shadow-xl transition-all">
                        <Link to="/login">Đăng nhập Admin</Link>
                    </Button>
                    <Button size="large" className="h-12 px-8 text-lg font-medium">
                        <Link to="/super-admin">Khu vực Super Admin</Link>
                    </Button>
                </Space>
            </div>

            {/* PRICING SECTION */}
            <div className="max-w-6xl mx-auto">
                <div className="text-center mb-12">
                    <Title level={2} className="text-3xl font-bold text-gray-900 mb-6">
                        Bảng Giá Dịch Vụ Mới Nhất
                    </Title>
                    {/* TOGGLE CHU KỲ THANH TOÁN */}
                    <Radio.Group
                        value={selectedDuration}
                        onChange={(e) => setSelectedDuration(e.target.value)}
                        size="large"
                        buttonStyle="solid"
                        className="shadow-md rounded-lg overflow-hidden p-1 bg-gray-200/60 backdrop-blur"
                    >
                        <Radio.Button value={30} className="px-6 text-base font-medium">1 Tháng</Radio.Button>
                        <Radio.Button value={90} className="px-6 text-base font-medium">3 Tháng</Radio.Button>
                        <Radio.Button value={365} className="px-6 text-base font-medium">
                            12 Tháng <Tag color="success" className="ml-2 border-0 bg-green-500 text-white font-bold">Tiết kiệm 20%</Tag>
                        </Radio.Button>
                    </Radio.Group>
                </div>

                {isLoading ? (
                    <div className="flex justify-center items-center py-20">
                        <Spin size="large" />
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 items-stretch">
                        {packages.map((pkg) => {
                            const { price, originalPrice } = getPriceForDuration(pkg, selectedDuration);
                            const isPopular = pkg.code.includes('PRO') || Number(pkg.price) > 100000;

                            return (
                                <Card
                                    key={pkg.id}
                                    hoverable
                                    onClick={() => handleOpenModal(pkg)}
                                    className={`relative flex flex-col justify-between h-full rounded-2xl shadow-sm hover:shadow-2xl transition-all duration-300 border-2 ${
                                        isPopular ? 'border-blue-500 bg-blue-50/10' : 'border-gray-200 bg-white'
                                    }`}
                                    bodyStyle={{ flexGrow: 1, display: 'flex', flexDirection: 'column', padding: '2.5rem' }}
                                >
                                    {isPopular && (
                                        <span className="absolute top-0 right-10 -translate-y-1/2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold text-xs px-4 py-1.5 rounded-full uppercase tracking-wider shadow-md">
                                            Phổ Biến Nhất
                                        </span>
                                    )}

                                    <div>
                                        <div className="flex items-center justify-between mb-4">
                                            <Title level={3} className="!m-0 text-2xl font-bold text-gray-900">{pkg.name}</Title>
                                            <SafetyCertificateOutlined className="text-2xl text-blue-500" />
                                        </div>
                                        <Paragraph className="text-gray-500 text-sm h-12 line-clamp-2 mb-6">
                                            {pkg.description || 'Gói giải pháp tối ưu cho hàng quán kinh doanh chuyên nghiệp.'}
                                        </Paragraph>

                                        {/* GIÁ TIỀN */}
                                        <div className="my-6 pt-6 border-t border-gray-100">
                                            <div className="flex items-baseline gap-2">
                                                <Text className="text-4xl font-black text-gray-900 tracking-tight">
                                                    {formatCurrency(price)}
                                                </Text>
                                                <Text className="text-gray-500 font-medium">/ {selectedDuration} ngày</Text>
                                            </div>
                                            {originalPrice && (
                                                <Text delete className="text-gray-400 text-sm mt-1 block">
                                                    Giá gốc: {formatCurrency(originalPrice)}
                                                </Text>
                                            )}
                                        </div>

                                        {/* TÍNH NĂNG */}
                                        <div className="space-y-3.5 my-8">
                                            {pkg.features?.slice(0, 5).map((f) => (
                                                <div key={f.id} className="flex items-start gap-3 text-gray-600 text-sm font-medium">
                                                    <CheckCircleOutlined className="text-green-500 mt-1 flex-shrink-0 text-base" />
                                                    <span>{f.name}</span>
                                                </div>
                                            ))}
                                            {pkg.features?.length > 5 && (
                                                <div className="text-blue-600 text-sm font-semibold pl-7">
                                                    + {pkg.features.length - 5} tính năng nâng cao khác...
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mt-auto pt-6">
                                        <Button
                                            type={isPopular ? 'primary' : 'default'}
                                            size="large"
                                            block
                                            className={`h-12 rounded-xl text-base font-semibold ${
                                                isPopular ? 'shadow-lg hover:shadow-xl' : 'hover:bg-gray-50 border-gray-300'
                                            }`}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                handleOpenModal(pkg);
                                            }}
                                        >
                                            Xem Chi Tiết & Đăng Ký
                                        </Button>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* MODAL CHI TIẾT GÓI */}
            <Modal
                title={
                    <div className="flex items-center gap-3 pb-4 border-b border-gray-100">
                        <div className="p-2 bg-blue-50 text-blue-600 rounded-xl">
                            <SafetyCertificateOutlined className="text-3xl" />
                        </div>
                        <div>
                            <Title level={3} className="!m-0 text-2xl font-bold">{selectedPackage?.name}</Title>
                            <Text type="secondary" className="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded">
                                CODE: {selectedPackage?.code}
                            </Text>
                        </div>
                    </div>
                }
                open={isModalOpen}
                onCancel={() => setIsModalOpen(false)}
                footer={null}
                width={720}
                centered
                className="rounded-2xl overflow-hidden"
            >
                {selectedPackage && (
                    <div className="py-4 space-y-8">
                        {/* MÔ TẢ */}
                        <div>
                            <Title level={5} className="text-gray-900 font-bold mb-2">Tổng Quan Gói Dịch Vụ</Title>
                            <Paragraph className="text-gray-600 leading-relaxed text-base">
                                {selectedPackage.description || 'Gói giải pháp toàn diện giúp chuẩn hóa quy trình bán hàng, nâng cao doanh thu và quản lý thất thoát hiệu quả cho cơ sở kinh doanh của bạn.'}
                            </Paragraph>
                        </div>

                        {/* BẢNG TỔNG HỢP CÁC MỐC GIÁ */}
                        <div>
                            <Title level={5} className="text-gray-900 font-bold mb-3">Bảng So Sánh Các Mốc Giá & Ưu Đãi</Title>
                            <Table
                                dataSource={selectedPackage.prices?.map((p: PackagePrice) => ({ ...p, key: p.id })) || []}
                                columns={[
                                    {
                                        title: 'Chu Kỳ / Thời Hạn',
                                        dataIndex: 'duration_days',
                                        key: 'duration_days',
                                        render: (days: number) => <span className="font-semibold text-gray-800">{days} ngày ({Math.round(days / 30)} tháng)</span>,
                                    },
                                    {
                                        title: 'Giá Ưu Đãi',
                                        dataIndex: 'price',
                                        key: 'price',
                                        render: (price: number) => <span className="text-blue-600 font-bold text-base">{formatCurrency(price)}</span>,
                                    },
                                    {
                                        title: 'Giá Gốc',
                                        dataIndex: 'original_price',
                                        key: 'original_price',
                                        render: (price?: number | null) => price ? <Text delete className="text-gray-400">{formatCurrency(price)}</Text> : '—',
                                    },
                                ]}
                                pagination={false}
                                size="small"
                                className="border border-gray-200 rounded-xl overflow-hidden shadow-sm"
                                locale={{ emptyText: `Đang áp dụng mức giá gốc chuẩn: ${formatCurrency(selectedPackage.price)} / ${selectedPackage.duration_days} ngày` }}
                            />
                        </div>

                        {/* DANH SÁCH TÍNH NĂNG ĐẦY ĐỦ */}
                        <div>
                            <Title level={5} className="text-gray-900 font-bold mb-4">Danh Sách Tính Năng Bao Gồm</Title>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 p-6 rounded-2xl border border-gray-100">
                                {selectedPackage.features?.map((f) => (
                                    <div key={f.id} className="flex items-center gap-3 text-gray-700 font-medium text-sm">
                                        <CheckCircleOutlined className="text-green-500 text-lg flex-shrink-0" />
                                        <span>{f.name}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* CALL TO ACTION */}
                        <div className="pt-6 border-t border-gray-100 flex items-center justify-end gap-4">
                            <Button size="large" className="h-12 px-6 rounded-xl font-medium" onClick={() => setIsModalOpen(false)}>
                                Đóng
                            </Button>
                            <Button type="primary" size="large" className="h-12 px-8 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all">
                                <Link to="/login">Đăng Ký Dùng Thử Ngay</Link>
                            </Button>
                        </div>
                    </div>
                )}
            </Modal>
        </div>
    );
};

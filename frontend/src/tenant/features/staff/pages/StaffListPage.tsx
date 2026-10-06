import { useState } from 'react';
import { Table, Button, Input, Tag, Space, Popconfirm, message, Select } from 'antd';
import { PlusOutlined, EditOutlined, StopOutlined, CheckCircleOutlined } from '@ant-design/icons';
import { useStaffList, useDeactivateStaff } from '../services/staff.hooks';
import { StaffFormModal } from '../components/StaffFormModal';
import { User, UserRole } from '@/types';
import dayjs from 'dayjs';
import { useDebounce } from '@/lib/use-debounce';

const { Search } = Input;

export const StaffListPage = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const debouncedSearch = useDebounce(searchTerm, 500);
    const [roleFilter, setRoleFilter] = useState<string | undefined>(undefined);
    const [isActiveFilter, setIsActiveFilter] = useState<boolean | undefined>(undefined);
    const [currentPage, setCurrentPage] = useState(1);
    
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [editingStaff, setEditingStaff] = useState<User | null>(null);

    const { data, isLoading } = useStaffList({
        q: debouncedSearch,
        role: roleFilter,
        is_active: isActiveFilter,
        page: currentPage,
        per_page: 10,
    });

    const deactivateStaffMutation = useDeactivateStaff();

    const handleAdd = () => {
        setEditingStaff(null);
        setIsModalVisible(true);
    };

    const handleEdit = (record: User) => {
        setEditingStaff(record);
        setIsModalVisible(true);
    };

    const handleDeactivate = (id: string) => {
        deactivateStaffMutation.mutate(id, {
            onSuccess: () => {
                message.success('Đã vô hiệu hóa tài khoản nhân viên!');
            },
            onError: (error: any) => {
                message.error(error?.response?.data?.message || 'Có lỗi xảy ra!');
            }
        });
    };

    const columns = [
        {
            title: 'Tên nhân viên',
            dataIndex: 'name',
            key: 'name',
            render: (text: string, record: User) => (
                <div>
                    <div className="font-medium text-gray-900">{text}</div>
                    <div className="text-xs text-gray-500">{record.email}</div>
                </div>
            )
        },
        {
            title: 'Chức vụ',
            dataIndex: 'role_label',
            key: 'role_label',
            render: (_: any, record: User) => {
                let color = 'default';
                if (record.role === UserRole.MANAGER) color = 'purple';
                if (record.role === UserRole.WAITER) color = 'blue';
                if (record.role === UserRole.KITCHEN) color = 'orange';
                if (record.role === UserRole.CASHIER) color = 'green';
                return <Tag color={color}>{record.role_label}</Tag>;
            }
        },
        {
            title: 'Trạng thái',
            dataIndex: 'is_active',
            key: 'is_active',
            render: (isActive: boolean) => (
                <Tag icon={isActive ? <CheckCircleOutlined /> : <StopOutlined />} color={isActive ? 'success' : 'error'}>
                    {isActive ? 'Hoạt động' : 'Đã khóa'}
                </Tag>
            )
        },
        {
            title: 'Đăng nhập cuối',
            dataIndex: 'last_login_at',
            key: 'last_login_at',
            render: (date: string) => date ? dayjs(date).format('DD/MM/YYYY HH:mm') : 'Chưa từng'
        },
        {
            title: 'Hành động',
            key: 'action',
            render: (_: any, record: User) => (
                <Space size="middle">
                    <Button 
                        type="text" 
                        icon={<EditOutlined />} 
                        onClick={() => handleEdit(record)}
                        className="text-blue-600 hover:text-blue-800"
                    />
                    {record.is_active && (
                        <Popconfirm
                            title="Khóa tài khoản này?"
                            description="Tài khoản bị khóa sẽ không thể đăng nhập."
                            onConfirm={() => handleDeactivate(record.id)}
                            okText="Khóa"
                            cancelText="Hủy"
                            okButtonProps={{ danger: true }}
                        >
                            <Button 
                                type="text" 
                                danger 
                                icon={<StopOutlined />} 
                            />
                        </Popconfirm>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div className="p-6 h-full flex flex-col bg-gray-50">
            <div className="mb-6 flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Quản lý nhân sự</h1>
                    <p className="text-gray-500 mt-1">Thêm và phân quyền tài khoản cho nhân viên nhà hàng</p>
                </div>
                <Button type="primary" icon={<PlusOutlined />} onClick={handleAdd} size="large">
                    Thêm nhân viên
                </Button>
            </div>

            <div className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col">
                <div className="flex flex-wrap gap-4 mb-4">
                    <Search
                        placeholder="Tìm tên, email..."
                        allowClear
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-64"
                    />
                    <Select
                        placeholder="Chọn chức vụ"
                        allowClear
                        className="w-40"
                        onChange={setRoleFilter}
                        options={[
                            { value: UserRole.MANAGER, label: 'Quản lý' },
                            { value: UserRole.WAITER, label: 'Phục vụ' },
                            { value: UserRole.KITCHEN, label: 'Bếp' },
                            { value: UserRole.CASHIER, label: 'Thu ngân' },
                        ]}
                    />
                    <Select
                        placeholder="Trạng thái"
                        allowClear
                        className="w-36"
                        onChange={setIsActiveFilter}
                        options={[
                            { value: true, label: 'Hoạt động' },
                            { value: false, label: 'Đã khóa' },
                        ]}
                    />
                </div>

                <Table
                    columns={columns}
                    dataSource={data?.data}
                    rowKey="id"
                    loading={isLoading}
                    pagination={{
                        current: currentPage,
                        total: data?.meta?.total || 0,
                        pageSize: data?.meta?.per_page || 10,
                        onChange: (page) => setCurrentPage(page),
                        showSizeChanger: false,
                    }}
                    className="flex-1 overflow-auto"
                />
            </div>

            <StaffFormModal
                visible={isModalVisible}
                onClose={() => setIsModalVisible(false)}
                initialData={editingStaff}
            />
        </div>
    );
};

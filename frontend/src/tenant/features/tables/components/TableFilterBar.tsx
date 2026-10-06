import { Input, Select, Button, Space, Segmented } from 'antd';
import { PlusOutlined, AppstoreOutlined, UnorderedListOutlined, SearchOutlined } from '@ant-design/icons';
import type { TableArea, TableStatus } from '@/types';
import { TABLE_STATUS_OPTIONS } from '@/types';

export type ViewMode = 'grid' | 'table';

interface TableFilterBarProps {
    areas: TableArea[];
    selectedAreaId: string | undefined;
    selectedStatus: TableStatus | undefined;
    searchValue: string;
    viewMode: ViewMode;
    onAreaChange: (areaId: string | undefined) => void;
    onStatusChange: (status: TableStatus | undefined) => void;
    onSearchChange: (value: string) => void;
    onViewModeChange: (mode: ViewMode) => void;
    onAddTable: () => void;
}

export const TableFilterBar = ({
    areas,
    selectedAreaId,
    selectedStatus,
    searchValue,
    viewMode,
    onAreaChange,
    onStatusChange,
    onSearchChange,
    onViewModeChange,
    onAddTable,
}: TableFilterBarProps) => {
    const areaOptions = [
        { label: 'Tất cả khu vực', value: '__all__' },
        { label: 'Chưa phân khu vực', value: '__none__' },
        ...areas.map((area) => ({ label: area.name, value: area.id })),
    ];

    const statusOptions = [
        { label: 'Tất cả trạng thái', value: '__all__' },
        ...TABLE_STATUS_OPTIONS.map((s) => ({ label: s.label, value: s.value })),
    ];

    return (
        <div style={{
            display: 'flex',
            flexWrap: 'wrap',
            gap: 12,
            alignItems: 'center',
            marginBottom: 20,
            padding: '16px 20px',
            background: '#fff',
            borderRadius: 12,
            boxShadow: '0 1px 3px rgba(0, 0, 0, 0.06)',
        }}>
            <Select
                value={selectedAreaId || '__all__'}
                onChange={(val) => onAreaChange(val === '__all__' ? undefined : val === '__none__' ? '__none__' : val)}
                options={areaOptions}
                style={{ minWidth: 180 }}
                placeholder="Lọc theo khu vực"
            />

            <Select
                value={selectedStatus || '__all__'}
                onChange={(val) => onStatusChange(val === '__all__' ? undefined : val as TableStatus)}
                options={statusOptions}
                style={{ minWidth: 170 }}
                placeholder="Lọc theo trạng thái"
            />

            <Input
                prefix={<SearchOutlined style={{ color: '#9ca3af' }} />}
                placeholder="Tìm theo tên hoặc mã bàn..."
                value={searchValue}
                onChange={(e) => onSearchChange(e.target.value)}
                allowClear
                style={{ maxWidth: 260, flex: 1 }}
            />

            <div style={{ flex: 1 }} />

            <Space>
                <Segmented
                    value={viewMode}
                    onChange={(val) => onViewModeChange(val as ViewMode)}
                    options={[
                        { label: <AppstoreOutlined />, value: 'grid' },
                        { label: <UnorderedListOutlined />, value: 'table' },
                    ]}
                />

                <Button
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={onAddTable}
                    style={{
                        background: '#10b981',
                        borderColor: '#10b981',
                        borderRadius: 8,
                        fontWeight: 500,
                    }}
                >
                    Thêm bàn
                </Button>
            </Space>
        </div>
    );
};

import { Button, List, Space, Typography, Popconfirm } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import type { ItemGroup } from '@/types';

const { Text } = Typography;

interface ItemGroupSidebarProps {
    itemGroups: ItemGroup[];
    selectedGroupId: string | null;
    onSelectGroup: (id: string | null) => void;
    onAddGroup: () => void;
    onEditGroup: (group: ItemGroup) => void;
    onDeleteGroup: (id: string) => void;
    isLoading: boolean;
}

export const ItemGroupSidebar = ({
    itemGroups,
    selectedGroupId,
    onSelectGroup,
    onAddGroup,
    onEditGroup,
    onDeleteGroup,
    isLoading,
}: ItemGroupSidebarProps) => {
    return (
        <div className="h-full flex flex-col bg-white md:border-r border-gray-200">
            <div className="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <Text strong className="text-lg">Nhóm Món</Text>
                <Button type="primary" icon={<PlusOutlined />} onClick={onAddGroup} size="small">
                    Thêm
                </Button>
            </div>
            
            <div className="flex-1 overflow-y-auto p-2">
                <div 
                    className={`p-3 mb-1 rounded cursor-pointer transition-colors ${selectedGroupId === null ? 'bg-blue-50 text-blue-600 font-medium' : 'hover:bg-gray-100'}`}
                    onClick={() => onSelectGroup(null)}
                >
                    Tất cả món ăn
                </div>
                
                <List
                    loading={isLoading}
                    dataSource={itemGroups}
                    renderItem={(group) => (
                        <List.Item
                            className={`px-3 py-2 mb-1 rounded cursor-pointer border-none transition-colors ${selectedGroupId === group.id ? 'bg-blue-50 text-blue-600' : 'hover:bg-gray-100'}`}
                            onClick={() => onSelectGroup(group.id)}
                            actions={[
                                <Space key="actions" onClick={(e) => e.stopPropagation()}>
                                    <Button 
                                        type="text" 
                                        icon={<EditOutlined className="text-gray-500 hover:text-blue-500" />} 
                                        size="small"
                                        onClick={() => onEditGroup(group)}
                                    />
                                    <Popconfirm
                                        title="Xóa nhóm món"
                                        description="Bạn có chắc chắn muốn xóa nhóm này? Các món thuộc nhóm này có thể bị ảnh hưởng."
                                        onConfirm={() => onDeleteGroup(group.id)}
                                        okText="Xóa"
                                        cancelText="Hủy"
                                        okButtonProps={{ danger: true }}
                                    >
                                        <Button 
                                            type="text" 
                                            danger
                                            icon={<DeleteOutlined />} 
                                            size="small"
                                        />
                                    </Popconfirm>
                                </Space>
                            ]}
                        >
                            <div className="flex items-center gap-2 truncate">
                                <div className={`w-2 h-2 rounded-full ${group.is_active ? 'bg-green-500' : 'bg-gray-300'}`}></div>
                                <Text className={selectedGroupId === group.id ? 'font-medium text-blue-600' : ''} ellipsis>
                                    {group.name}
                                </Text>
                            </div>
                        </List.Item>
                    )}
                />
            </div>
        </div>
    );
};

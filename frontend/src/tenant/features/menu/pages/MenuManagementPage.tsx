import { useState, useMemo } from 'react';
import { message, Drawer } from 'antd';
import { ItemGroupSidebar } from '../components/ItemGroupSidebar';
import { ItemTable } from '../components/ItemTable';
import { ItemGroupModal } from '../components/ItemGroupModal';
import { ItemModal } from '../components/ItemModal';
import { RecipeTab } from '../components/RecipeTab';
import {
    useItemGroups,
    useCreateItemGroup,
    useUpdateItemGroup,
    useDeleteItemGroup,
    useItems,
    useCreateItem,
    useUpdateItem,
    useDeleteItem,
} from '../services/menu.hooks';
import type { ItemGroup, Item, CreateItemGroupRequest, CreateItemRequest } from '@/types';

export const MenuManagementPage = () => {
    // --- State ---
    const [selectedGroupId, setSelectedGroupId] = useState<string | null>(null);
    const [typeFilter, setTypeFilter] = useState<string>('ALL');

    const [isGroupModalVisible, setIsGroupModalVisible] = useState(false);
    const [editingGroup, setEditingGroup] = useState<ItemGroup | null>(null);

    const [isItemModalVisible, setIsItemModalVisible] = useState(false);
    const [editingItem, setEditingItem] = useState<Item | null>(null);
    const [addingItemType, setAddingItemType] = useState<string>('MENU_ITEM');
    const [recipeItemId, setRecipeItemId] = useState<string | null>(null);
    const [recipeItemName, setRecipeItemName] = useState<string>('');

    // --- Queries ---
    const { data: groupData, isLoading: isLoadingGroups } = useItemGroups();
    const { data: itemData, isLoading: isLoadingItems } = useItems();

    // --- Derived Data ---
    const itemGroups = useMemo(() => groupData?.data || [], [groupData?.data]);
    const allItems = useMemo(() => itemData?.data || [], [itemData?.data]);

    // --- Mutations ---
    const createGroupMutation = useCreateItemGroup();
    const updateGroupMutation = useUpdateItemGroup(editingGroup?.id || '');
    const deleteGroupMutation = useDeleteItemGroup();

    const createItemMutation = useCreateItem();
    const updateItemMutation = useUpdateItem(editingItem?.id || '');
    const deleteItemMutation = useDeleteItem();

    // --- Derived Data ---
    const filteredItems = useMemo(() => {
        let result = allItems;
        if (selectedGroupId) {
            result = result.filter(item => item.item_group_id === selectedGroupId);
        }
        if (typeFilter !== 'ALL') {
            result = result.filter(item => (item.item_type || 'MENU_ITEM') === typeFilter);
        }
        return result;
    }, [allItems, selectedGroupId, typeFilter]);

    const selectedGroupName = useMemo(() => {
        if (!selectedGroupId) return undefined;
        return itemGroups.find(g => g.id === selectedGroupId)?.name;
    }, [itemGroups, selectedGroupId]);

    // --- Handlers: Item Group & Type Filter ---
    const handleSelectGroup = (groupId: string | null) => {
        setSelectedGroupId(groupId);
        if (groupId !== null) {
            setTypeFilter('ALL');
        }
    };

    const handleSetTypeFilter = (type: string) => {
        setTypeFilter(type);
        if (type !== 'ALL') {
            setSelectedGroupId(null);
        }
    };

    // --- Handlers: Item Group ---
    const handleAddGroup = () => {
        setEditingGroup(null);
        setIsGroupModalVisible(true);
    };

    const handleEditGroup = (group: ItemGroup) => {
        setEditingGroup(group);
        setIsGroupModalVisible(true);
    };

    const handleDeleteGroup = (id: string) => {
        deleteGroupMutation.mutate(id, {
            onSuccess: () => {
                message.success('Đã xóa nhóm món ăn!');
                if (selectedGroupId === id) setSelectedGroupId(null);
            },
            onError: (error: unknown) => {
                message.error((error as { response?: { data?: { message?: string } } }).response?.data?.message || 'Có lỗi xảy ra khi xóa!');
            }
        });
    };

    const handleSubmitGroup = (values: Record<string, unknown>) => {
        if (editingGroup) {
            updateGroupMutation.mutate(values, {
                onSuccess: () => {
                    message.success('Cập nhật nhóm món thành công!');
                    setIsGroupModalVisible(false);
                },
                onError: (error: unknown) => {
                    message.error((error as { response?: { data?: { message?: string } } }).response?.data?.message || 'Có lỗi xảy ra!');
                }
            });
        } else {
            createGroupMutation.mutate(values as unknown as CreateItemGroupRequest, {
                onSuccess: () => {
                    message.success('Thêm nhóm món thành công!');
                    setIsGroupModalVisible(false);
                },
                onError: (error: unknown) => {
                    message.error((error as { response?: { data?: { message?: string } } }).response?.data?.message || 'Có lỗi xảy ra!');
                }
            });
        }
    };

    // --- Handlers: Item ---
    const handleAddItem = (type: string = 'MENU_ITEM') => {
        setAddingItemType(type);
        setEditingItem(null);
        setIsItemModalVisible(true);
    };

    const handleEditItem = (item: Item) => {
        setEditingItem(item);
        setIsItemModalVisible(true);
    };

    const handleViewRecipe = (item: Item) => {
        setRecipeItemId(item.id);
        setRecipeItemName(item.name);
    };

    const handleDeleteItem = (id: string) => {
        deleteItemMutation.mutate(id, {
            onSuccess: () => {
                message.success('Đã xóa món ăn!');
            },
            onError: (error: unknown) => {
                message.error((error as { response?: { data?: { message?: string } } }).response?.data?.message || 'Có lỗi xảy ra khi xóa!');
            }
        });
    };

    const handleSubmitItem = (values: Record<string, unknown>) => {
        if (editingItem) {
            updateItemMutation.mutate(values, {
                onSuccess: () => {
                    message.success('Cập nhật món ăn thành công!');
                    setIsItemModalVisible(false);
                },
                onError: (error: unknown) => {
                    message.error((error as { response?: { data?: { message?: string } } }).response?.data?.message || 'Có lỗi xảy ra!');
                }
            });
        } else {
            createItemMutation.mutate(values as unknown as CreateItemRequest, {
                onSuccess: () => {
                    message.success('Thêm món ăn thành công!');
                    setIsItemModalVisible(false);
                },
                onError: (error: unknown) => {
                    message.error((error as { response?: { data?: { message?: string } } }).response?.data?.message || 'Có lỗi xảy ra!');
                }
            });
        }
    };

    return (
        <div className="flex flex-col md:flex-row h-full bg-gray-100 overflow-hidden">
            {/* Sidebar */}
            <div className="w-full md:w-64 flex-shrink-0 h-48 md:h-full border-b md:border-b-0 border-gray-200">
                <ItemGroupSidebar
                    itemGroups={itemGroups}
                    selectedGroupId={selectedGroupId}
                    onSelectGroup={handleSelectGroup}
                    onAddGroup={handleAddGroup}
                    onEditGroup={handleEditGroup}
                    onDeleteGroup={handleDeleteGroup}
                    isLoading={isLoadingGroups}
                />
            </div>

            {/* Main Content */}
            <div className="flex-1 overflow-hidden">
                <ItemTable
                    items={filteredItems}
                    itemGroups={itemGroups}
                    isLoading={isLoadingItems}
                    onAddItem={handleAddItem}
                    onEditItem={handleEditItem}
                    onDeleteItem={handleDeleteItem}
                    onViewRecipe={handleViewRecipe}
                    selectedGroupName={selectedGroupName}
                    typeFilter={typeFilter}
                    onSetTypeFilter={handleSetTypeFilter}
                />
            </div>

            {/* Modals */}
            <ItemGroupModal
                visible={isGroupModalVisible}
                onClose={() => setIsGroupModalVisible(false)}
                onSubmit={handleSubmitGroup}
                initialData={editingGroup}
                isLoading={createGroupMutation.isPending || updateGroupMutation.isPending}
            />

            <ItemModal
                visible={isItemModalVisible}
                onClose={() => setIsItemModalVisible(false)}
                onSubmit={handleSubmitItem}
                initialData={editingItem}
                isLoading={createItemMutation.isPending || updateItemMutation.isPending}
                itemGroups={itemGroups}
                defaultGroupId={selectedGroupId || undefined}
                defaultItemType={addingItemType}
            />

            {/* Recipe Drawer */}
            <Drawer
                title={`Công thức: ${recipeItemName}`}
                open={!!recipeItemId}
                onClose={() => setRecipeItemId(null)}
                width={700}
                destroyOnClose
            >
                {recipeItemId && <RecipeTab itemId={recipeItemId} />}
            </Drawer>
        </div>
    );
};

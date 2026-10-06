// --- Transaction Types ---

export type TransactionType = 'receipt' | 'issue' | 'adjustment' | 'waste' | 'return' | 'recipe_use';

export const TRANSACTION_TYPE_OPTIONS: {
    value: TransactionType;
    label: string;
    color: string;
}[] = [
    { value: 'receipt', label: 'Nhập kho', color: 'green' },
    { value: 'issue', label: 'Xuất kho', color: 'red' },
    { value: 'adjustment', label: 'Điều chỉnh', color: 'blue' },
    { value: 'waste', label: 'Hủy hàng', color: 'orange' },
    { value: 'return', label: 'Trả hàng', color: 'purple' },
    { value: 'recipe_use', label: 'Bán hàng (BOM)', color: 'cyan' },
];

// --- Entities ---

export interface InventoryItem {
    id: string;
    warehouse_id: string;
    warehouse_name: string;
    item_id: string;
    item_name: string;
    item_unit: string;
    quantity: string;
    created_at: string;
    updated_at: string;
}

export interface InventoryTransaction {
    id: string;
    warehouse_name: string;
    item_name: string;
    item_unit: string;
    transaction_type: TransactionType;
    transaction_label: string;
    reference_type: string | null;
    reference_id: string | null;
    quantity_change: string;
    before_quantity: string;
    after_quantity: string;
    note: string | null;
    creator_name: string | null;
    created_at: string;
}

// --- Request Params ---

export interface InventoryListParams {
    warehouse_id?: string;
    search?: string;
}

export interface TransactionListParams {
    warehouse_id?: string;
    item_id?: string;
    page?: number;
    per_page?: number;
}

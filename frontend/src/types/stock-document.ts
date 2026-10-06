// --- Document Type ---

export type DocumentType = 'receipt' | 'issue' | 'adjustment' | 'waste' | 'return';

export const DOCUMENT_TYPE_OPTIONS: {
    value: DocumentType;
    label: string;
    color: string;
}[] = [
    { value: 'receipt', label: 'Phiếu nhập kho', color: 'green' },
    { value: 'issue', label: 'Phiếu xuất kho', color: 'red' },
    { value: 'adjustment', label: 'Phiếu điều chỉnh', color: 'blue' },
    { value: 'waste', label: 'Phiếu hủy hàng', color: 'orange' },
    { value: 'return', label: 'Phiếu trả hàng', color: 'purple' },
];

// --- Document Status ---

export type DocumentStatus = 'draft' | 'confirmed' | 'cancelled';

export const DOCUMENT_STATUS_OPTIONS: {
    value: DocumentStatus;
    label: string;
    antColor: 'default' | 'success' | 'error';
}[] = [
    { value: 'draft', label: 'Nháp', antColor: 'default' },
    { value: 'confirmed', label: 'Đã xác nhận', antColor: 'success' },
    { value: 'cancelled', label: 'Đã hủy', antColor: 'error' },
];

// --- Stock Document Item ---

export interface StockDocumentItem {
    id: string;
    item_id: string;
    item_name: string | null;
    item_unit: string | null;
    quantity: string;
    unit_cost: string;
    total_cost: string;
}

// --- Stock Document ---

export interface StockDocument {
    id: string;
    restaurant_id: string;
    warehouse_id: string;
    warehouse_name: string | null;
    document_type: DocumentType;
    document_label: string;
    code: string;
    status: DocumentStatus;
    status_label: string;
    note: string | null;
    created_by: string | null;
    creator_name: string | null;
    items: StockDocumentItem[];
    created_at: string;
    updated_at: string;
}

// --- Create Request ---

export interface CreateStockDocumentItemRequest {
    item_id: string;
    quantity: number;
    unit_cost: number;
}

export interface CreateStockDocumentRequest {
    warehouse_id: string;
    document_type: DocumentType;
    note?: string;
    items: CreateStockDocumentItemRequest[];
}

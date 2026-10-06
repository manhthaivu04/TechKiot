import { create } from 'zustand';
import type { Item, ServiceType } from '@/types';

export interface PosCartItem {
    item_id: string;
    name: string;
    sale_price: number;
    image_url: string | null;
    quantity: number;
    note?: string;
}

interface PosCartState {
    serviceType: ServiceType;
    selectedTableId: string | null;
    items: PosCartItem[];
    // Computed
    totalItems: () => number;
    subtotal: () => number;
    // Actions
    addItem: (item: Item) => void;
    removeItem: (item_id: string) => void;
    updateQuantity: (item_id: string, quantity: number) => void;
    setServiceType: (type: ServiceType) => void;
    setSelectedTableId: (id: string | null) => void;
    clearCart: () => void;
}

export const usePosCartStore = create<PosCartState>()((set, get) => ({
    serviceType: 'TAKEAWAY',
    selectedTableId: null,
    items: [],

    totalItems: () => get().items.reduce((sum, i) => sum + i.quantity, 0),

    subtotal: () =>
        get().items.reduce((sum, i) => sum + i.sale_price * i.quantity, 0),

    addItem: (item: Item) => {
        const existing = get().items.find((i) => i.item_id === item.id);
        if (existing) {
            set((state) => ({
                items: state.items.map((i) =>
                    i.item_id === item.id
                        ? { ...i, quantity: i.quantity + 1 }
                        : i
                ),
            }));
        } else {
            set((state) => ({
                items: [
                    ...state.items,
                    {
                        item_id: item.id,
                        name: item.name,
                        sale_price: parseFloat(item.sale_price),
                        image_url: item.image_url,
                        quantity: 1,
                    },
                ],
            }));
        }
    },

    removeItem: (item_id) =>
        set((state) => ({
            items: state.items.filter((i) => i.item_id !== item_id),
        })),

    updateQuantity: (item_id, quantity) => {
        if (quantity <= 0) {
            get().removeItem(item_id);
            return;
        }
        set((state) => ({
            items: state.items.map((i) =>
                i.item_id === item_id ? { ...i, quantity } : i
            ),
        }));
    },

    setServiceType: (serviceType) => set({ serviceType }),
    setSelectedTableId: (selectedTableId) => set({ selectedTableId }),
    clearCart: () => set({ items: [], selectedTableId: null }),
}));

import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { CartItem } from '@/types/public-menu';

interface CartState {
    items: CartItem[];
    restaurantId: string | null;
    // Computed helpers
    totalItems: () => number;
    totalPrice: () => number;
    // Actions
    addItem: (item: Omit<CartItem, 'quantity'> & { quantity?: number }) => void;
    removeItem: (item_id: string) => void;
    updateQuantity: (item_id: string, quantity: number) => void;
    clearCart: () => void;
    setRestaurantId: (id: string) => void;
}

export const useCartStore = create<CartState>()(
    persist(
        (set, get) => ({
            items: [],
            restaurantId: null,

            totalItems: () => get().items.reduce((sum, i) => sum + i.quantity, 0),

            totalPrice: () =>
                get().items.reduce((sum, i) => sum + i.sale_price * i.quantity, 0),

            addItem: (newItem) => {
                const existing = get().items.find((i) => i.item_id === newItem.item_id && i.note === newItem.note);
                if (existing) {
                    set((state) => ({
                        items: state.items.map((i) =>
                            i.item_id === newItem.item_id && i.note === newItem.note
                                ? { ...i, quantity: i.quantity + (newItem.quantity ?? 1) }
                                : i
                        ),
                    }));
                } else {
                    set((state) => ({
                        items: [...state.items, { ...newItem, quantity: newItem.quantity ?? 1 }],
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

            clearCart: () => set({ items: [] }),

            setRestaurantId: (id) => set({ restaurantId: id }),
        }),
        {
            name: 'kiottay-cart',
        }
    )
);

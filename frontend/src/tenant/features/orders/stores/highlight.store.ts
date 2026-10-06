import { create } from 'zustand';

export type HighlightType = 'NEW_ORDER' | 'NEW_ITEM';

interface HighlightStore {
    highlightedOrders: Record<string, HighlightType>;
    addHighlight: (orderId: string, type: HighlightType) => void;
    removeHighlight: (orderId: string) => void;
    clearAll: () => void;
}

export const useHighlightStore = create<HighlightStore>((set) => ({
    highlightedOrders: {},
    addHighlight: (orderId, type) =>
        set((state) => ({
            highlightedOrders: {
                ...state.highlightedOrders,
                [orderId]: type,
            },
        })),
    removeHighlight: (orderId) =>
        set((state) => {
            const newMap = { ...state.highlightedOrders };
            delete newMap[orderId];
            return { highlightedOrders: newMap };
        }),
    clearAll: () => set({ highlightedOrders: {} }),
}));

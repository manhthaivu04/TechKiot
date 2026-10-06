import { Outlet } from 'react-router-dom';

/**
 * MenuLayout — minimal wrapper for the public QR Menu zone.
 * All visual chrome (header, cart bar) is handled directly in child pages
 * to allow per-page scroll/sticky control.
 */
export const MenuLayout = () => {
    return (
        <div className="max-w-lg mx-auto min-h-screen bg-gray-50 overflow-x-hidden relative">
            <Outlet />
        </div>
    );
};

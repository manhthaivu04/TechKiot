/**
 * echoClient.ts
 * Khởi tạo và export một instance Laravel Echo duy nhất (singleton).
 * Kết nối với Laravel Reverb (self-hosted WebSocket server).
 *
 * Biến môi trường trong .env:
 *   VITE_REVERB_APP_KEY=yjlxiowbcpdasqsdjriq
 *   VITE_REVERB_HOST=127.0.0.1
 *   VITE_REVERB_PORT=8081
 *   VITE_REVERB_SCHEME=http
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Gắn Pusher vào window để Laravel Echo tìm thấy (BẮT BUỘC)
(window as Window & { Pusher?: typeof Pusher }).Pusher = Pusher;

// ── Đọc biến môi trường Reverb ──────────────────────────────────────────────
const REVERB_KEY    = import.meta.env.VITE_REVERB_APP_KEY ?? '';
const REVERB_HOST   = import.meta.env.VITE_REVERB_HOST   ?? '127.0.0.1';
const REVERB_PORT   = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);
const REVERB_SCHEME = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

// ── Tạo Echo instance ────────────────────────────────────────────────────────
const createEchoInstance = (): Echo<'pusher'> => {
    // Bật Pusher logging trong development để debug WebSocket
    if (import.meta.env.DEV) {
        Pusher.logToConsole = true;
    }

    const isTLS = REVERB_SCHEME === 'https';
    const port = REVERB_PORT || (isTLS ? 443 : 8080);

    return new Echo({
        // FIX: Dùng 'pusher' làm broadcaster cho Reverb
        // 'reverb' chỉ có trong laravel-echo >= 1.15, nhưng gây lỗi với một số version.
        // 'pusher' + wsHost là cách ổn định nhất.
        broadcaster:       'pusher',
        key:               REVERB_KEY,
        cluster:           'mt1',          // Bắt buộc có, dù Reverb không dùng
        wsHost:            REVERB_HOST,
        wsPort:            port,
        wssPort:           port,
        forceTLS:          isTLS,
        enabledTransports: ['ws', 'wss'],
        disableStats:      true,

        // FIX: Cấu hình auth endpoint cho Private Channel
        // Token JWT từ localStorage (Sanctum)
        authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/broadcasting/auth`,
        auth: {
            headers: {
                Authorization: `Bearer ${localStorage.getItem('auth-store')
                    ? JSON.parse(localStorage.getItem('auth-store') ?? '{}')?.state?.token ?? ''
                    : ''}`,
                Accept: 'application/json',
            },
        },
    });
};

// ── Singleton: chỉ tạo 1 lần trong suốt vòng đời app ────────────────────────
let echoInstance: Echo<'pusher'> | null = null;

export const getEcho = (): Echo<'pusher'> => {
    if (!echoInstance) {
        echoInstance = createEchoInstance();
    }
    return echoInstance;
};

/** Ngắt kết nối và reset instance (gọi khi user logout) */
export const disconnectEcho = (): void => {
    echoInstance?.disconnect();
    echoInstance = null;
};

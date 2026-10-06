import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Gán Pusher vào window scope để laravel-echo có thể sử dụng
(window as any).Pusher = Pusher;

const isHttps = (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https';

export const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'your_reverb_key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : 443,
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

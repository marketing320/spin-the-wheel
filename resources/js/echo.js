import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Realtime transport (Laravel Reverb / Pusher protocol).
 *
 * Disabled by default: set VITE_REVERB_ENABLED=true and rebuild to turn it on.
 * When disabled, no WebSocket is opened at all — the app relies on HTTP polling
 * (see live-view.js and player-sync.js), which avoids console errors when the
 * websocket server isn't reachable through the proxy/tunnel.
 */
const ENABLED = String(import.meta.env.VITE_REVERB_ENABLED ?? '') === 'true';

if (ENABLED) {
    window.Pusher = Pusher;

    const LOOPBACK = ['', 'localhost', '127.0.0.1', '0.0.0.0'];
    const pageIsHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';

    let host = import.meta.env.VITE_REVERB_HOST;
    let scheme = import.meta.env.VITE_REVERB_SCHEME || (pageIsHttps ? 'https' : 'http');
    let port = Number(import.meta.env.VITE_REVERB_PORT) || (scheme === 'https' ? 443 : 80);

    // In production behind a same-host TLS proxy, prefer the current origin.
    if (pageIsHttps && LOOPBACK.includes(host)) {
        host = window.location.hostname;
        scheme = 'https';
        port = 443;
    }

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: host || (typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'),
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

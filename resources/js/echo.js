import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Resolve the Reverb connection. In production the app usually sits behind a
 * TLS-terminating proxy on the same host, so if the build baked in a loopback
 * host (the local default) but the page is served over HTTPS, we connect to the
 * current origin over wss:443 instead. This lets a single build work locally
 * (ws://127.0.0.1:8080) and in production (wss://your-domain) without a rebuild.
 */
const LOOPBACK = ['', 'localhost', '127.0.0.1', '0.0.0.0'];
const pageIsHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';

let host = import.meta.env.VITE_REVERB_HOST;
let scheme = import.meta.env.VITE_REVERB_SCHEME || (pageIsHttps ? 'https' : 'http');
let port = Number(import.meta.env.VITE_REVERB_PORT) || (scheme === 'https' ? 443 : 80);

if (pageIsHttps && LOOPBACK.includes(host)) {
    host = window.location.hostname;
    scheme = 'https';
    port = 443;
}

const forceTLS = scheme === 'https';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: host || (typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'),
    wsPort: port,
    wssPort: port,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
});

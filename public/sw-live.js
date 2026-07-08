// Minimal service worker for the installable live-view/roadshow-live display.
// Exists only to satisfy PWA installability checks on browsers that still
// weight service-worker presence — it deliberately does NOT cache anything.
// This display is realtime/server-driven (live spin sync, live queue state),
// so a caching layer would risk showing stale content instead of no benefit.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {
    // No-op: every request falls through to the browser's normal network
    // handling. The handler's mere presence is what browsers check for.
});

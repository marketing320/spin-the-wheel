/**
 * Subscribes to the public "spin-stage" broadcast channel and forwards spin
 * lifecycle events to the caller. Shared by the player page (to detect other
 * players spinning) and the live-view screen (to mirror the active spin).
 *
 * Returns an unsubscribe function. Safe to call even if Echo/Reverb is not
 * connected — the caller can additionally poll as a fallback.
 */
export function subscribeToSpins({ onStarted, onCompleted, onExpired, onQueueUpdated } = {}) {
    if (typeof window === 'undefined' || !window.Echo) {
        console.warn('Echo is not available; realtime sync disabled.');
        return () => {};
    }

    const channel = window.Echo.channel('spin-stage');

    if (onStarted) channel.listen('.spin.started', onStarted);
    if (onCompleted) channel.listen('.spin.completed', onCompleted);
    if (onExpired) channel.listen('.spin.expired', onExpired);
    if (onQueueUpdated) channel.listen('.spin.queue-updated', onQueueUpdated);

    return () => {
        try {
            window.Echo.leaveChannel('spin-stage');
        } catch (e) {
            /* no-op */
        }
    };
}

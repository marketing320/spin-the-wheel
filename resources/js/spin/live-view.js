import '../echo';
import { createWheel } from './wheel-scene';
import { SpinController } from './spin-controller';
import { fireConfetti } from './confetti-controller';
import { subscribeToSpins } from './live-sync';

/**
 * Public live-view / event-screen entry. Mirrors the active player spin in
 * realtime via broadcast events, with a polling fallback so the screen still
 * works when the Reverb websocket server is not running.
 */
function initLiveView() {
    const el = document.getElementById('live-config');
    if (!el) return;
    const config = JSON.parse(el.textContent);

    const stage = document.getElementById('wheel-stage');
    let wheel = createWheel(stage, config.segments || []);
    const controller = new SpinController(wheel);

    const idle = document.getElementById('idle-screen');
    const nameEl = document.getElementById('current-player');
    const reveal = document.getElementById('prize-reveal');
    const revealName = document.getElementById('reveal-prize-name');
    const revealRarity = document.getElementById('reveal-rarity');

    let currentSpinId = null;
    let lastPayload = null;
    let resetTimer = null;

    const show = (node) => { node?.classList.remove('hidden'); node?.classList.add('flex'); };
    const hide = (node) => { node?.classList.add('hidden'); node?.classList.remove('flex'); };

    function toIdle() {
        currentSpinId = null;
        lastPayload = null;
        hide(reveal);
        show(idle);
        if (nameEl) nameEl.textContent = '';
    }

    function handleStart(payload) {
        if (!payload || payload.spin_session_id === currentSpinId) return;
        clearTimeout(resetTimer);
        currentSpinId = payload.spin_session_id;
        lastPayload = payload;

        // Rebuild the wheel with the authoritative segments for this spin.
        if (Array.isArray(payload.wheel_segments) && payload.wheel_segments.length) {
            wheel = createWheel(stage, payload.wheel_segments);
            controller.wheel = wheel;
        }

        hide(idle);
        hide(reveal);
        if (nameEl) nameEl.textContent = payload.player_display || '';
        controller.run(payload);
    }

    function handleComplete(payload) {
        const data = payload || lastPayload;
        if (!data) return;
        if (revealName) revealName.textContent = data.prize_name || 'A prize!';
        if (revealRarity && data.prize_rarity) {
            revealRarity.textContent = data.prize_rarity;
        }
        show(reveal);
        fireConfetti(data.confetti_level || 'medium');

        clearTimeout(resetTimer);
        resetTimer = setTimeout(toIdle, (config.settings.auto_reset_seconds || 12) * 1000);
    }

    function handleExpired() {
        clearTimeout(resetTimer);
        toIdle();
    }

    subscribeToSpins({
        onStarted: handleStart,
        onCompleted: handleComplete,
        onExpired: handleExpired,
    });

    // Sync if a spin is already active when the screen loads.
    const fetchActive = () => fetch(config.routes.active, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .catch(() => null);

    fetchActive().then((d) => { if (d?.active && d.spin) handleStart(d.spin); });

    // Polling fallback (covers a down websocket server): detect spin start/end.
    setInterval(async () => {
        const d = await fetchActive();
        if (!d) return;
        if (d.active && d.spin && d.spin.spin_session_id !== currentSpinId) {
            handleStart(d.spin);
        } else if (!d.active && currentSpinId && !resetTimer) {
            handleComplete(lastPayload);
        }
    }, 3000);
}

function boot() {
    const el = document.getElementById('live-config');
    if (!el || el.dataset.inited) return;
    el.dataset.inited = '1';
    initLiveView();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
if (document.readyState !== 'loading') boot();

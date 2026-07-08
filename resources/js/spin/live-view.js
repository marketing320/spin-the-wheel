import '../echo';
import { createWheel } from './wheel-scene';
import { SpinController } from './spin-controller';
import { fireConfetti } from './confetti-controller';
import { subscribeToSpins } from './live-sync';

// ---------------------------------------------------------------------------
// Idle spin pace — degrees per second the wheel slowly rotates while nobody
// is playing. Tune this single number to speed up/slow down the idle motion;
// no other changes needed. Rebuild with `npm run build` after editing.
// ---------------------------------------------------------------------------
const IDLE_SPIN_SPEED_DEG_PER_SEC = 12;

function initLiveView() {
    const configElement = document.getElementById('live-config');
    if (!configElement) return;
    const config = JSON.parse(configElement.textContent);

    const stage = document.getElementById('wheel-stage');
    const pointer = document.getElementById('live-wheel-pointer');
    let wheel = createWheel(stage, config.segments || []);
    const controller = new SpinController(wheel, { pointer, soundUrl: config.soundUrl });
    const playerName = document.getElementById('current-player');
    const reveal = document.getElementById('prize-reveal');
    const revealName = document.getElementById('reveal-prize-name');
    const revealRarity = document.getElementById('reveal-rarity');
    const revealImage = document.getElementById('reveal-prize-image');
    const queueCount = document.getElementById('queue-count');
    const queueList = document.getElementById('queue-list');
    const soundOverlay = document.getElementById('enable-sound-overlay');
    const soundButton = document.getElementById('enable-sound-button');
    const ctaBanner = document.getElementById('cta-banner');

    // /front-view only — a full-screen admin-uploaded image slideshow that
    // takes over during idle instead of the spinning wheel. Both are null on
    // /live-view and /roadshow-live, so all of the logic below is a no-op
    // there (same optional-element pattern as ctaBanner above).
    const idleSlideshow = document.getElementById('idle-slideshow');
    const liveContent = document.getElementById('live-content');
    const slideEls = idleSlideshow ? Array.from(idleSlideshow.querySelectorAll('.idle-slide')) : [];

    // Live prize display above the pointer — shows whatever is under it.
    const prizeChip = document.getElementById('pointer-prize-chip');
    const prizeImage = document.getElementById('pointer-prize-image');
    const prizeIcon = document.getElementById('pointer-prize-icon');
    const prizeName = document.getElementById('pointer-prize-name');
    let currentSegments = config.segments || [];

    function showSegment(index) {
        const seg = currentSegments[index];
        if (!seg || !prizeName) return;
        prizeName.textContent = seg.label || '';
        if (prizeChip) prizeChip.style.background = seg.color || '#0e75bc';
        if (prizeImage && prizeIcon) {
            const hasImage = Boolean(seg.image);
            prizeImage.classList.toggle('hidden', !hasImage);
            prizeIcon.classList.toggle('hidden', hasImage);
            if (hasImage) prizeImage.src = seg.image;
        }
    }

    // Idle animation: a slow continuous rotation while nobody is spinning,
    // so the wheel is never just sitting still. Runs on its own rAF loop,
    // completely separate from SpinController's physics-driven one — the two
    // never run at the same time (handleStart() always stops this first).
    let idleAngle = 0;
    let idleRafId = null;
    let idleLastTimestamp = null;

    function idleSpinTick(timestamp) {
        if (idleLastTimestamp === null) idleLastTimestamp = timestamp;
        const deltaSeconds = (timestamp - idleLastTimestamp) / 1000;
        idleLastTimestamp = timestamp;

        idleAngle = (idleAngle + IDLE_SPIN_SPEED_DEG_PER_SEC * deltaSeconds) % 360;
        wheel?.setRotationDegrees(idleAngle);
        showSegment(SpinController.segmentAt(idleAngle, currentSegments.length || 1));

        idleRafId = requestAnimationFrame(idleSpinTick);
    }

    // Constant CTA banner: a single fixed message + color, rendered
    // server-side in the blade view (only present at all when enabled with
    // a message set). Tied to the same idle/active lifecycle as the idle
    // spin above — always shown/hidden together, so it's never visible
    // during an active spin or the prize-reveal overlay — and pops in with
    // a bounce every time it appears.
    function playPop(node, className) {
        if (!node) return;
        node.classList.remove(className);
        void node.offsetWidth; // force reflow so the animation restarts even if it's already applied
        node.classList.add(className);
    }

    function showCtaBanner() {
        if (!ctaBanner) return;
        show(ctaBanner);
        playPop(ctaBanner, 'animate-cta-pop-in');
    }

    function hideCtaBanner() {
        hide(ctaBanner);
    }

    // Idle image slideshow (/front-view only): cross-fades through the
    // admin-uploaded images on a timer. With zero images (banner disabled or
    // none uploaded yet), slideEls is empty and startIdleSpin() below falls
    // through to the normal idle-spin wheel instead — this never shows a
    // blank takeover screen.
    let slideIndex = 0;
    let slideTimer = null;

    function showSlide(index) {
        slideEls.forEach((el, i) => { el.style.opacity = i === index ? '1' : '0'; });
    }

    function showIdleSlideshow() {
        show(idleSlideshow);
        slideIndex = 0;
        showSlide(slideIndex);
        if (slideEls.length > 1 && slideTimer === null) {
            slideTimer = setInterval(() => {
                slideIndex = (slideIndex + 1) % slideEls.length;
                showSlide(slideIndex);
            }, frontViewIntervalMs);
        }
    }

    function hideIdleSlideshow() {
        hide(idleSlideshow);
        if (slideTimer !== null) {
            clearInterval(slideTimer);
            slideTimer = null;
        }
    }

    const frontViewIntervalMs = Math.max(2, Number(config.settings.front_view_interval_seconds) || 6) * 1000;

    function startIdleSpin() {
        showCtaBanner();

        if (idleSlideshow && slideEls.length > 0) {
            hide(liveContent);
            showIdleSlideshow();
            return;
        }

        if (idleRafId !== null) return;
        idleLastTimestamp = null;
        idleRafId = requestAnimationFrame(idleSpinTick);
    }

    function stopIdleSpin() {
        hideCtaBanner();

        if (idleSlideshow) {
            hideIdleSlideshow();
            show(liveContent);
        }

        if (idleRafId !== null) {
            cancelAnimationFrame(idleRafId);
            idleRafId = null;
        }
    }

    let currentSpinId = null;
    let revealedSpinId = null;
    let lastPayload = null;
    let resetTimer = null;

    const show = (node) => { node?.classList.remove('hidden'); node?.classList.add('flex'); };
    const hide = (node) => { node?.classList.add('hidden'); node?.classList.remove('flex'); };

    function renderQueue(queue = { count: 0, players: [] }) {
        if (queueCount) queueCount.textContent = String(queue.count || 0);
        if (!queueList) return;
        queueList.replaceChildren();

        if (!Array.isArray(queue.players) || queue.players.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'text-slate-400';
            empty.textContent = 'No players waiting';
            queueList.appendChild(empty);
            return;
        }

        queue.players.forEach((queuedPlayer) => {
            const row = document.createElement('li');
            row.className = 'flex items-center gap-3 rounded-lg bg-slate-100 px-3 py-2';
            const position = document.createElement('span');
            position.className = 'font-display text-xs text-brand-600';
            position.textContent = `#${queuedPlayer.position}`;
            const name = document.createElement('span');
            name.className = 'min-w-0 truncate font-semibold text-slate-700';
            name.textContent = queuedPlayer.name || 'Player';
            row.append(position, name);
            queueList.appendChild(row);
        });
    }

    function toIdle() {
        currentSpinId = null;
        revealedSpinId = null;
        lastPayload = null;
        hide(reveal);
        if (playerName) playerName.textContent = '';
        currentSegments = config.segments || [];
        showSegment(0);
        startIdleSpin();
    }

    function handleComplete(payload) {
        const data = payload || lastPayload;
        if (!data) return;
        if (revealedSpinId === data.spin_session_id) return;
        revealedSpinId = data.spin_session_id;
        if (revealName) revealName.textContent = data.prize_name || 'A prize!';
        if (revealRarity) revealRarity.textContent = data.prize_rarity || '';
        if (revealImage) {
            revealImage.classList.toggle('hidden', !data.prize_image);
            if (data.prize_image) revealImage.src = data.prize_image;
        }
        stopIdleSpin();
        show(reveal);
        fireConfetti(data.confetti_level || 'medium', {
            image: data.confetti_image,
            imageCount: data.confetti_image_count,
            imageSize: data.confetti_image_size,
        });

        clearTimeout(resetTimer);
        resetTimer = setTimeout(toIdle, (config.settings.auto_reset_seconds || 12) * 1000);
    }

    function handleStart(payload) {
        if (!payload || payload.spin_session_id === currentSpinId) return;
        stopIdleSpin();
        clearTimeout(resetTimer);
        resetTimer = null;
        currentSpinId = payload.spin_session_id;
        revealedSpinId = null;
        lastPayload = payload;

        if (Array.isArray(payload.wheel_segments) && payload.wheel_segments.length) {
            wheel?.dispose?.();
            wheel = createWheel(stage, payload.wheel_segments);
            controller.wheel = wheel;
            currentSegments = payload.wheel_segments;
        }

        hide(reveal);
        if (playerName) playerName.textContent = payload.player_display || '';

        if (payload.phase === 'buffer') {
            controller.run(payload, { onSegment: showSegment });
            handleComplete(payload);
            return;
        }

        controller.run(payload, { onSegment: showSegment, onComplete: () => handleComplete(payload) });
    }

    function handleExpired() {
        clearTimeout(resetTimer);
        controller.stop();
        toIdle();
    }

    soundButton?.addEventListener('click', async () => {
        soundButton.disabled = true;
        const unlocked = await controller.audio.unlock();
        soundButton.disabled = false;
        if (!unlocked) return;
        hide(soundOverlay);
        if (lastPayload) controller.run(lastPayload);
    });

    subscribeToSpins({
        onStarted: handleStart,
        onCompleted: handleComplete,
        onExpired: handleExpired,
        onQueueUpdated: renderQueue,
    });

    const fetchActive = () => fetch(config.routes.active, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .catch(() => null);

    async function sync() {
        const data = await fetchActive();
        if (!data) return;
        renderQueue(data.queue);
        if (data.active && data.spin) {
            if (data.spin.spin_session_id !== currentSpinId) handleStart(data.spin);
        } else if (currentSpinId && !resetTimer) {
            handleComplete(lastPayload);
        }
    }

    renderQueue(config.queue);
    showSegment(0); // resting prize under the pointer
    startIdleSpin();
    sync();
    setInterval(sync, 3000);
}

function boot() {
    const element = document.getElementById('live-config');
    if (!element || element.dataset.inited) return;
    element.dataset.inited = '1';
    initLiveView();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);
if (document.readyState !== 'loading') boot();

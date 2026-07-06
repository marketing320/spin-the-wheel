import { createWheel } from './wheel-scene';
import { SpinController } from './spin-controller';
import { fireConfetti } from './confetti-controller';
import { subscribeToSpins } from './live-sync';

function readConfig() {
    const el = document.getElementById('spin-config');
    return el ? JSON.parse(el.textContent) : null;
}

function getCoords(enabled) {
    if (!enabled || !('geolocation' in navigator)) {
        return Promise.resolve({ lat: null, lng: null });
    }

    return new Promise((resolve) => {
        navigator.geolocation.getCurrentPosition(
            (position) => resolve({ lat: position.coords.latitude, lng: position.coords.longitude }),
            () => resolve({ lat: null, lng: null }),
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 },
        );
    });
}

export function initPlayerPage() {
    const config = readConfig();
    if (!config) return;

    const stage = document.getElementById('wheel-stage');
    const pointer = document.getElementById('wheel-pointer');
    const wheel = createWheel(stage, config.segments || []);
    const controller = new SpinController(wheel, { pointer, soundUrl: config.soundUrl });
    const button = document.getElementById('spin-button');
    const idleLabel = button?.querySelector('[data-label-idle-text]');
    const hint = document.getElementById('spin-hint');
    const banner = document.getElementById('status-banner');
    const modal = document.getElementById('result-modal');
    const queueModal = document.getElementById('queue-modal');
    const queueModalPosition = document.getElementById('queue-modal-position');
    const turnModal = document.getElementById('turn-modal');
    const csrf = config.csrf || document.querySelector('meta[name=csrf-token]')?.content;

    // Live prize display above the pointer — shows whatever is under the pointer.
    const segmentsList = config.segments || [];
    const prizeChip = document.getElementById('pointer-prize-chip');
    const prizeImage = document.getElementById('pointer-prize-image');
    const prizeIcon = document.getElementById('pointer-prize-icon');
    const prizeName = document.getElementById('pointer-prize-name');

    function showSegment(index) {
        const seg = segmentsList[index];
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

    let mySpinId = null;
    let submitting = false;
    let spinning = false;
    let eligibility = {
        eligible: Boolean(config.eligibility?.eligible),
        spin_in_progress: Boolean(config.spinInProgress),
        queue: config.queue || { queued: false, position: null, ahead: 0 },
        can_start: false,
    };

    const setHint = (text) => { if (hint) hint.textContent = text || ''; };

    function queueMessage(queue) {
        const ahead = Number(queue?.ahead || 0);
        if (ahead === 1) return 'There is 1 person in front of you. Please wait…';
        if (ahead > 1) return `There are ${ahead} people in front of you. Please wait…`;
        return 'You are first in the queue. Please wait for your turn…';
    }

    const showModal = (node) => { node?.classList.remove('hidden'); node?.classList.add('flex'); };
    const hideModal = (node) => { node?.classList.add('hidden'); node?.classList.remove('flex'); };

    // Whether the player has already tapped "Let's go!" on the turn modal for
    // their current turn — prevents it popping back open on every poll tick
    // while they still haven't spun. Reset once they leave the queue so it
    // can show again next time they queue up.
    let turnAcknowledged = false;

    function updateQueueModals() {
        const queue = eligibility.queue || {};
        const queued = Boolean(queue.queued);
        const canStart = Boolean(eligibility.can_start);

        if (queued && canStart) {
            hideModal(queueModal);
            if (!turnAcknowledged) showModal(turnModal);
            return;
        }

        if (queued) {
            hideModal(turnModal);
            const ahead = Number(queue.ahead || 0);
            if (queueModalPosition) {
                queueModalPosition.textContent = ahead === 0
                    ? "You're next in line!"
                    : ahead === 1
                        ? '1 person ahead of you'
                        : `${ahead} people ahead of you`;
            }
            showModal(queueModal);
            return;
        }

        hideModal(queueModal);
        hideModal(turnModal);
        turnAcknowledged = false;
    }

    function renderState() {
        if (!button) return;

        const queue = eligibility.queue || {};
        const ownSpinActive = Boolean(mySpinId && spinning);
        const canJoin = eligibility.eligible && !queue.queued && !ownSpinActive && !submitting;
        const canSpin = eligibility.eligible && queue.queued && eligibility.can_start && !ownSpinActive && !submitting;
        const available = eligibility.eligible && !queue.queued && !eligibility.spin_in_progress && !ownSpinActive && !submitting;

        button.disabled = !(canJoin || canSpin || available);
        button.querySelector('[data-label-idle]')?.classList.toggle('hidden', submitting || ownSpinActive);
        button.querySelector('[data-label-spinning]')?.classList.toggle('hidden', !(submitting || ownSpinActive));

        if (idleLabel) {
            idleLabel.textContent = canSpin ? 'YOUR TURN — SPIN' : 'SPIN!';
        }

        if (!eligibility.eligible && eligibility.message) {
            if (banner) banner.innerHTML = `<div class="rounded-xl border-2 border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">${eligibility.message}</div>`;
            setHint('');
        } else {
            if (banner) banner.innerHTML = '';
            if (queue.queued) {
                setHint(canSpin ? 'It is your turn. Tap the button to spin!' : queueMessage(queue));
            } else if (eligibility.spin_in_progress && !spinning) {
                setHint('Another player is spinning…');
            } else if (!spinning && !submitting) {
                setHint('');
            }
        }

        updateQueueModals();
    }

    async function post(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body || {}),
        });

        return { status: response.status, data: await response.json().catch(() => ({})) };
    }

    async function refreshEligibility() {
        try {
            const response = await fetch(config.routes.eligibility, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) eligibility = await response.json();
            renderState();
        } catch (_) {
            // Preserve the last known state during a temporary network failure.
        }
    }

    let voucherCountdownInterval = null;

    function stopVoucherCountdown() {
        if (voucherCountdownInterval) {
            clearInterval(voucherCountdownInterval);
            voucherCountdownInterval = null;
        }
    }

    function showVoucher(result) {
        const section = modal?.querySelector('#result-voucher');
        if (!section) return;

        stopVoucherCountdown();

        if (!result.voucher_code) {
            section.classList.add('hidden');
            return;
        }

        section.classList.remove('hidden');
        section.querySelector('#result-voucher-code').textContent = result.voucher_code;
        const qr = section.querySelector('#result-voucher-qr');
        const barcode = section.querySelector('#result-voucher-barcode');
        if (qr && result.voucher_qr_url) qr.src = result.voucher_qr_url;
        if (barcode && result.voucher_barcode_url) barcode.src = result.voucher_barcode_url;

        const countdown = section.querySelector('#result-voucher-countdown');
        const expiresAtMs = Date.parse(result.voucher_expires_at);
        if (!countdown || !expiresAtMs) return;

        const tick = () => {
            const remaining = expiresAtMs - controller.serverNow();
            if (remaining <= 0) {
                countdown.textContent = 'Expired';
                stopVoucherCountdown();
                return;
            }
            const h = Math.floor(remaining / 3_600_000);
            const m = Math.floor((remaining % 3_600_000) / 60_000);
            const s = Math.floor((remaining % 60_000) / 1000);
            countdown.textContent = [h, m, s].map((n) => String(n).padStart(2, '0')).join(':');
        };

        tick();
        voucherCountdownInterval = setInterval(tick, 1000);
    }

    function showResult(result) {
        if (!modal) return;
        const image = modal.querySelector('#result-prize-image');
        if (image) {
            image.classList.toggle('hidden', !result.prize_image);
            if (result.prize_image) image.src = result.prize_image;
        }
        modal.querySelector('#result-prize-name').textContent = result.prize_name || 'A prize!';
        const rarity = modal.querySelector('#result-rarity');
        if (rarity && result.prize_rarity) {
            rarity.innerHTML = `<span class="pill bg-slate-100 font-display uppercase text-slate-700 ring-2 ring-slate-300">${result.prize_rarity}</span>`;
        }
        modal.querySelector('#result-message').textContent = result.redemption_message || '';
        showVoucher(result);
        const link = modal.querySelector('#result-link');
        if (link && mySpinId) link.href = config.routes.result.replace('SPIN_ID', mySpinId);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        fireConfetti(result.confetti_level || 'medium', {
            image: result.confetti_image,
            imageCount: result.confetti_image_count,
            imageSize: result.confetti_image_size,
        });
    }

    async function completeMySpin(result) {
        if (!mySpinId) return;
        showResult(result || {});
        await post(config.routes.complete.replace('SPIN_ID', mySpinId));
    }

    async function onSpinClick() {
        if (submitting || (mySpinId && spinning)) return;

        submitting = true;
        const queue = eligibility.queue || {};
        const shouldJoin = !queue.queued && (eligibility.spin_in_progress || Number(queue.count || 0) > 0);
        setHint(shouldJoin ? 'Please wait…' : 'Checking your location…');
        renderState();

        if (shouldJoin) {
            const { status, data } = await post(config.routes.queue);
            submitting = false;
            if (status === 202 && data.queued) {
                eligibility.queue = data.queue;
                eligibility.spin_in_progress = true;
                eligibility.can_start = false;
                renderState();
                return;
            }
            setHint(data.message || 'Unable to spin right now.');
            await refreshEligibility();
            return;
        }

        // This must run inside the final tap gesture for mobile audio.
        await controller.audio.unlock();

        const coords = await getCoords(config.geofenceEnabled);
        const { status, data } = await post(config.routes.start, coords);
        submitting = false;

        if (status === 200 && data.ok && data.spin) {
            mySpinId = data.spin.spin_session_id;
            spinning = true;
            eligibility.spin_in_progress = true;
            eligibility.queue = { queued: false, position: null, ahead: 0 };
            setHint('Good luck!');
            renderState();
            controller.run(data.spin, {
                onSegment: showSegment,
                onComplete: async () => {
                    spinning = false;
                    await completeMySpin(data.spin);
                    renderState();
                },
            });
            return;
        }

        if (status === 202 && data.queued) {
            eligibility.queue = data.queue;
            eligibility.spin_in_progress = true;
            eligibility.can_start = false;
            renderState();
            return;
        }

        setHint(data.message || 'Unable to spin right now.');
        await refreshEligibility();
    }

    button?.addEventListener('click', onSpinClick);

    document.getElementById('turn-modal-dismiss')?.addEventListener('click', () => {
        turnAcknowledged = true;
        hideModal(turnModal);
    });

    subscribeToSpins({
        onStarted: (payload) => {
            if (payload.spin_session_id === mySpinId) return;
            eligibility.spin_in_progress = true;
            spinning = true;
            setHint(`${payload.player_display || 'Another player'} is spinning…`);
            renderState();
            controller.run(payload, {
                onSegment: showSegment,
                onComplete: () => {
                    spinning = false;
                    refreshEligibility();
                },
            });
        },
        onCompleted: (payload) => {
            if (payload.spin_session_id === mySpinId) return;
            spinning = false;
            refreshEligibility();
        },
        onExpired: () => {
            spinning = false;
            refreshEligibility();
        },
        onQueueUpdated: refreshEligibility,
    });

    const fetchActive = () => fetch(config.routes.active, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .catch(() => null);

    let mirroredSpinId = null;
    async function poll() {
        if (mySpinId && spinning) return;
        const active = await fetchActive();
        if (active?.active && active.spin && active.spin.spin_session_id !== mySpinId) {
            eligibility.spin_in_progress = true;
            if (mirroredSpinId !== active.spin.spin_session_id) {
                mirroredSpinId = active.spin.spin_session_id;
                spinning = true;
                controller.run(active.spin, {
                    onSegment: showSegment,
                    onComplete: () => {
                        spinning = false;
                        refreshEligibility();
                    },
                });
            }
        } else {
            mirroredSpinId = null;
            await refreshEligibility();
        }
        renderState();
    }

    renderState();
    showSegment(0); // resting prize under the pointer
    poll();
    setInterval(poll, 3000);
}

import { createWheel } from './wheel-scene';
import { SpinController } from './spin-controller';
import { fireConfetti } from './confetti-controller';
import { subscribeToSpins } from './live-sync';

function readConfig() {
    const el = document.getElementById('spin-config');
    return el ? JSON.parse(el.textContent) : null;
}

function getCoords(enabled) {
    if (!enabled) {
        return Promise.resolve({ ok: true, coords: { lat: null, lng: null } });
    }

    if (!('geolocation' in navigator)) {
        return Promise.resolve({
            ok: false,
            title: 'Location not supported',
            message: 'This browser cannot share your location. Please try a current mobile browser.',
        });
    }

    return new Promise((resolve) => {
        navigator.geolocation.getCurrentPosition(
            (position) => resolve({
                ok: true,
                coords: { lat: position.coords.latitude, lng: position.coords.longitude },
            }),
            (error) => {
                const failures = {
                    1: {
                        title: 'Location permission needed',
                        message: 'Please allow location access in your browser settings, then try again.',
                    },
                    2: {
                        title: 'Location unavailable',
                        message: 'We could not determine your current location. Check your device location settings and try again.',
                    },
                    3: {
                        title: 'Location check timed out',
                        message: 'Getting your location took too long. Move somewhere with a clearer signal and try again.',
                    },
                };

                resolve({
                    ok: false,
                    ...(failures[error?.code] || {
                        title: 'Could not verify location',
                        message: 'We could not access your current location. Please try again.',
                    }),
                });
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 },
        );
    });
}

const EXPECTED_ELIGIBILITY_REASONS = new Set([
    'blocked',
    'not_verified',
    'form_incomplete',
    'campaign_closed',
    'once_per_campaign',
    'once_per_day',
    'max_per_campaign',
    'max_per_day',
    'cooldown',
]);

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
    const locationModal = document.getElementById('location-modal');
    const errorModal = document.getElementById('error-modal');
    const errorModalTitle = document.getElementById('error-modal-title');
    const errorModalMessage = document.getElementById('error-modal-message');
    const errorModalClose = document.getElementById('error-modal-close');
    const errorModalRetry = document.getElementById('error-modal-retry');
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
    const locationRequired = Boolean(config.geofenceEnabled);
    let locationVerified = !locationRequired;
    let locationVerifying = false;
    let verifiedCoords = { lat: null, lng: null };
    let errorRetryAction = null;
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

    const showModal = (node) => {
        node?.classList.remove('hidden');
        node?.classList.add('flex');
        node?.setAttribute('aria-hidden', 'false');
    };
    const hideModal = (node) => {
        node?.classList.add('hidden');
        node?.classList.remove('flex');
        node?.setAttribute('aria-hidden', 'true');
    };

    function closeErrorModal() {
        hideModal(errorModal);
        errorRetryAction = null;
    }

    function showErrorModal({ title, message, retry = null }) {
        hideModal(locationModal);
        if (errorModalTitle) errorModalTitle.textContent = title || 'Something went wrong';
        if (errorModalMessage) errorModalMessage.textContent = message || 'Please try again.';
        errorRetryAction = retry;
        errorModalRetry?.classList.toggle('hidden', !retry);
        errorModalClose?.classList.toggle('col-span-2', !retry);
        showModal(errorModal);
        (retry ? errorModalRetry : errorModalClose)?.focus();
    }

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
        const locationReady = !locationRequired || locationVerified;
        const canJoin = eligibility.eligible && locationReady && !queue.queued && !ownSpinActive && !submitting;
        const canSpin = eligibility.eligible && locationReady && queue.queued && eligibility.can_start && !ownSpinActive && !submitting;
        const available = eligibility.eligible && locationReady && !queue.queued && !eligibility.spin_in_progress && !ownSpinActive && !submitting;

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
            if (locationRequired && !locationVerified && !locationVerifying) {
                setHint('Location verification is required before you can spin.');
            } else if (queue.queued) {
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

    function applyExpectedEligibilityFailure(data) {
        if (!data || !EXPECTED_ELIGIBILITY_REASONS.has(data.reason)) return false;

        eligibility = {
            ...eligibility,
            eligible: false,
            reason: data.reason,
            message: data.message || 'You are not eligible to spin right now.',
            next_available_at: data.next_available_at || null,
        };
        renderState();
        return true;
    }

    function locationFailureCopy(data) {
        if (data?.reason === 'outside_radius') {
            return {
                title: 'Outside the event area',
                message: data.message || 'You must be at the event location to spin the wheel.',
            };
        }

        if (data?.reason === 'location_unavailable') {
            return {
                title: 'Could not verify location',
                message: data.message || 'Please allow location access and try again.',
            };
        }

        return {
            title: 'Location verification failed',
            message: data?.message || 'We could not confirm your current location. Please try again.',
        };
    }

    async function verifyLocation() {
        if (!locationRequired) {
            locationVerified = true;
            hideModal(locationModal);
            renderState();
            return true;
        }

        if (locationVerifying) return false;

        locationVerifying = true;
        locationVerified = false;
        closeErrorModal();
        showModal(locationModal);
        locationModal?.querySelector('[tabindex="-1"]')?.focus();
        renderState();

        const result = await getCoords(true);
        if (!result.ok) {
            locationVerifying = false;
            verifiedCoords = { lat: null, lng: null };
            renderState();
            showErrorModal({
                title: result.title,
                message: result.message,
                retry: verifyLocation,
            });
            return false;
        }

        let response;
        try {
            response = await post(config.routes.geofence, result.coords);
        } catch (_) {
            locationVerifying = false;
            verifiedCoords = { lat: null, lng: null };
            renderState();
            showErrorModal({
                title: 'Connection problem',
                message: 'We could not verify your location with the server. Check your connection and try again.',
                retry: verifyLocation,
            });
            return false;
        }

        locationVerifying = false;
        if (response.status === 200 && response.data?.passed) {
            verifiedCoords = result.coords;
            locationVerified = true;
            hideModal(locationModal);
            renderState();
            return true;
        }

        verifiedCoords = { lat: null, lng: null };
        renderState();
        showErrorModal({
            ...locationFailureCopy(response.data),
            retry: verifyLocation,
        });
        return false;
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

        if (locationRequired && !locationVerified) {
            await verifyLocation();
            return;
        }

        submitting = true;
        const queue = eligibility.queue || {};
        const shouldJoin = !queue.queued && (eligibility.spin_in_progress || Number(queue.count || 0) > 0);
        setHint(shouldJoin ? 'Please wait…' : 'Preparing your spin…');
        renderState();

        if (shouldJoin) {
            let response;
            try {
                response = await post(config.routes.queue);
            } catch (_) {
                submitting = false;
                renderState();
                showErrorModal({
                    title: 'Connection problem',
                    message: 'We could not join the spin queue. Check your connection and try again.',
                    retry: onSpinClick,
                });
                return;
            }

            const { status, data } = response;
            submitting = false;
            if (status === 202 && data.queued) {
                eligibility.queue = data.queue;
                eligibility.spin_in_progress = true;
                eligibility.can_start = false;
                renderState();
                return;
            }

            if (applyExpectedEligibilityFailure(data)) return;

            if (status === 409 && data.reason === 'spin_available') {
                await refreshEligibility();
                return;
            }

            setHint(data.message || 'Unable to spin right now.');
            renderState();
            showErrorModal({
                title: 'Could not join the queue',
                message: data.message || 'The spin queue is unavailable right now. Please try again.',
                retry: onSpinClick,
            });
            return;
        }

        // This must run inside the final tap gesture for mobile audio.
        await controller.audio.unlock();

        let response;
        try {
            response = await post(config.routes.start, verifiedCoords);
        } catch (_) {
            submitting = false;
            renderState();
            showErrorModal({
                title: 'Connection problem',
                message: 'We could not start the wheel. Check your connection and try again.',
                retry: onSpinClick,
            });
            return;
        }

        const { status, data } = response;
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

        if (applyExpectedEligibilityFailure(data)) return;

        if (data.reason === 'outside_radius' || data.reason === 'location_unavailable' || data.reason === 'geofence_blocked') {
            locationVerified = false;
            verifiedCoords = { lat: null, lng: null };
            renderState();
            showErrorModal({
                ...locationFailureCopy(data),
                retry: verifyLocation,
            });
            return;
        }

        if (data.reason === 'no_prizes') {
            renderState();
            showErrorModal({
                title: 'No prizes available',
                message: data.message || 'No prizes are currently available. Please try again later.',
                retry: onSpinClick,
            });
            return;
        }

        setHint(data.message || 'Unable to spin right now.');
        renderState();
        showErrorModal({
            title: 'Could not start the wheel',
            message: data.message || 'An unexpected error stopped the spin from starting. Please try again.',
            retry: onSpinClick,
        });
    }

    button?.addEventListener('click', onSpinClick);

    document.getElementById('turn-modal-dismiss')?.addEventListener('click', () => {
        turnAcknowledged = true;
        hideModal(turnModal);
    });

    errorModalClose?.addEventListener('click', () => {
        closeErrorModal();
        renderState();
    });

    errorModalRetry?.addEventListener('click', async () => {
        const retry = errorRetryAction;
        closeErrorModal();
        if (retry) await retry();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && errorModal && !errorModal.classList.contains('hidden')) {
            closeErrorModal();
            renderState();
        }
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
    if (locationRequired) {
        verifyLocation();
    } else {
        hideModal(locationModal);
    }
    poll();
    setInterval(poll, 3000);
}

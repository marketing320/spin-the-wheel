import { createWheel } from './wheel-scene';
import { SpinController } from './spin-controller';
import { fireConfetti } from './confetti-controller';
import { subscribeToSpins } from './live-sync';

/** Read the JSON config the blade view embedded. */
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
            (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            () => resolve({ lat: null, lng: null }),
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 }
        );
    });
}

export function initPlayerPage() {
    const config = readConfig();
    if (!config) return;

    const stage = document.getElementById('wheel-stage');
    const wheel = createWheel(stage, config.segments || []);
    const controller = new SpinController(wheel);

    const button = document.getElementById('spin-button');
    const hint = document.getElementById('spin-hint');
    const banner = document.getElementById('status-banner');
    const modal = document.getElementById('result-modal');

    const csrf = config.csrf || document.querySelector('meta[name=csrf-token]')?.content;
    let mySpinId = null;
    let busy = false;

    const setHint = (t) => { if (hint) hint.textContent = t || ''; };
    const setBusy = (state) => {
        busy = state;
        if (!button) return;
        button.disabled = state;
        button.querySelector('[data-label-idle]')?.classList.toggle('hidden', state);
        button.querySelector('[data-label-spinning]')?.classList.toggle('hidden', !state);
    };
    const enableIfEligible = (elig) => {
        if (!button) return;
        const ok = elig?.eligible && !elig?.spin_in_progress;
        button.disabled = !ok;
        if (banner && elig && !elig.eligible && elig.message) {
            banner.innerHTML = `<div class="glass rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">${elig.message}</div>`;
        } else if (banner && ok) {
            banner.innerHTML = '';
        }
    };

    async function post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body || {}),
        });
        return { status: res.status, data: await res.json().catch(() => ({})) };
    }

    async function refreshEligibility() {
        try {
            const res = await fetch(config.routes.eligibility, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            enableIfEligible(await res.json());
        } catch (e) { /* ignore */ }
    }

    function showResult(result) {
        if (!modal) return;
        const img = modal.querySelector('#result-prize-image');
        if (img) {
            if (result.prize_image) {
                img.src = result.prize_image;
                img.classList.remove('hidden');
            } else {
                img.classList.add('hidden');
            }
        }
        modal.querySelector('#result-prize-name').textContent = result.prize_name || 'A prize!';
        const rarityEl = modal.querySelector('#result-rarity');
        if (rarityEl && result.prize_rarity) {
            rarityEl.innerHTML = `<span class="pill font-display uppercase bg-slate-100 text-slate-700 ring-2 ring-slate-300">${result.prize_rarity}</span>`;
        }
        modal.querySelector('#result-message').textContent = result.redemption_message || '';
        const link = modal.querySelector('#result-link');
        if (link && mySpinId) link.href = config.routes.result.replace('SPIN_ID', mySpinId);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        fireConfetti(result.confetti_level || 'medium');
    }

    async function completeMySpin() {
        if (!mySpinId) return;
        const { data } = await post(config.routes.complete.replace('SPIN_ID', mySpinId));
        showResult(data.result || {});
    }

    async function onSpinClick() {
        if (busy) return;
        setBusy(true);
        setHint('Getting ready…');

        const coords = await getCoords(config.geofenceEnabled);
        const { status, data } = await post(config.routes.start, coords);

        if (status === 200 && data.ok) {
            mySpinId = data.spin.spin_session_id;
            setHint('Good luck! 🍀');
            controller.run(data.spin, { onComplete: completeMySpin });
        } else {
            setBusy(false);
            setHint(data.message || 'Unable to spin right now.');
            if (data.next_available_at) {
                setHint(`${data.message} `);
            }
        }
    }

    button?.addEventListener('click', onSpinClick);

    // Mirror other players' spins + keep the waiting state fresh.
    subscribeToSpins({
        onStarted: (e) => {
            if (e.spin_session_id === mySpinId) return; // our own — already animating
            setBusy(true);
            setHint(`${e.player_display || 'Another player'} is spinning…`);
            controller.run(e);
        },
        onCompleted: (e) => {
            if (e.spin_session_id === mySpinId) return;
            setHint('');
            setBusy(false);
            refreshEligibility();
        },
        onExpired: () => { setBusy(false); setHint(''); refreshEligibility(); },
    });

    const fetchActive = () => fetch(config.routes.active, { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .catch(() => null);

    if (window.Echo) {
        // Websocket mode: sync once if a spin is already in progress on load.
        if (config.spinInProgress) {
            fetchActive().then((d) => {
                if (d?.active && d.spin) {
                    setBusy(true);
                    setHint(`${d.spin.player_display || 'Another player'} is spinning…`);
                    controller.run(d.spin);
                }
            });
        }
    } else {
        // Polling mode: keep the waiting/eligibility state fresh and mirror
        // another player's spin without websockets.
        let mirroringId = null;
        const poll = async () => {
            if (mySpinId) return; // I'm spinning — don't interfere.
            const d = await fetchActive();
            if (d?.active && d.spin) {
                if (mirroringId !== d.spin.spin_session_id) {
                    mirroringId = d.spin.spin_session_id;
                    setBusy(true);
                    setHint(`${d.spin.player_display || 'Another player'} is spinning…`);
                    controller.run(d.spin);
                }
            } else {
                if (mirroringId) { mirroringId = null; setHint(''); }
                setBusy(false);
                refreshEligibility();
            }
        };
        poll();
        setInterval(poll, 4000);
    }
}

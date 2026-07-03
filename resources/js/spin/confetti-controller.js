import confetti from 'canvas-confetti';

/**
 * Fires celebration effects whose intensity scales with the prize's configured
 * confetti level (light → max). The same level runs identically on the player
 * phone and the live-view screen.
 */
const LEVELS = {
    light: { particles: 60, spread: 55, waves: 1, duration: 800 },
    medium: { particles: 120, spread: 70, waves: 1, duration: 1200 },
    strong: { particles: 180, spread: 90, waves: 2, duration: 1800, sparkle: true },
    heavy: { particles: 260, spread: 110, waves: 3, duration: 2600, sparkle: true, gold: true },
    max: { particles: 320, spread: 130, waves: 4, duration: 4200, sparkle: true, gold: true, fireworks: true },
};

const GOLD = ['#fde68a', '#f59e0b', '#fbbf24', '#fff7cd', '#ffd700'];
const RAINBOW = ['#6366f1', '#ec4899', '#22d3ee', '#34d399', '#f59e0b', '#f43f5e'];

function burst(opts, colors) {
    confetti({
        particleCount: opts.particles,
        spread: opts.spread,
        startVelocity: 45,
        origin: { y: 0.6 },
        colors,
        scalar: 1.05,
        ticks: 220,
    });
}

function fireworks(durationMs, colors) {
    const end = Date.now() + durationMs;
    (function frame() {
        confetti({ particleCount: 6, angle: 60, spread: 65, origin: { x: 0 }, colors });
        confetti({ particleCount: 6, angle: 120, spread: 65, origin: { x: 1 }, colors });
        if (Date.now() < end) requestAnimationFrame(frame);
    })();
}

export function fireConfetti(level = 'light') {
    const cfg = LEVELS[level] || LEVELS.light;
    const colors = cfg.gold ? GOLD : RAINBOW;

    for (let w = 0; w < cfg.waves; w++) {
        setTimeout(() => burst(cfg, colors), w * 350);
    }

    if (cfg.sparkle) {
        setTimeout(() => confetti({
            particleCount: Math.round(cfg.particles / 2),
            spread: 360,
            startVelocity: 25,
            gravity: 0.5,
            scalar: 0.7,
            origin: { y: 0.4 },
            colors,
        }), 250);
    }

    if (cfg.fireworks) {
        fireworks(cfg.duration, colors);
    }
}

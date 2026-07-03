import confetti from 'canvas-confetti';

const CELEBRATION_MS = 4000;
const EMISSION_MS = 3200;
const RAINBOW = ['#6366f1', '#ec4899', '#22d3ee', '#34d399', '#f59e0b', '#f43f5e'];
const GOLD = ['#fde68a', '#f59e0b', '#fbbf24', '#fff7cd', '#ffd700'];

// Particle counts are total budgets for the full celebration, not per burst.
const LEVELS = {
    light: { particles: 48, bursts: 3, spread: 52, velocity: 34, scalar: 0.78 },
    medium: { particles: 72, bursts: 4, spread: 62, velocity: 38, scalar: 0.84 },
    strong: { particles: 104, bursts: 5, spread: 72, velocity: 42, scalar: 0.9 },
    heavy: { particles: 144, bursts: 6, spread: 82, velocity: 46, scalar: 0.96, gold: true },
    max: { particles: 192, bursts: 8, spread: 92, velocity: 50, scalar: 1, gold: true, cannons: true },
};

let generation = 0;
let timers = [];

export function stopConfetti() {
    generation += 1;
    timers.forEach((timer) => clearTimeout(timer));
    timers = [];
    confetti.reset();
}

function launch(config, count, burstIndex, colors) {
    const fromLeft = burstIndex % 2 === 0;
    const cannon = config.cannons && burstIndex > 1;

    confetti({
        particleCount: count,
        angle: cannon ? (fromLeft ? 60 : 120) : 90,
        spread: cannon ? 58 : config.spread,
        startVelocity: config.velocity,
        decay: 0.93,
        gravity: 0.9,
        drift: cannon ? (fromLeft ? 0.25 : -0.25) : 0,
        ticks: 100,
        scalar: config.scalar,
        origin: cannon
            ? { x: fromLeft ? 0.04 : 0.96, y: 0.72 }
            : { x: 0.32 + ((burstIndex % 3) * 0.18), y: 0.62 },
        colors,
        zIndex: 60,
        disableForReducedMotion: true,
    });
}

/**
 * Rain an admin-uploaded image using the standalone `confettea` library,
 * layered ON TOP of the canvas-confetti celebration. No-op if the library
 * isn't loaded or no image is configured.
 */
function fireImageConfetti({ image, count = 30, size = 44 } = {}) {
    if (!image || typeof window === 'undefined' || !window.confettea) return;

    const base = {
        images: [image],
        shapes: [],
        particleCount: Math.max(5, count),
        particleSize: Math.max(16, size),
        spread: 78,
        startVelocity: 46,
        gravity: 0.9,
        decay: 0.94,
        ticks: 220,
        zIndex: 65, // above the result modal (z-50) and canvas confetti (z-60)
        origin: { x: 0.5, y: 0.56 },
    };

    window.confettea.burst(base);
    timers.push(setTimeout(() => {
        window.confettea.burst({ ...base, particleCount: Math.round(base.particleCount * 0.7), origin: { x: 0.5, y: 0.42 } });
    }, 350));
}

/**
 * Runs one cancellable, four-second celebration. The five prize levels vary
 * only their total particle budget and presentation, keeping phones stable.
 * When `options.image` is set, the uploaded image rains on top.
 */
export function fireConfetti(level = 'light', options = {}) {
    stopConfetti();

    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        return stopConfetti;
    }

    fireImageConfetti({ image: options.image, count: options.imageCount, size: options.imageSize });

    const currentGeneration = generation;
    const config = LEVELS[level] || LEVELS.light;
    const colors = config.gold ? GOLD : RAINBOW;
    const baseCount = Math.floor(config.particles / config.bursts);
    const remainder = config.particles % config.bursts;

    for (let index = 0; index < config.bursts; index += 1) {
        const delay = config.bursts === 1
            ? 0
            : Math.round((index / (config.bursts - 1)) * EMISSION_MS);
        const count = baseCount + (index < remainder ? 1 : 0);

        timers.push(setTimeout(() => {
            if (generation !== currentGeneration) return;
            launch(config, count, index, colors);
        }, delay));
    }

    timers.push(setTimeout(() => {
        if (generation !== currentGeneration) return;
        confetti.reset();
        timers = [];
    }, CELEBRATION_MS));

    return stopConfetti;
}

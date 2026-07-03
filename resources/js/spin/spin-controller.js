/**
 * Drives the wheel animation from a server payload. Uses server-aligned time so
 * the player phone and the live-view screen render the SAME rotation at the
 * SAME wall-clock moment — and a screen that joins mid-spin jumps to the
 * correct elapsed position and continues smoothly.
 */

const easeOutQuart = (p) => 1 - Math.pow(1 - p, 4);

export class SpinController {
    constructor(wheel) {
        this.wheel = wheel;
        this.clockOffset = 0; // clientNow - serverNow
        this.raf = null;
    }

    /** Align our clock to the server using the payload's server_time. */
    syncClock(payload) {
        if (payload?.server_time) {
            this.clockOffset = Date.now() - Date.parse(payload.server_time);
        }
    }

    serverNow() {
        return Date.now() - this.clockOffset;
    }

    /**
     * Animate to the payload's final angle. Resolves (and calls onComplete)
     * when the spin's end time is reached.
     */
    run(payload, { onComplete, onProgress } = {}) {
        this.syncClock(payload);
        cancelAnimationFrame(this.raf);

        const startMs = Date.parse(payload.started_at_server);
        const duration = Math.max(1, payload.spin_duration_ms || 6500);
        const finalAngle = payload.final_angle || 0;

        const tick = () => {
            const elapsed = this.serverNow() - startMs;
            const p = Math.min(Math.max(elapsed / duration, 0), 1);
            const angle = finalAngle * easeOutQuart(p);

            this.wheel?.setRotationDegrees(angle);
            onProgress?.(p);

            if (p < 1) {
                this.raf = requestAnimationFrame(tick);
            } else {
                this.wheel?.setRotationDegrees(finalAngle);
                onComplete?.();
            }
        };

        tick();
    }

    stop() {
        cancelAnimationFrame(this.raf);
    }
}

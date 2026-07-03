import { SpinAudio } from './spin-audio';
import { buildPhysicsTimeline } from './wheel-physics';

/**
 * Drives a deterministic Matter.js scene from server-aligned time so the
 * player page and live view render the same physical state at the same moment.
 */
export class SpinController {
    constructor(wheel, { pointer = null, soundUrl = null, onAudioBlocked = null } = {}) {
        this.wheel = wheel;
        this.pointer = pointer;
        this.audio = new SpinAudio(soundUrl, { onBlocked: onAudioBlocked });
        this.clockOffset = 0;
        this.raf = null;
    }

    syncClock(payload) {
        if (payload?.server_time) {
            this.clockOffset = Date.now() - Date.parse(payload.server_time);
        }
    }

    serverNow() {
        return Date.now() - this.clockOffset;
    }

    /** Index of the segment currently under the top pointer for a rotation. */
    static segmentAt(wheelDeg, segmentCount) {
        if (!segmentCount || segmentCount < 1) return 0;
        const seg = 360 / segmentCount;
        const norm = (((360 - (wheelDeg % 360)) % 360) + 360) % 360;
        return Math.floor(norm / seg) % segmentCount;
    }

    run(payload, { onComplete, onProgress, onSegment } = {}) {
        this.syncClock(payload);
        cancelAnimationFrame(this.raf);

        const startMs = Date.parse(payload.started_at_server);
        const duration = Math.max(1, payload.spin_duration_ms || 8000);
        const soundDuration = Math.max(duration, payload.sound_duration_ms || 11000);
        const finalAngle = payload.final_angle || 0;
        const segmentCount = payload.wheel_segments?.length || payload.segment_count || 1;
        const physics = buildPhysicsTimeline(finalAngle, duration, segmentCount);
        const initialElapsed = Math.max(0, this.serverNow() - startMs);

        if (initialElapsed < soundDuration) this.audio.playAt(initialElapsed);

        let lastSegment = -1;
        const emitSegment = (wheelDeg) => {
            const index = SpinController.segmentAt(wheelDeg, segmentCount);
            if (index !== lastSegment) {
                lastSegment = index;
                onSegment?.(index);
            }
        };

        const tick = () => {
            const elapsed = this.serverNow() - startMs;
            const progress = Math.min(Math.max(elapsed / duration, 0), 1);
            const state = physics.sample(progress);

            this.wheel?.setRotationDegrees(state.wheelDeg);
            if (this.pointer) this.pointer.style.rotate = `${state.pointerDeg}deg`;
            emitSegment(state.wheelDeg);
            onProgress?.(progress);

            if (progress < 1) {
                this.raf = requestAnimationFrame(tick);
            } else {
                this.wheel?.setRotationDegrees(finalAngle);
                if (this.pointer) this.pointer.style.rotate = '0deg';
                emitSegment(finalAngle);
                onComplete?.();
            }
        };

        tick();
    }

    stop() {
        cancelAnimationFrame(this.raf);
        this.audio.stop();
    }
}

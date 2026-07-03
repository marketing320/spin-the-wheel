/** Keeps the spin soundtrack aligned to the server spin timeline. */
export class SpinAudio {
    constructor(url, { onBlocked } = {}) {
        this.onBlocked = onBlocked;
        this.audio = url ? new Audio(url) : null;
        if (this.audio) this.audio.preload = 'auto';
    }

    async unlock() {
        if (!this.audio) return false;
        try {
            this.audio.muted = true;
            await this.audio.play();
            this.audio.pause();
            this.audio.currentTime = 0;
            this.audio.muted = false;
            return true;
        } catch (error) {
            this.audio.muted = false;
            this.onBlocked?.(error);
            return false;
        }
    }

    async playAt(elapsedMs = 0) {
        if (!this.audio) return;
        this.audio.pause();
        const seek = () => {
            const duration = Number.isFinite(this.audio.duration) ? this.audio.duration : Infinity;
            this.audio.currentTime = Math.max(0, Math.min(elapsedMs / 1000, duration));
        };
        seek();
        try {
            await this.audio.play();
        } catch (error) {
            this.onBlocked?.(error);
        }
    }

    stop() {
        if (!this.audio) return;
        this.audio.pause();
        this.audio.currentTime = 0;
    }
}

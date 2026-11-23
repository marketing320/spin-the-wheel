import * as THREE from 'three';

/**
 * Uses the same wheel face for Three.js and the rotated 2D canvas fallback.
 *
 * Layout convention (must match App\Services\WheelAnimationService):
 *   segment i occupies clockwise angles [i*seg, (i+1)*seg) starting at the top.
 *   Rotating the wheel clockwise by R degrees brings segment
 *   floor(((360 - R%360)%360)/seg) under the top pointer.
 */

const DEG2RAD = Math.PI / 180;
const INK = '#0f172a';
const EVENT_COLORS = { RM50: '#263b24', RM70: '#426a34', RM100: '#a8e827' };

/** Pick black or white text for legibility against a segment colour. */
function contrastColor(hex) {
    const c = (hex || '#888888').replace('#', '');
    const r = parseInt(c.substring(0, 2), 16) || 0;
    const g = parseInt(c.substring(2, 4), 16) || 0;
    const b = parseInt(c.substring(4, 6), 16) || 0;
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.6 ? INK : '#ffffff';
}

/** Draw the wheel face onto a 2D canvas (used as a texture or directly). */
function drawWheelFace(segments, size, eventTheme) {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const cx = size / 2;
    const cy = size / 2;
    const r = size / 2 - Math.round(size * 0.03);
    const n = Math.max(segments.length, 1);
    const seg = (Math.PI * 2) / n;
    const top = -Math.PI / 2; // 12 o'clock
    const border = Math.max(3, Math.round(size / (eventTheme ? 230 : 110)));
    const labelRadius = r * 0.92;
    const halfAngle = Math.min(seg / 2, Math.PI / 4);
    const dividerClearance = border / 2 + size * 0.006;

    for (let i = 0; i < n; i++) {
        const s = segments[i] || { color: '#0e75bc', label: '' };
        const a0 = top + i * seg;
        const a1 = a0 + seg;
        const label = String(s.label ?? '');
        const amount = eventTheme ? label.match(/^RM\s*\d+(?:[.,]\d+)?(?=\s|$)/i)?.[0].toUpperCase().replace(/\s+/g, '') : null;
        const color = eventTheme ? EVENT_COLORS[amount] || '#304728' : s.color || '#0e75bc';

        // Solid wedge with a hard ink border (pixel look, no gradient).
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, a0, a1);
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
        ctx.lineJoin = 'miter';
        ctx.lineWidth = border;
        ctx.strokeStyle = eventTheme ? '#0b1009' : INK;
        ctx.stroke();

        // Big, readable prize name running along the radius.
        ctx.save();
        ctx.translate(cx, cy);
        const angle = a0 + seg / 2;
        const flip = eventTheme && Math.cos(angle) < 0;
        ctx.rotate(angle + (flip ? Math.PI : 0));
        ctx.textAlign = flip ? 'left' : 'right';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = contrastColor(color);
        ctx.shadowColor = 'rgba(0,0,0,0.28)';
        ctx.shadowBlur = eventTheme ? 0 : 3;
        const shown = amount ?? (label.length > 14 ? label.slice(0, 13) + '…' : label);
        const fontSize = Math.round(eventTheme
            ? Math.min(r * 0.14, r * seg * 0.58)
            : Math.max(22, Math.min(48, (r * 0.92) / Math.max(5, shown.length))));
        const font = eventTheme ? 'system-ui, sans-serif' : '"Pixelify Sans", ui-monospace, monospace';
        ctx.font = `700 ${fontSize}px ${font}`;
        if (eventTheme) {
            const metrics = ctx.measureText(shown);
            const inward = flip ? metrics.actualBoundingBoxRight : metrics.actualBoundingBoxLeft;
            const height = Math.max(metrics.actualBoundingBoxAscent, metrics.actualBoundingBoxDescent);
            // The inner corner of the text is closest to the narrowing slice divider.
            const textExtent = inward * Math.sin(halfAngle) + height * Math.cos(halfAngle);
            const available = labelRadius * Math.sin(halfAngle) - dividerClearance;
            if (textExtent > available) {
                const fittedSize = Math.max(1, Math.floor(fontSize * available / textExtent));
                ctx.font = `700 ${fittedSize}px ${font}`;
            }
            ctx.fillText(shown, (flip ? -1 : 1) * labelRadius, 0, r * 0.62);
        } else {
            ctx.fillText(shown, r - border * 3, 0);
        }
        ctx.restore();
    }

    return canvas;
}

function makeWheelTexture(canvas, eventTheme) {
    const texture = new THREE.CanvasTexture(canvas);
    if (!eventTheme) {
        texture.magFilter = THREE.NearestFilter;
        texture.minFilter = THREE.NearestFilter;
        texture.generateMipmaps = false;
    }
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.anisotropy = 1;
    return texture;
}

class ThreeWheel {
    constructor(container, segments) {
        this.container = container;
        this.segments = segments;
        this.eventTheme = container.dataset.wheelTheme === 'event';
        this.rotationDeg = 0;
        this._redrawQueued = false;

        const size = Math.min(container.clientWidth || 360, container.clientHeight || 360) || 360;

        this.renderer = new THREE.WebGLRenderer({ antialias: this.eventTheme, alpha: true });
        this.renderer.setPixelRatio(this.eventTheme ? Math.min(window.devicePixelRatio || 1, 2) : 1);
        this.renderer.setSize(size, size, false);
        this.renderer.domElement.style.width = '100%';
        this.renderer.domElement.style.height = '100%';
        if (!this.eventTheme) this.renderer.domElement.style.imageRendering = 'pixelated';
        container.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
        this.camera.position.set(0, 0, 5.1); // close in so the disc nearly fills the stage

        this.wheelGroup = new THREE.Group();
        this.scene.add(this.wheelGroup);

        this.canvas = drawWheelFace(segments, 700, this.eventTheme);
        this.texture = makeWheelTexture(this.canvas, this.eventTheme);
        this.face = new THREE.Mesh(
            new THREE.CircleGeometry(2, 96),
            new THREE.MeshBasicMaterial({ map: this.texture, transparent: true })
        );
        this.wheelGroup.add(this.face);

        if (!this.eventTheme && document.fonts?.ready) {
            document.fonts.ready.then(() => this._scheduleRedraw());
        }

        this._onResize = () => this.resize();
        window.addEventListener('resize', this._onResize);
        this._animate = this._animate.bind(this);
        this._animate();
    }

    _scheduleRedraw() {
        if (this._redrawQueued) return;
        this._redrawQueued = true;
        requestAnimationFrame(() => {
            this._redrawQueued = false;
            this.texture.image = drawWheelFace(this.segments, 700, this.eventTheme);
            this.texture.needsUpdate = true;
        });
    }

    _animate() {
        this.face.rotation.z = -this.rotationDeg * DEG2RAD;
        this.renderer.render(this.scene, this.camera);
        this._raf = requestAnimationFrame(this._animate);
    }

    setRotationDegrees(deg) {
        this.rotationDeg = deg;
    }

    resize() {
        const size = Math.min(this.container.clientWidth, this.container.clientHeight) || 360;
        this.renderer.setSize(size, size, false);
    }

    dispose() {
        cancelAnimationFrame(this._raf);
        window.removeEventListener('resize', this._onResize);
        this.renderer.dispose();
    }
}

class CanvasWheel {
    constructor(container, segments) {
        this.segments = segments;
        this.eventTheme = container.dataset.wheelTheme === 'event';
        this.rotationDeg = 0;
        this._size = Math.min((container.clientWidth || 360) * 2, 1000);
        const canvas = drawWheelFace(segments, this._size, this.eventTheme);
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.borderRadius = '50%';
        if (!this.eventTheme) canvas.style.imageRendering = 'pixelated';
        container.appendChild(canvas);
        this.canvas = canvas;

        if (!this.eventTheme && document.fonts?.ready) {
            document.fonts.ready.then(() => this._redraw());
        }
    }

    _redraw() {
        const ctx = this.canvas.getContext('2d');
        const fresh = drawWheelFace(this.segments, this._size, this.eventTheme);
        ctx.clearRect(0, 0, this._size, this._size);
        ctx.drawImage(fresh, 0, 0);
    }

    setRotationDegrees(deg) {
        this.rotationDeg = deg;
        this.canvas.style.transform = `rotate(${deg}deg)`;
    }

    dispose() {}
}

/** Build the best available wheel renderer for the container. */
export function createWheel(container, segments) {
    if (!container) return null;
    container.innerHTML = '';

    try {
        return new ThreeWheel(container, segments);
    } catch (e) {
        console.warn('WebGL wheel unavailable, using 2D fallback.', e);
        return new CanvasWheel(container, segments);
    }
}

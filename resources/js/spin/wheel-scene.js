import * as THREE from 'three';

/**
 * Renders the prize wheel with a flat pixel-art aesthetic. Uses Three.js for a
 * subtle 3D stage when WebGL is available (flat-shaded, no gradients/glow,
 * nearest-neighbour "pixelated" texture) and falls back to a rotated 2D canvas.
 *
 * Layout convention (must match App\Services\WheelAnimationService):
 *   segment i occupies clockwise angles [i*seg, (i+1)*seg) starting at the top.
 *   Rotating the wheel clockwise by R degrees brings segment
 *   floor(((360 - R%360)%360)/seg) under the top pointer.
 */

const DEG2RAD = Math.PI / 180;
const INK = '#0f172a';

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
function drawWheelFace(segments, size) {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const cx = size / 2;
    const cy = size / 2;
    const r = size / 2 - Math.round(size * 0.03);
    const n = Math.max(segments.length, 1);
    const seg = (Math.PI * 2) / n;
    const top = -Math.PI / 2; // 12 o'clock
    const border = Math.max(3, Math.round(size / 110));

    for (let i = 0; i < n; i++) {
        const s = segments[i] || { color: '#0e75bc', label: '' };
        const a0 = top + i * seg;
        const a1 = a0 + seg;
        const color = s.color || '#0e75bc';

        // Solid wedge with a hard ink border (pixel look, no gradient).
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, a0, a1);
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
        ctx.lineJoin = 'miter';
        ctx.lineWidth = border;
        ctx.strokeStyle = INK;
        ctx.stroke();

        // Label in the arcade/pixel font, colour chosen for contrast.
        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(a0 + seg / 2);
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = contrastColor(color);
        const fontSize = Math.max(14, Math.min(30, (r * 0.8) / Math.max(6, String(s.label).length)));
        ctx.font = `700 ${Math.round(fontSize)}px "Pixelify Sans", ui-monospace, monospace`;
        const label = String(s.label ?? '');
        ctx.fillText(label.length > 16 ? label.slice(0, 15) + '…' : label, r - border * 4, 0);
        ctx.restore();
    }

    // Thick ink outer ring.
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.lineWidth = border * 2;
    ctx.strokeStyle = INK;
    ctx.stroke();

    return canvas;
}

function makePixelTexture(canvas, renderer) {
    const texture = new THREE.CanvasTexture(canvas);
    texture.magFilter = THREE.NearestFilter;
    texture.minFilter = THREE.NearestFilter;
    texture.generateMipmaps = false;
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.anisotropy = 1;
    return texture;
}

class ThreeWheel {
    constructor(container, segments) {
        this.container = container;
        this.segments = segments;
        this.rotationDeg = 0;

        const size = Math.min(container.clientWidth || 360, container.clientHeight || 360) || 360;

        this.renderer = new THREE.WebGLRenderer({ antialias: false, alpha: true });
        this.renderer.setPixelRatio(1); // crisp, blocky pixels
        this.renderer.setSize(size, size, false);
        this.renderer.domElement.style.width = '100%';
        this.renderer.domElement.style.height = '100%';
        this.renderer.domElement.style.imageRendering = 'pixelated';
        container.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
        this.camera.position.set(0, 0, 6.2);

        this.wheelGroup = new THREE.Group();
        this.scene.add(this.wheelGroup);

        // Flat-shaded textured disc that spins (no gradients, no lighting).
        this.canvas = drawWheelFace(segments, 512);
        const texture = makePixelTexture(this.canvas, this.renderer);
        this.texture = texture;
        this.face = new THREE.Mesh(
            new THREE.CircleGeometry(2, 72),
            new THREE.MeshBasicMaterial({ map: texture, transparent: true })
        );
        this.wheelGroup.add(this.face);

        // Flat ink ring bezel (static) — solid colour, no metal/gradient.
        const bezel = new THREE.Mesh(
            new THREE.TorusGeometry(2.04, 0.09, 8, 72),
            new THREE.MeshBasicMaterial({ color: 0x0f172a })
        );
        this.scene.add(bezel);

        // Redraw once the pixel font has loaded so labels use it.
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => {
                const redrawn = drawWheelFace(this.segments, 512);
                this.texture.image = redrawn;
                this.texture.needsUpdate = true;
            });
        }

        this._onResize = () => this.resize();
        window.addEventListener('resize', this._onResize);
        this._animate = this._animate.bind(this);
        this._animate();
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
        this.rotationDeg = 0;
        const size = Math.min(container.clientWidth || 360, container.clientHeight || 360) || 360;
        const canvas = drawWheelFace(segments, Math.min(size * 2, 900));
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.borderRadius = '50%';
        canvas.style.imageRendering = 'pixelated';
        container.appendChild(canvas);
        this.canvas = canvas;

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => {
                const ctx = this.canvas.getContext('2d');
                const fresh = drawWheelFace(this.segments, this.canvas.width);
                ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                ctx.drawImage(fresh, 0, 0);
            });
        }
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

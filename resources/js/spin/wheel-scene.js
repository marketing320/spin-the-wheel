import * as THREE from 'three';

/**
 * Renders the prize wheel. Uses Three.js for a glowing 3D stage when WebGL is
 * available, and transparently falls back to a rotated 2D canvas otherwise.
 *
 * Layout convention (must match App\Services\WheelAnimationService):
 *   segment i occupies clockwise angles [i*seg, (i+1)*seg) starting at the top.
 *   The pointer is fixed at the top; rotating the wheel clockwise by R degrees
 *   brings segment floor(((360 - R%360)%360)/seg) under the pointer.
 */

const DEG2RAD = Math.PI / 180;

/** Draw the full wheel face onto a 2D canvas used as a texture (or directly). */
function drawWheelFace(segments, size) {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const cx = size / 2;
    const cy = size / 2;
    const r = size / 2 - 4;
    const n = Math.max(segments.length, 1);
    const seg = (Math.PI * 2) / n;
    const top = -Math.PI / 2; // 12 o'clock in canvas coordinates

    for (let i = 0; i < n; i++) {
        const s = segments[i] || { color: '#6366f1', label: '' };
        const a0 = top + i * seg;
        const a1 = a0 + seg;

        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, a0, a1);
        ctx.closePath();
        ctx.fillStyle = s.color || '#6366f1';
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = 'rgba(0,0,0,0.25)';
        ctx.stroke();

        // Label
        ctx.save();
        ctx.translate(cx, cy);
        ctx.rotate(a0 + seg / 2);
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#ffffff';
        ctx.shadowColor = 'rgba(0,0,0,0.55)';
        ctx.shadowBlur = 4;
        const fontSize = Math.max(12, Math.min(28, (r * 0.9) / Math.max(6, String(s.label).length)));
        ctx.font = `700 ${fontSize}px ui-sans-serif, system-ui, sans-serif`;
        const label = String(s.label ?? '');
        ctx.fillText(label.length > 18 ? label.slice(0, 17) + '…' : label, r - 14, 0);
        ctx.restore();
    }

    // Outer ring
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.lineWidth = 6;
    ctx.strokeStyle = 'rgba(255,255,255,0.35)';
    ctx.stroke();

    return canvas;
}

class ThreeWheel {
    constructor(container, segments) {
        this.container = container;
        this.rotationDeg = 0;

        const size = Math.min(container.clientWidth || 360, container.clientHeight || 360) || 360;

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setSize(size, size, false);
        this.renderer.domElement.style.width = '100%';
        this.renderer.domElement.style.height = '100%';
        container.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
        this.camera.position.set(0, 0, 6.2);

        // Lights for the metallic bezel.
        this.scene.add(new THREE.AmbientLight(0xffffff, 0.9));
        const key = new THREE.DirectionalLight(0xffffff, 1.1);
        key.position.set(3, 5, 6);
        this.scene.add(key);
        const rim = new THREE.PointLight(0x8b5cf6, 1.4, 40);
        rim.position.set(-4, -2, 4);
        this.scene.add(rim);

        this.wheelGroup = new THREE.Group();
        this.scene.add(this.wheelGroup);

        // Wheel face (textured disc that spins).
        const texture = new THREE.CanvasTexture(drawWheelFace(segments, 1024));
        texture.anisotropy = this.renderer.capabilities.getMaxAnisotropy();
        texture.colorSpace = THREE.SRGBColorSpace;
        const face = new THREE.Mesh(
            new THREE.CircleGeometry(2, 96),
            new THREE.MeshBasicMaterial({ map: texture, transparent: true })
        );
        this.face = face;
        this.wheelGroup.add(face);

        // Static metallic bezel for depth.
        const bezel = new THREE.Mesh(
            new THREE.TorusGeometry(2.06, 0.12, 24, 96),
            new THREE.MeshStandardMaterial({ color: 0xf5c542, metalness: 0.9, roughness: 0.25 })
        );
        this.scene.add(bezel);

        // Glow halo behind the wheel.
        const halo = new THREE.Mesh(
            new THREE.CircleGeometry(2.6, 64),
            new THREE.MeshBasicMaterial({ color: 0x6366f1, transparent: true, opacity: 0.12 })
        );
        halo.position.z = -0.4;
        this.scene.add(halo);

        this._onResize = () => this.resize();
        window.addEventListener('resize', this._onResize);

        this._raf = null;
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
        this.rotationDeg = 0;
        const size = Math.min(container.clientWidth || 360, container.clientHeight || 360) || 360;
        const canvas = drawWheelFace(segments, Math.min(size * 2, 900));
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.borderRadius = '50%';
        canvas.style.transition = 'none';
        canvas.style.willChange = 'transform';
        container.appendChild(canvas);
        this.canvas = canvas;
    }

    setRotationDegrees(deg) {
        this.rotationDeg = deg;
        this.canvas.style.transform = `rotate(${deg}deg)`;
    }

    dispose() {}
}

/**
 * Build the best available wheel renderer for the container.
 */
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

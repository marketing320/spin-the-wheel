import Matter from 'matter-js';

const { Bodies, Body, Composite, Engine } = Matter;
const FIXED_STEP_MS = 1000 / 60;
const WHEEL_AIR_FRICTION = 0.0048;
const POINTER_AIR_FRICTION = 0.16;
const POINTER_SPRING = 0.055;
const POINTER_KICK = 0.19;
const MAX_POINTER_ANGLE = 0.34;

function createWorld() {
    const engine = Engine.create({ gravity: { x: 0, y: 0, scale: 0 } });
    const noCollision = { group: 0, category: 0x0002, mask: 0 };
    const wheel = Bodies.circle(0, 0, 1, { frictionAir: WHEEL_AIR_FRICTION, collisionFilter: noCollision });
    const pointer = Bodies.rectangle(0, 0, 0.12, 0.5, { frictionAir: POINTER_AIR_FRICTION, collisionFilter: noCollision });

    Body.setInertia(wheel, 1);
    Body.setInertia(pointer, 0.025);
    Composite.add(engine.world, [wheel, pointer]);

    return { engine, wheel, pointer };
}

function simulateFinalAngle(initialVelocity, steps) {
    const { engine, wheel } = createWorld();
    Body.setAngularVelocity(wheel, initialVelocity);
    for (let i = 0; i < steps; i++) Engine.update(engine, FIXED_STEP_MS);
    return wheel.angle;
}

/**
 * Precompute the same fixed-step Matter.js scene on every screen. Calibrating
 * initial velocity preserves the server-selected prize while air friction
 * supplies the gradual physical slowdown.
 */
export function buildPhysicsTimeline(finalAngleDeg, durationMs, segmentCount) {
    const steps = Math.max(1, Math.ceil(durationMs / FIXED_STEP_MS));
    const target = finalAngleDeg * Math.PI / 180;
    const trialAngle = simulateFinalAngle(0.1, steps) || 1;
    const initialVelocity = 0.1 * target / trialAngle;
    const { engine, wheel, pointer } = createWorld();
    const samples = [{ wheelDeg: 0, pointerDeg: 0 }];
    const segmentRadians = (Math.PI * 2) / Math.max(1, segmentCount);
    let lastBoundary = 0;

    Body.setAngularVelocity(wheel, initialVelocity);

    for (let i = 1; i <= steps; i++) {
        pointer.torque += (-pointer.angle * POINTER_SPRING)
            - (pointer.angularVelocity * POINTER_AIR_FRICTION * 0.12);
        Engine.update(engine, FIXED_STEP_MS);

        const boundary = Math.floor(Math.max(0, wheel.angle) / segmentRadians);
        if (boundary > lastBoundary) {
            const crossed = Math.min(boundary - lastBoundary, 3);
            Body.setAngularVelocity(pointer, pointer.angularVelocity + POINTER_KICK * crossed);
            lastBoundary = boundary;
        }

        if (Math.abs(pointer.angle) > MAX_POINTER_ANGLE) {
            Body.setAngle(pointer, Math.sign(pointer.angle) * MAX_POINTER_ANGLE);
            Body.setAngularVelocity(pointer, -pointer.angularVelocity * 0.35);
        }

        samples.push({
            wheelDeg: wheel.angle * 180 / Math.PI,
            pointerDeg: pointer.angle * 180 / Math.PI,
        });
    }

    samples[samples.length - 1].wheelDeg = finalAngleDeg;

    return {
        sample(progress) {
            const position = Math.min(samples.length - 1, Math.max(0, progress * (samples.length - 1)));
            const lower = Math.floor(position);
            const upper = Math.min(samples.length - 1, lower + 1);
            const mix = position - lower;

            return {
                wheelDeg: samples[lower].wheelDeg + (samples[upper].wheelDeg - samples[lower].wheelDeg) * mix,
                pointerDeg: samples[lower].pointerDeg + (samples[upper].pointerDeg - samples[lower].pointerDeg) * mix,
            };
        },
    };
}

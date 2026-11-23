import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { setImmediate } from 'node:timers/promises';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../resources/js/spin/player-sync.js', import.meta.url), 'utf8')
    .replace(/^import .*;\r?\n/gm, '')
    .replace('export function initPlayerPage', 'function initPlayerPage');

test('an eligible player can tap Spin while another player is spinning', async () => {
    let spin;
    const button = { disabled: false, querySelector: () => null, addEventListener: (_event, callback) => { spin = callback; } };
    const banner = { innerHTML: '' };
    const hint = { textContent: '' };
    const config = {
        csrf: 'test',
        eligibility: { eligible: false, message: 'Daily limit reached.' },
        routes: { eligibility: '/eligibility', active: '/active', start: '/start' },
    };
    const nodes = {
        'spin-config': { textContent: JSON.stringify(config) },
        'spin-button': button,
        'status-banner': banner,
        'spin-hint': hint,
    };
    let allowed = false;
    let busy = true;
    let poll;
    let checks = 0;
    let attempts = 0;
    runInNewContext(`${source}\ninitPlayerPage();`, {
        document: { getElementById: id => nodes[id] || null, addEventListener() {} },
        createWheel: () => ({}),
        SpinController: class { run() {} },
        setInterval: callback => { poll = callback; },
        fetch: async (url, options) => {
            if (url === '/start') {
                attempts++;
                assert.equal(options.method, 'POST');
                return { status: 423, json: async () => ({ reason: 'spin_in_progress' }) };
            }
            if (url === '/eligibility') {
                checks++;
                assert.equal(options.cache, 'no-store');
                return { ok: true, json: async () => ({
                    eligible: allowed,
                    message: allowed ? null : 'Daily limit reached.',
                    spin_in_progress: busy,
                }) };
            }
            return { json: async () => busy ? { active: true, spin: { spin_session_id: 99 } } : { active: false } };
        },
    });

    assert.match(banner.innerHTML, /Daily limit reached/);
    await setImmediate();
    assert.equal(button.disabled, true);

    allowed = true;
    await poll();
    assert.equal(button.disabled, false);
    assert.equal(banner.innerHTML, '');
    assert.equal(hint.textContent, '');

    await spin();
    assert.equal(attempts, 1);
    assert.equal(button.disabled, false);
    assert.match(hint.textContent, /spin is already in progress/);

    busy = false;
    await poll();
    assert.equal(button.disabled, false);
    assert.equal(banner.innerHTML, '');
    assert.equal(hint.textContent, '');

    allowed = false;
    await poll();
    assert.equal(button.disabled, true);
    assert.match(banner.innerHTML, /Daily limit reached/);
    assert.equal(checks, 4);
});

test('an active spin uses its saved segment order after the wheel layout changes', async () => {
    const first = { prize_id: 1, label: 'RM50' };
    const second = { prize_id: 2, label: 'RM100' };
    const savedSegments = [second, first];
    const prizeName = {};
    const nodes = {
        'spin-config': { textContent: JSON.stringify({ csrf: 'test', segments: [first, second], routes: { active: '/active' } }) },
        'pointer-prize-name': prizeName,
    };
    let displayedSegments;

    runInNewContext(`${source}\ninitPlayerPage();`, {
        document: { getElementById: id => nodes[id] || null, addEventListener() {} },
        createWheel: (_stage, segments) => ({ segments }),
        SpinController: class {
            constructor(wheel) { this.wheel = wheel; }
            run(_payload, { onSegment }) {
                displayedSegments = this.wheel.segments;
                onSegment(0);
            }
        },
        setInterval() {},
        fetch: async url => url === '/active'
            ? { json: async () => ({ active: true, spin: { spin_session_id: 99, wheel_segments: savedSegments } }) }
            : { ok: false },
    });

    await setImmediate();
    assert.equal(displayedSegments, savedSegments);
    assert.equal(prizeName.textContent, 'RM100');
});

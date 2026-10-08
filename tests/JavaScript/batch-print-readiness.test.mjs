import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../resources/views/residents/id-card-batch-landscape.blade.php', import.meta.url), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
class Element {
    constructor() { this.listeners = {}; this.dataset = {}; this.classes = new Set(); this.classList = { toggle: (c, on) => on ? this.classes.add(c) : this.classes.delete(c), add: c => this.classes.add(c), remove: c => this.classes.delete(c) }; }
    addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); }
    removeEventListener(type, fn) { this.listeners[type] = (this.listeners[type] || []).filter(f => f !== fn); }
    async emit(type) { for (const fn of this.listeners[type] || []) await fn({ currentTarget: this, preventDefault() {} }); }
}
function setup(statuses, responseOk = true) {
    const ids = Object.fromEntries(['print-selected','image-status','retry-images','print-error','batch-print-form','batch-order-form','order-selection-inputs','selection-summary','print-tracking-summary'].map(id => [id, new Element()]));
    const images = statuses.map((status, i) => Object.assign(new Element(), { complete: status !== 'loading', naturalWidth: status === 'ready' ? 300 : 0, src: 'https://example.test/qrcode/' + i, alt: 'QR ' + i, decode: async () => {} }));
    const choices = images.map(img => { const group = new Element(); group.querySelectorAll = () => [img]; const small = {}; group.querySelector = () => small; return Object.assign(new Element(), { checked: true, closest: () => group }); });
    const body = new Element(); const window = new Element(); window.location = { href: 'https://example.test/batch' };
    let prints = 0, posts = 0, payload;
    window.print = () => { prints++; };
    const timers = new Set();
    runInNewContext(script, {
        document: { body, getElementById: id => ids[id], querySelectorAll: selector => selector === '.resident-print-group img' ? images : selector.includes('.resident-print-selection') ? choices : [] },
        window, URL, Date, AbortController,
        setTimeout: fn => { timers.add(fn); return fn; }, clearTimeout: fn => timers.delete(fn),
        FormData: class { constructor() { payload = choices.filter(c => c.checked); } },
        fetch: async () => { posts++; if (typeof responseOk === 'function') await responseOk(); return { ok: responseOk, redirected: false, headers: { get: () => 'application/json' }, json: async () => ({}) }; },
    });
    return { ids, images, choices, body, window, timers, counts: () => ({ prints, posts, selected: payload?.length }) };
}
const settle = () => new Promise(resolve => setImmediate(resolve));

test('loading or broken QR blocks submission and browser print readiness', async () => {
    for (const status of ['loading', 'failed']) {
        const h = setup(['ready', status]); await settle();
        assert.equal(h.ids['print-selected'].disabled, true);
        await h.ids['batch-print-form'].emit('submit'); await h.window.emit('beforeprint');
        assert.equal(h.counts().posts, 0); assert.equal(h.counts().prints, 0);
        assert.equal(h.body.classes.has('batch-images-ready'), false);
    }
});
test('retry reloads failed QR and enables printing only after successful decode', async () => {
    const h = setup(['failed']); await settle();
    assert.equal(h.ids['retry-images'].hidden, false);
    await h.ids['retry-images'].emit('click');
    assert.match(h.images[0].src, /_print_retry=/);
    assert.equal(h.ids['print-selected'].disabled, true);
    h.images[0].naturalWidth = 300; await h.images[0].emit('load');
    assert.equal(h.ids['print-selected'].disabled, false);
    await h.ids['batch-print-form'].emit('submit');
    assert.deepEqual(h.counts(), { prints: 1, posts: 1, selected: 1 });
});
test('unselected failed QR does not block selected IDs; reselecting does', async () => {
    const h = setup(['ready','failed']); await settle();
    h.choices[1].checked = false; await h.choices[1].emit('change');
    assert.equal(h.ids['print-selected'].disabled, false);
    await h.ids['batch-print-form'].emit('submit');
    assert.deepEqual(h.counts(), { prints: 1, posts: 1, selected: 1 });
    h.choices[1].checked = true; await h.choices[1].emit('change');
    assert.equal(h.ids['print-selected'].disabled, true);
});
test('failed tracking request never opens print dialog and allows retry', async () => {
    const h = setup(['ready'], false); await settle();
    await h.ids['batch-print-form'].emit('submit');
    assert.equal(h.counts().prints, 0);
    assert.match(h.ids['print-error'].textContent, /Could not record/);
    assert.equal(h.ids['print-selected'].disabled, false);
});
test('stalled image becomes retryable and no selection stays blocked', async () => {
    const h = setup(['loading']); for (const timer of [...h.timers]) timer();
    assert.equal(h.ids['retry-images'].hidden, false);
    h.choices[0].checked = false; await h.choices[0].emit('change');
    assert.equal(h.ids['print-selected'].disabled, true);
});
test('image decoding failure blocks printing even with nonzero dimensions', async () => {
    const h = setup(['loading']); h.images[0].complete = true; h.images[0].naturalWidth = 300;
    h.images[0].decode = async () => { throw new Error('decode failed'); };
    await h.images[0].emit('load');
    assert.equal(h.ids['print-selected'].disabled, true);
    assert.equal(h.ids['retry-images'].hidden, false);
});

test('double click records and prints once while request is pending', async () => {
    let release;
    const h = setup(['ready'], () => new Promise(resolve => { release = resolve; })); await settle();
    const first = h.ids['batch-print-form'].emit('submit');
    await h.ids['batch-print-form'].emit('submit');
    assert.equal(h.counts().posts, 1);
    assert.equal(h.ids['print-selected'].disabled, true);
    release(); await first;
    assert.equal(h.counts().prints, 1);
});
test('successful tracking updates visible initiation status without navigation', async () => {
    const h = setup(['ready']); await settle();
    await h.ids['batch-print-form'].emit('submit');
    assert.equal(h.choices[0].closest().dataset.printInitiated, '1');
    assert.match(h.ids['print-tracking-summary'].textContent, /1 print initiated; 0 remaining/);
});

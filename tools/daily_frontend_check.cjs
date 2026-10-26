// Exercise the real routine autosave script with failed and successful responses.
// No browser, network or application database is used.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../resources/views/entrenamientos/crear.blade.php'), 'utf8');
const inline = source.match(/@push\('scripts'\)[\s\S]*?<script>([\s\S]*?)<\/script>/)[1]
    .replace(/let DIAS = .*;/, 'let DIAS = [];')
    .replace(/\{\{[\s\S]*?\}\}/g, 'qa-route');

async function autosave(response) {
    const nodes = new Map(), timers = new Map(), events = {}, toasts = [];
    let timerId = 0;
    const get = id => {
        if (!nodes.has(id)) nodes.set(id, {
            value: 'Prueba', textContent: '', listeners: {},
            classList: {add() {}, remove() {}, toggle() {}},
            setAttribute() {}, querySelectorAll: () => [],
            addEventListener(name, handler) { this.listeners[name] = handler; },
        });
        return nodes.get(id);
    };
    const context = vm.createContext({
        document: {getElementById: get, querySelector: () => ({content: 'csrf-test'})},
        window: {
            addEventListener: (name, handler) => { events[name] = handler; },
            showAlphaToast: (message, type) => toasts.push({message, type}),
        },
        setTimeout: (callback, delay) => { const id = ++timerId; timers.set(id, {callback, delay}); return id; },
        clearTimeout: id => timers.delete(id),
        fetch: async () => response(),
    });
    vm.runInContext(inline, context);
    get('campo-nombre').listeners.input();
    [...timers.values()].find(timer => timer.delay === 600).callback();
    await new Promise(resolve => setImmediate(resolve));
    let warned = false;
    events.beforeunload({preventDefault() { warned = true; }});
    return {label: get('estado-guardado').textContent, warned, toasts};
}

(async () => {
    const failures = [
        ['invalid JSON', () => ({ok: true, status: 200, json: async () => { throw new SyntaxError('HTML response'); }})],
        ['null JSON', () => ({ok: true, status: 200, json: async () => null})],
        ['array JSON', () => ({ok: true, status: 200, json: async () => []})],
        ['false success', () => ({ok: true, status: 200, json: async () => ({ok: false})})],
        ['empty object', () => ({ok: true, status: 200, json: async () => ({})})],
        ['error message', () => ({ok: true, status: 200, json: async () => ({message: 'Error'})})],
        ['string success', () => ({ok: true, status: 200, json: async () => ({ok: 'false', rutina: {id: 1}})})],
        ['zero success', () => ({ok: true, status: 200, json: async () => ({ok: 0, rutina: {id: 1}})})],
        ['missing routine', () => ({ok: true, status: 200, json: async () => ({ok: true})})],
        ['expired session', () => ({ok: false, status: 419, json: async () => ({})})],
        ['offline', () => { throw new Error('Offline'); }],
    ];
    for (const [name, response] of failures) {
        const result = await autosave(response);
        assert.notEqual(result.label, 'Guardado', name + ' must not display saved');
        assert.equal(result.warned, true, name + ' must preserve unsaved changes warning');
        assert.equal(result.toasts.at(-1)?.type, 'error', name + ' must report failure');
        console.log('PASS routine autosave: ' + name);
    }
    const saved = await autosave(() => ({ok: true, status: 200, json: async () => ({ok: true, rutina: {id: 1}})}));
    assert.equal(saved.label, 'Guardado');
    assert.equal(saved.warned, false);
    assert.equal(saved.toasts.length, 0);
    console.log('PASS routine autosave: confirmed save clears unsaved warning');
    console.log('12 routine autosave checks passed (isolated JavaScript VM).');
})().catch(error => { console.error(error); process.exitCode = 1; });

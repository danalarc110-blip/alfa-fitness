// Regression checks using the real Blade scripts and modal event handlers.
// Runs offline in a JavaScript VM: this is not a browser or accessibility-tree test.
const assert = require('node:assert/strict');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const baseline = process.env.AUDIT_FIX_BASELINE_DIR;
const trainingPath = baseline ? path.join(baseline, 'entrenar.blade.php') : path.join(root, 'resources/views/entrenamientos/entrenar.blade.php');
const exercisesPath = baseline ? path.join(baseline, 'ejercicios.blade.php') : path.join(root, 'resources/views/ejercicios/index.blade.php');
const training = fs.readFileSync(trainingPath, 'utf8');
const exercises = fs.readFileSync(exercisesPath, 'utf8');
const animations = fs.readFileSync(path.join(root, 'resources/js/animations.js'), 'utf8');
const inline = source => {
    const match = source.match(/@push\('scripts'\)[\s\S]*?<script>([\s\S]*?)<\/script>/);
    assert.ok(match, 'the Blade view must contain its real script');
    return match[1].replace(/\{\{ \$diaSeleccionado \? \$diaSeleccionado->id : 'null' \}\}/g, 'null')
        .replace(/\{\{[\s\S]*?\}\}/g, 'qa-value');
};

function timerEnvironment() {
    let now = 1000000;
    let sequence = 0;
    const intervals = new Map(), nodes = new Map(), documentEvents = new Map(), windowEvents = new Map();
    const toasts = [], vibrations = [];
    const listen = (events, name, handler) => {
        if (!events.has(name)) events.set(name, []);
        events.get(name).push(handler);
    };
    const element = id => {
        if (!nodes.has(id)) nodes.set(id, {
            textContent: '', className: '', style: {}, disabled: false,
            classList: {add() {}, remove() {}}, setAttribute() {}, removeAttribute() {},
        });
        return nodes.get(id);
    };
    class ClockDate extends Date {
        constructor(...args) { super(...(args.length ? args : [now])); }
        static now() { return now; }
    }
    const document = {
        visibilityState: 'visible', hidden: false, getElementById: element,
        addEventListener: (name, handler) => listen(documentEvents, name, handler),
        querySelectorAll: () => [],
    };
    const context = vm.createContext({
        document, Date: ClockDate, navigator: {vibrate: pattern => vibrations.push(pattern)},
        localStorage: {getItem: () => 'true', setItem() {}},
        window: {
            showAlphaToast: (message, type) => toasts.push({message, type}),
            addEventListener: (name, handler) => listen(windowEvents, name, handler),
        },
        setInterval: callback => {
            const id = ++sequence;
            intervals.set(id, callback);
            assert.ok(intervals.size <= 1, 'only one countdown interval may be active');
            return id;
        },
        clearInterval: id => intervals.delete(id),
    });
    vm.runInContext(inline(training), context, {filename: trainingPath});
    return {
        run: code => vm.runInContext(code, context), element, intervals, vibrations,
        advance: milliseconds => { now += milliseconds; },
        tick: () => [...intervals.values()].forEach(callback => callback()),
        visible: () => (documentEvents.get('visibilitychange') || []).forEach(handler => handler({type: 'visibilitychange'})),
        pageshow: () => (windowEvents.get('pageshow') || []).forEach(handler => handler({type: 'pageshow'})),
        alerts: () => toasts.filter(item => /Tiempo de descanso cumplido/i.test(item.message)),
    };
}

function attributes(tag) {
    const result = {};
    for (const match of tag.matchAll(/([\w:-]+)\s*=\s*(["'])([\s\S]*?)\2/g)) result[match[1]] = match[3];
    return result;
}

function modalMarkup(id) {
    const start = exercises.indexOf('<div id="' + id + '"');
    assert.notEqual(start, -1, id + ' must exist');
    const nextModal = exercises.indexOf('{{-- MODAL', start + 1);
    const end = nextModal === -1 ? exercises.indexOf('@endcan', start) : nextModal;
    return exercises.slice(start, end);
}

function exerciseEnvironment(id) {
    const document = {activeElement: null, body: {style: {overflow: 'auto'}}};
    const listeners = {}, nodes = new Map();
    const create = attrs => {
        const classes = new Set((attrs.class || '').split(/\s+/));
        const node = {
            id: attrs.id, value: '', tabIndex: 0, attrs,
            classList: {add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)},
            hasAttribute: name => Object.hasOwn(attrs, name),
            getAttribute: name => attrs[name] ?? null,
            setAttribute: (name, value) => { attrs[name] = value; },
            getClientRects: () => [{}],
            focus: () => { document.activeElement = node; },
        };
        if (attrs.id) nodes.set(attrs.id, node);
        return node;
    };
    const markup = modalMarkup(id);
    const modal = create(attributes(markup.match(/^<div\b[^>]*>/)[0]));
    const controls = [...markup.matchAll(/<(button|input)\b[^>]*>/g)]
        .map(match => Object.assign(create(attributes(match[0])), {tagName: match[1]}));
    for (const match of markup.matchAll(/<form\b[^>]*>/g)) create(attributes(match[0]));
    modal.querySelector = selector => selector === 'button' ? controls.find(item => item.tagName === 'button') : null;
    modal.querySelectorAll = () => controls;
    modal.contains = node => node === modal || controls.includes(node);
    const trigger = create({id: 'qa-trigger'});
    trigger.focus();
    document.getElementById = key => nodes.get(key);
    document.querySelector = selector => selector === 'meta[name="csrf-token"]' ? {content: 'qa-csrf'} : nodes.get(selector.slice(1));
    document.querySelectorAll = () => [];
    document.addEventListener = (name, handler) => { listeners[name] = handler; };
    const context = vm.createContext({
        document, window: {matchMedia: () => ({matches: true, addEventListener() {}})}, Set,
    });
    vm.runInContext(animations, context, {filename: 'resources/js/animations.js'});
    vm.runInContext(inline(exercises), context, {filename: exercisesPath});
    const run = code => vm.runInContext(code, context);
    const open = id === 'modal-nuevo-ejercicio'
        ? () => {
            const button = [...exercises.matchAll(/<button\b[^>]*>/g)].map(match => attributes(match[0]))
                .find(attrs => attrs.onclick?.includes('modal-nuevo-ejercicio') && !attrs.onclick.includes('Close') && !attrs.onclick.includes(".add('hidden')"));
            assert.ok(button, 'the actual new-exercise trigger must exist');
            run(button.onclick);
        }
        : () => run('abrirEditarEjercicio({id: 7, nombre: "Press", grupo_muscular: "Pecho", subgrupo: "Superior"})');
    return {document, modal, controls, trigger, listeners, run, open};
}

let passed = 0, failed = 0, errors = 0;
function check(name, test) {
    try { test(); passed++; console.log('PASS ' + name); }
    catch (error) {
        if (error instanceof assert.AssertionError) { failed++; console.error('FAIL ' + name + ': ' + error.message); }
        else { errors++; console.error('ERROR ' + name + ': ' + error.stack); }
    }
}

check('90-second timer counts a 60-second suspension as elapsed time', () => {
    const env = timerEnvironment();
    env.run('ajustarTimer(90); iniciarTimer();');
    env.advance(60000); env.tick();
    assert.equal(env.element('timer-display').textContent, '00:30');
    assert.equal(env.alerts().length, 0);
});

check('a late completion tick ends immediately and alerts only once', () => {
    const env = timerEnvironment();
    env.run('ajustarTimer(90); iniciarTimer();');
    env.advance(120000); env.tick();
    assert.equal(env.element('timer-display').textContent, '00:00');
    assert.equal(env.element('timer-estado').textContent, '¡A entrenar!');
    assert.equal(env.intervals.size, 0);
    env.tick(); env.visible(); env.pageshow(); env.visible();
    assert.equal(env.alerts().length, 1);
    assert.equal(env.vibrations.length, 1);
});

check('pause before a callback preserves remaining time and resume excludes paused time', () => {
    const env = timerEnvironment();
    env.run('ajustarTimer(90); iniciarTimer();');
    env.advance(30000); env.run('pausarTimer();');
    assert.equal(env.element('timer-display').textContent, '01:00');
    assert.equal(env.intervals.size, 0);
    env.advance(300000); env.tick();
    assert.equal(env.element('timer-display').textContent, '01:00');
    env.run('iniciarTimer();'); env.advance(10000); env.tick();
    assert.equal(env.element('timer-display').textContent, '00:50');
    assert.equal(env.alerts().length, 0);
});

check('fractional pause retains the exact remaining duration', () => {
    const env = timerEnvironment();
    env.run('ajustarTimer(90); iniciarTimer();');
    env.advance(30500); env.run('pausarTimer();');
    env.advance(120000); env.run('iniciarTimer();');
    env.advance(59500); env.tick();
    assert.equal(env.element('timer-display').textContent, '00:00');
    assert.equal(env.alerts().length, 1);
});

check('reset stops the countdown, restores its base and rearms completion for a new rest', () => {
    const env = timerEnvironment();
    env.run('ajustarTimer(90); iniciarTimer();');
    env.advance(15000); env.tick(); env.run('reiniciarTimer();');
    assert.equal(env.element('timer-display').textContent, '01:30');
    assert.equal(env.element('timer-estado').textContent, 'Listo');
    assert.equal(env.intervals.size, 0);
    env.advance(120000); env.tick();
    assert.equal(env.element('timer-display').textContent, '01:30');
    for (let rest = 1; rest <= 2; rest++) {
        env.run('reiniciarTimer(); iniciarTimer();'); env.advance(90000); env.tick();
        assert.equal(env.alerts().length, rest);
    }
});

check('repeated start and resume never create duplicate active intervals', () => {
    const env = timerEnvironment();
    env.run('ajustarTimer(90); iniciarTimer(); iniciarTimer(); iniciarTimer();');
    assert.equal(env.intervals.size, 1);
    env.advance(1000); env.tick();
    assert.equal(env.element('timer-display').textContent, '01:29');
    env.run('pausarTimer(); iniciarTimer(); iniciarTimer();');
    assert.equal(env.intervals.size, 1);
    env.advance(1000); env.tick();
    assert.equal(env.element('timer-display').textContent, '01:28');
});

check('visibility and pageshow synchronize elapsed time without waiting for an interval', () => {
    const env = timerEnvironment();
    env.run('ajustarTimer(90); iniciarTimer();');
    env.advance(60000); env.visible();
    assert.equal(env.element('timer-display').textContent, '00:30');
    env.advance(31000); env.pageshow(); env.visible();
    assert.equal(env.element('timer-display').textContent, '00:00');
    assert.equal(env.alerts().length, 1);
});

for (const id of ['modal-nuevo-ejercicio', 'modal-editar-ejercicio']) {
    check(id + ' actual handlers register modal focus, keyboard closure and focus restoration', () => {
        const env = exerciseEnvironment(id);
        env.open();
        assert.equal(env.modal.classList.contains('hidden'), false);
        assert.equal(env.modal.attrs.role, 'dialog');
        assert.equal(env.modal.attrs['aria-modal'], 'true');
        assert.ok(env.modal.contains(env.document.activeElement), 'opening must move focus inside');
        const first = env.controls[0], last = env.controls.at(-1);
        last.focus(); env.listeners.keydown({key: 'Tab', shiftKey: false, preventDefault() {}});
        assert.equal(env.document.activeElement, first);
        first.focus(); env.listeners.keydown({key: 'Tab', shiftKey: true, preventDefault() {}});
        assert.equal(env.document.activeElement, last);
        env.listeners.keydown({key: 'Escape'});
        assert.equal(env.modal.classList.contains('hidden'), true);
        assert.equal(env.document.activeElement, env.trigger);
        assert.equal(env.document.body.style.overflow, 'auto');
        const closeButtons = env.controls.filter(control => control.attrs.onclick);
        assert.equal(closeButtons.length, 2, 'both close and cancel controls must be present');
        for (const button of closeButtons) {
            env.open(); env.run(button.attrs.onclick);
            assert.equal(env.modal.classList.contains('hidden'), true);
            assert.equal(env.document.activeElement, env.trigger);
            assert.equal(env.document.body.style.overflow, 'auto');
        }
    });

    check(id + ' markup associates its title and all five field labels', () => {
        const markup = modalMarkup(id);
        const attrs = attributes(markup.match(/^<div\b[^>]*>/)[0]);
        assert.ok(attrs['aria-labelledby'], 'the dialog must reference its purpose-specific heading');
        const heading = [...markup.matchAll(/<h[1-6]\b[^>]*>/g)].map(match => attributes(match[0]));
        assert.ok(heading.some(item => item.id === attrs['aria-labelledby']));
        const labels = [...markup.matchAll(/<label\b[^>]*>/g)].map(match => attributes(match[0]));
        const fields = [...markup.matchAll(/<input\b[^>]*>/g)].map(match => attributes(match[0]));
        assert.equal(fields.length, 5);
        assert.equal(new Set(fields.map(field => field.id)).size, 5);
        for (const field of fields) {
            assert.ok(field.id, field.name + ' needs an id');
            assert.equal(labels.filter(label => label.for === field.id).length, 1, field.name + ' needs one associated visible label');
        }
        const close = [...markup.matchAll(/<button\b[^>]*>[\s\S]*?<\/button>/g)]
            .find(match => match[0].includes('&times;'));
        assert.ok(close && attributes(close[0])['aria-label'], 'the icon close button needs a purpose-specific accessible name');
    });
}

console.log(`${passed} passed; ${failed} failed; ${errors} execution errors (offline VM and markup; browser checks are separate).`);
console.log('Training SHA256: ' + crypto.createHash('sha256').update(training).digest('hex'));
console.log('Exercises SHA256: ' + crypto.createHash('sha256').update(exercises).digest('hex'));
process.exitCode = failed || errors ? 1 : 0;

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../resources/views/entrenamientos/entrenar.blade.php'), 'utf8');
const toggleSource = source.slice(source.indexOf('    function toggleSerie('), source.indexOf('    function actualizarProgreso('));
const timerSource = source.slice(source.indexOf('    function iniciarTimer('), source.indexOf('    function pausarTimer('));
const finishSource = source.slice(source.indexOf('    async function finalizarEntrenamiento('), source.indexOf('    // Inicializar progreso al cargar'))
    .replace(/{{[\s\S]*?}}/g, '1');
assert.ok(toggleSource.includes('function toggleSerie'), 'No se encontró el control real de series.');
assert.ok(finishSource.includes('function finalizarEntrenamiento'), 'No se encontró el guardado real del entrenamiento.');

function button() {
    return {dataset: {completada: 'false'}, disabled: false, attributes: {},
        classList: {add() {}, remove() {}},
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; }};
}

function seriesContext() {
    const context = vm.createContext({button: button(), adjusted: [], starts: 0,
        obtenerAudioCtx() {}, actualizarProgreso() {},
        ajustarTimer(value) { context.adjusted.push(value); },
        iniciarTimer() { context.starts++; }});
    vm.runInContext(toggleSource, context);
    return context;
}

test('Completar una serie sin descanso conserva cero y no inicia un minuto de espera', () => {
    const context = seriesContext();
    vm.runInContext('toggleSerie(button, 0)', context);
    assert.equal(context.adjusted[0], 0);
    assert.equal(context.starts, 0);
    assert.equal(context.button.dataset.completada, 'true');
    assert.equal(context.button.attributes['aria-pressed'], 'true');
});

test('El descanso configurado y el predeterminado siguen iniciando el cronómetro', () => {
    for (const [value, expected] of [[90, 90], [undefined, 60]]) {
        const context = seriesContext();
        context.rest = value;
        vm.runInContext('toggleSerie(button, rest)', context);
        assert.equal(context.adjusted[0], expected);
        assert.equal(context.starts, 1);
        vm.runInContext('toggleSerie(button, rest)', context);
        assert.equal(context.button.dataset.completada, 'false');
        assert.equal(context.starts, 1);
    }
});

test('La alerta de descanso se dispara al llegar a cero, sin añadir otro segundo', () => {
    const state = {textContent: '', className: ''};
    const context = vm.createContext({segundosRestantes: 2, timerInterval: null, alerts: 0,
        document: {getElementById() { return state; }}, actualizarDisplay() {},
        setInterval(callback) { context.tick = callback; return 1; },
        pausarTimer() { context.timerInterval = null; },
        reproducirAlertaDescanso() { context.alerts++; }, window: {showAlphaToast() {}},
    });
    vm.runInContext(timerSource, context);
    vm.runInContext('iniciarTimer()', context);
    context.tick();
    assert.equal(context.segundosRestantes, 1);
    assert.equal(context.alerts, 0);
    context.tick();
    assert.equal(context.segundosRestantes, 0);
    assert.equal(context.alerts, 1);
    assert.equal(context.timerInterval, null);
    assert.equal(state.textContent, '¡A entrenar!');
});

test('Un fallo de red permite reintentar con el mismo UUID y muestra los valores guardados por el servidor', async () => {
    const finishButton = button();
    const summary = {textContent: ''};
    const payloads = [];
    const toasts = [];
    const context = vm.createContext({
        sesionIniciadaTimestamp: Date.now() - 300000, sesionUuid: 'a1b2c3d4-1111-4111-a111-a1b2c3d4e5f6',
        sesionGuardada: false, guardandoSesion: false, opens: 0, pausarTimer() {},
        document: {
            querySelectorAll(selector) { return {length: selector.includes('data-completada') ? 2 : 3}; },
            getElementById(id) { return id === 'btn-finalizar-entrenamiento' ? finishButton : summary; },
        },
        window: {showAlphaToast(message) { toasts.push(message); }, alphaAnimateModalOpen() { context.opens++; }},
        async fetch(url, options) {
            payloads.push(JSON.parse(options.body));
            if (payloads.length === 1) throw new Error('Sin conexión');
            return {ok: true, json: async () => ({ok: true, sesion: {series_completadas: 1, total_series: 3}})};
        },
    });
    vm.runInContext(finishSource, context);
    await vm.runInContext('finalizarEntrenamiento()', context);
    assert.equal(finishButton.disabled, false);
    assert.equal(context.guardandoSesion, false);
    assert.equal(context.sesionGuardada, false);
    assert.equal(context.opens, 0);
    assert.equal(toasts.length, 1);

    await vm.runInContext('finalizarEntrenamiento()', context);
    assert.equal(payloads.length, 2);
    assert.equal(payloads[0].sesion_uuid, payloads[1].sesion_uuid);
    assert.equal(context.sesionGuardada, true);
    assert.equal(finishButton.disabled, false);
    assert.equal(context.opens, 1);
    assert.equal(summary.textContent, 'Tu sesión se guardó con 1 de 3 series completadas.');
});

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'resources/views/entrenamientos/entrenar.blade.php'), 'utf8');
const timerSource = source.slice(source.indexOf('    function formatearTiempo('), source.indexOf('    // TOGGLE DE SERIES INTERACTIVAS'));
assert.ok(timerSource.includes('function iniciarTimer'), 'Se ejecuta el cronómetro real de la vista.');

function timerContext() {
    let now = 0;
    const nodes = new Map();
    const context = vm.createContext({segundosRestantes: 60, tiempoBase: 60, timerInterval: null, timerFinTimestamp: null,
        Date: {now: () => now},
        document: {getElementById(id) { if (!nodes.has(id)) nodes.set(id, {}); return nodes.get(id); }},
        setInterval(callback) { context.tick = callback; return 1; }, clearInterval() {},
        alerts: 0, reproducirAlertaDescanso() { context.alerts++; }, window: {showAlphaToast() {}},
    });
    vm.runInContext(timerSource, context);
    return {context, nodes, advance(milliseconds) { now += milliseconds; }};
}

test('El descanso descuenta el tiempo real aunque el navegador suspenda callbacks', () => {
    const {context, nodes, advance} = timerContext();
    vm.runInContext('iniciarTimer()', context);
    advance(35000);
    context.tick();
    assert.equal(context.segundosRestantes, 25);
    assert.equal(nodes.get('timer-display').textContent, '00:25');
    advance(30000);
    context.tick();
    assert.equal(context.segundosRestantes, 0);
    assert.equal(context.timerInterval, null);
    assert.equal(context.alerts, 1);
    assert.equal(nodes.get('timer-estado').textContent, '¡A entrenar!');
});

test('Pausar captura el tiempo transcurrido entre callbacks y reanudar conserva lo pendiente', () => {
    const {context, advance} = timerContext();
    vm.runInContext('iniciarTimer()', context);
    advance(15200);
    vm.runInContext('pausarTimer()', context);
    assert.equal(context.segundosRestantes, 45);
    advance(120000);
    vm.runInContext('iniciarTimer()', context);
    advance(45000);
    context.tick();
    assert.equal(context.segundosRestantes, 0);
    assert.equal(context.alerts, 1);
});

test('Un descanso de cero no crea intervalos ni alarmas', () => {
    const {context} = timerContext();
    vm.runInContext('ajustarTimer(0); iniciarTimer()', context);
    assert.equal(context.timerInterval, null);
    assert.equal(context.segundosRestantes, 0);
    assert.equal(context.alerts, 0);
});


const builder = fs.readFileSync(path.join(root, 'resources/views/entrenamientos/crear.blade.php'), 'utf8');
const rowSource = builder.slice(builder.indexOf('    function renderFila('), builder.indexOf('    /* ---- toggle imagen'));
const saveSource = builder.slice(builder.indexOf('    function guardarCampo('), builder.indexOf('    function eliminarEjercicio('));

function field(html, value) {
    const tag = html.match(/<input[^>]*data-campo="series"[^>]*>/)[0];
    return {dataset: {id: '7', campo: 'series'}, value, disabled: /\bdisabled\b/.test(tag),
        setAttribute() {}, removeAttribute() {}};
}

test('Reconstruir el panel conserva el bloqueo del campo pendiente y permite editarlo al terminar', async () => {
    const calls = [];
    let visible;
    const record = {id: 7, series: 3, repeticiones: '10', peso: null, descanso_segundos: 60,
        ejercicio: {nombre: 'Sentadilla', grupo_muscular: 'Piernas'}};
    const context = vm.createContext({DIAS: [{ejercicios: [record]}], record, camposGuardando: new Set(),
        h: value => String(value ?? ''), miniImg: () => '', marcarGuardado() {}, mostrarError() {},
        URLS: {actualizarEjercicio: id => '/ejercicios/' + id},
        document: {querySelector() { return visible; }},
        api(url, method, body) { return new Promise((resolve, reject) => calls.push({body, resolve, reject})); },
    });
    vm.runInContext(rowSource + saveSource, context);
    visible = field(vm.runInContext('renderFila(record)', context), '4');
    context.input = visible;
    vm.runInContext('guardarCampo(input)', context);
    visible = field(vm.runInContext('renderFila(record)', context), '5');
    assert.equal(visible.disabled, true, 'Cambiar de día no debe reactivar un campo que sigue guardándose.');
    assert.equal(calls.length, 1);
    calls[0].resolve({rutina_ejercicio: {...record, series: 4}});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(visible.disabled, false, 'Se desbloquea el input reconstruido, no solo el eliminado.');
    context.input = visible;
    vm.runInContext('guardarCampo(input)', context);
    calls[1].resolve({rutina_ejercicio: {...record, series: 5}});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(Number(record.series), 5);
});

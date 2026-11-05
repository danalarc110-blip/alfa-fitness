const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const assert = require('node:assert/strict');
const {spawn, spawnSync} = require('node:child_process');
const puppeteer = require('puppeteer-core');
const {acceptPrivacyPuppeteer} = require('./privacy_qa.cjs');
const root = path.resolve(__dirname, '..');
const before = process.argv.includes('--before');
const mode = before ? 'before' : 'after';
const out = path.join(root, 'outputs/revision-2026-11-04/tercera-revision');
fs.mkdirSync(out, {recursive: true});
const cfg = JSON.parse(fs.readFileSync(path.join(root, 'storage/app/private/review-qa.json'), 'utf8'));
assert.equal(fs.realpathSync.native(path.dirname(cfg.database)).toLowerCase(), fs.realpathSync.native(os.tmpdir()).toLowerCase());
assert.ok(path.basename(cfg.database).startsWith('alpha-review-'));
const url = 'http://127.0.0.1:8061';
const php = 'C:/xampp/php/php.exe';
const cache = path.join(os.tmpdir(), 'alpha-minor-config-' + process.pid + '-' + Date.now() + '.php');
assert.equal(fs.existsSync(cache), false);
const env = {...process.env, APP_CONFIG_CACHE: cache, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: url, DB_CONNECTION: 'sqlite', DB_DATABASE: cfg.database, DB_URL: '', SESSION_DRIVER: 'file', SESSION_COOKIE: 'alpha_minor_review', CACHE_STORE: 'array', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync'};
const seeded = spawnSync(php, [], {cwd: root, env, encoding: 'utf8', windowsHide: true, input: String.raw`<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== getenv('DB_DATABASE') || config('database.connections.sqlite.url')) throw new RuntimeException('Disposable database required.');
foreach (range('a', 'o') as $letter) {
    App\Models\Cliente::firstOrCreate(['correo'=>'revision-'.$letter.'@example.test'], ['nombre'=>'Revisión fila '.$letter, 'activo'=>true]);
}
$cliente = App\Models\Cliente::where('correo', 'cliente@example.test')->firstOrFail();
$retirado = App\Models\Ejercicio::firstOrCreate(['nombre'=>'Press histórico retirado'], ['grupo_muscular'=>'Pecho','activo'=>false]);
App\Models\PersonalRecord::firstOrCreate(['cliente_id'=>$cliente->id, 'ejercicio_id'=>$retirado->id], ['peso_kg'=>42,'repeticiones'=>5]);
echo json_encode(['cliente'=>$cliente->id, 'retirado'=>$retirado->id]);
`});
assert.equal(seeded.status, 0, seeded.stderr);
const fixture = JSON.parse(seeded.stdout);
let server, browser;
const errors = [], checks = [];
(async () => {
    const log = fs.openSync(path.join(out, 'server-' + mode + '.log'), 'a');
    server = spawn(php, ['-d', 'max_execution_time=180', '-S', '127.0.0.1:8061', '-t', 'public', 'tools/qa_router.php'], {cwd: root, env, windowsHide: true, stdio: ['ignore', log, log]});
    fs.closeSync(log);
    browser = await puppeteer.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, protocolTimeout: 120000});
    const page = await browser.newPage();
    page.setDefaultNavigationTimeout(180000);
    page.on('pageerror', error => errors.push(error.message));
    const visit = async route => {
        const response = await page.goto(url + route, {waitUntil: 'domcontentloaded'});
        assert.equal(response.status(), 200, route);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow ' + route);
        console.log('Checked ' + route);
    };
    await page.setViewport({width: 1440, height: 1000});
    const login = async (email, client = false) => {
        await visit('/login');
        await acceptPrivacyPuppeteer(page);
        if (client) await page.click('#tab-btn-clientes');
        const form = client ? '#form-cliente-login' : '#form-usuario';
        await page.type(form + (client ? ' [name=correo]' : ' [name=email]'), email);
        await page.type(form + ' [name=password]', cfg.password);
        await Promise.all([page.waitForNavigation({waitUntil: 'domcontentloaded'}), page.click(form + ' [type=submit]')]);
        assert.ok(new URL(page.url()).pathname.endsWith('/dashboard'), 'Login must reach the intended dashboard');
    };
    const post = async (route, data) => page.evaluate(async (route, data) => {
        const response = await fetch(route, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json'}, body: new URLSearchParams(data)});
        return {status: response.status, body: await response.text()};
    }, route, data);
    await login('administrador@example.test');
    await visit('/cuentas?q=Revisi%C3%B3n');
    const selector = 'nav[role=navigation]';
    const navigation = await page.$(selector);
    assert.ok(navigation);
    const label = await navigation.evaluate(node => node.getAttribute('aria-label'));
    assert.equal(label, before ? 'Pagination Navigation' : 'Navegación de páginas');
    await navigation.screenshot({path: path.join(out, 'pagination-' + mode + '-1440.png')});
    const next = await navigation.evaluate(node => [...node.querySelectorAll('a[rel=next]')].find(link => link.getClientRects().length).href);
    assert.equal(new URL(next).searchParams.get('q'), 'Revisión');
    await visit(new URL(next).pathname + new URL(next).search);
    assert.ok((await page.$eval('main', node => node.textContent)).includes('Página 2 de 2'));
    const links = await page.$$eval(selector + ' a[rel=prev]', nodes => nodes.map(node => node.href));
    assert.ok(links.every(link => new URL(link).searchParams.get('q') === 'Revisión'));
    checks.push('Pagination links preserve search on next and previous pages');
    if (!before) {
        const text = await page.$eval(selector, node => node.textContent);
        assert.ok(text.includes('Mostrando') && text.includes('resultados'));
        await page.setViewport({width: 390, height: 844, isMobile: true, hasTouch: true});
        await visit('/cuentas?q=Revisi%C3%B3n&page=2');
        await (await page.$(selector)).screenshot({path: path.join(out, 'pagination-after-390.png')});
        checks.push('Visible pagination and accessible page names are Spanish on desktop/mobile without overflow');
        await page.setViewport({width: 1440, height: 1000});
        await visit('/ventas?q=0');
        assert.ok((await page.$eval('main', node => node.textContent)).includes('Limpiar'));
        assert.equal(await page.$eval('#venta-historial', node => node.classList.contains('hidden')), false);
        checks.push('Sales search for zero opens history and offers clearing the filter');
        const invalidPrice = await post('/productos', {nombre: 'Precio inválido de revisión', precio: '1.005', stock: 1});
        assert.equal(invalidPrice.status, 422);
        assert.ok(invalidPrice.body.includes('admite 0-2 decimales'));
        const invalidUuid = await post('/asistencia', {cliente_id: fixture.cliente, entrada_uuid: 'incorrecto'});
        assert.equal(invalidUuid.status, 422);
        assert.ok(invalidUuid.body.includes('El identificador de entrada no es'));
        checks.push('Price and entry identifier validation explains errors in Spanish');
        await post('/logout', {});
        await login('cliente@example.test', true);
        for (const width of [1440, 390]) {
            await page.setViewport({width, height: width === 390 ? 844 : 1000, isMobile: width === 390, hasTouch: width === 390});
            await visit('/progreso?ejercicio_id=' + fixture.retirado);
            assert.equal(await page.$eval('#filtrar-ejercicio-propio', node => node.selectedOptions[0].textContent.trim()), 'Press histórico retirado (inactivo)');
            assert.equal(await page.$$eval('form[method=POST][action$="/progreso"] select[name=ejercicio_id] option', nodes => nodes.some(node => node.textContent.includes('Press histórico retirado'))), false);
            await page.screenshot({path: path.join(out, 'progreso-cliente-' + width + '.png'), fullPage: true});
        }
        const rejectedRecord = await post('/progreso', {ejercicio_id: fixture.retirado, peso_kg: 43, repeticiones: 5});
        assert.equal(rejectedRecord.status, 422);
        await post('/logout', {});
        await login('entrenador@example.test');
        for (const width of [1440, 390]) {
            await page.setViewport({width, height: width === 390 ? 844 : 1000, isMobile: width === 390, hasTouch: width === 390});
            await visit('/entrenador/clientes/' + fixture.cliente + '/progreso?ejercicio_id=' + fixture.retirado);
            assert.equal(await page.$eval('#filtrar-ejercicio', node => node.selectedOptions[0].textContent.trim()), 'Press histórico retirado (inactivo)');
            await page.screenshot({path: path.join(out, 'progreso-entrenador-' + width + '.png'), fullPage: true});
        }
        checks.push('Client and assigned trainer consult retired exercise history on desktop/mobile; new records on retired exercises remain rejected');
    }
    assert.deepEqual(errors, []);
    fs.writeFileSync(path.join(out, 'browser-' + mode + '.json'), JSON.stringify({date: new Date().toISOString(), checks, errors}, null, 2));
    console.log('PASS ' + mode + ': ' + checks.join('; '));
})().catch(error => {console.error(error); process.exitCode = 1;}).finally(async () => {
    if (server) server.kill();
    if (browser) {
        const instance = browser;
        await Promise.race([instance.close(), new Promise(resolve => setTimeout(() => {instance.process()?.kill(); resolve();}, 10000))]);
    }
});

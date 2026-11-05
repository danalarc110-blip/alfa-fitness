const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const crypto = require('node:crypto');
const assert = require('node:assert/strict');
const {spawn, spawnSync} = require('node:child_process');
const puppeteer = require('puppeteer-core');
const {acceptPrivacyPuppeteer} = require('./privacy_qa.cjs');
const root = path.resolve(__dirname, '..');
const out = path.join(root, 'outputs/revision-2026-11-04/tercera-revision/entrenamientos');
fs.mkdirSync(out, {recursive: true});
const cfg = JSON.parse(fs.readFileSync(path.join(root, 'storage/app/private/review-qa.json'), 'utf8'));
assert.equal(fs.realpathSync.native(path.dirname(cfg.database)).toLowerCase(), fs.realpathSync.native(os.tmpdir()).toLowerCase());
assert.ok(path.basename(cfg.database).startsWith('alpha-review-'));
const database = path.join(os.tmpdir(), 'alpha-training-' + crypto.randomUUID() + '.sqlite');
const cache = path.join(os.tmpdir(), 'alpha-training-config-' + crypto.randomUUID() + '.php');
assert.equal(fs.existsSync(database), false);
assert.equal(fs.existsSync(cache), false);
const php = 'C:/xampp/php/php.exe';
const url = 'http://127.0.0.1:8081';
const env = {...process.env, APP_CONFIG_CACHE: cache, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: url,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '', QA_SOURCE_DB: cfg.database, QA_TRAINING_PASSWORD: cfg.password,
    SESSION_DRIVER: 'file', SESSION_COOKIE: 'alpha_training_review', CACHE_STORE: 'array', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync'};
let server, browser;
const errors = [], checks = [];
(async () => {
    const seeded = spawnSync(php, [], {cwd: root, env, encoding: 'utf8', windowsHide: true, input: String.raw`<?php
require 'vendor/autoload.php';
$source = getenv('QA_SOURCE_DB');
$target = getenv('DB_DATABASE');
if (realpath(dirname($source)) !== realpath(sys_get_temp_dir()) || !str_starts_with(basename($source), 'alpha-review-')
    || realpath(dirname($target)) !== realpath(sys_get_temp_dir()) || !str_starts_with(basename($target), 'alpha-training-') || file_exists($target)) {
    throw new RuntimeException('Only disposable fixtures are permitted.');
}
$snapshot = new PDO('sqlite:'.$source);
$snapshot->exec('VACUUM INTO '.$snapshot->quote($target));
$snapshot = null;
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $target || config('database.connections.sqlite.url')) {
    throw new RuntimeException('Disposable database required.');
}
$exercise = App\Models\Ejercicio::firstOrCreate(['nombre'=>'Verificación descanso cero'], ['grupo_muscular'=>'Piernas', 'activo'=>true]);
$ids = [];
foreach (['entrenador', 'cliente'] as $role) {
    $user = $role === 'cliente' ? App\Models\Cliente::where('correo','cliente@example.test')->firstOrFail()
        : App\Models\User::where('email','entrenador@example.test')->firstOrFail();
    $user->forceFill(['password'=>getenv('QA_TRAINING_PASSWORD'), 'activo'=>true])->save();
    if ($role !== 'cliente') $user->forceFill(['password_establecida'=>true])->save();
    $routine = App\Models\Rutina::create(['user_type'=>$role === 'cliente' ? 'cliente' : 'web', 'user_id'=>$user->id,
        'nombre'=>'Sesión de verificación '.$role, 'objetivo'=>'Fuerza', 'nivel'=>'Intermedio', 'dias_por_semana'=>1]);
    $day = $routine->dias()->create(['orden'=>1, 'titulo'=>'Entrenamiento sin descanso']);
    $day->ejercicios()->create(['ejercicio_id'=>$exercise->id, 'orden'=>1, 'series'=>3, 'repeticiones'=>'10', 'descanso_segundos'=>0]);
    $ids[$role] = $routine->id;
}
echo json_encode($ids, JSON_THROW_ON_ERROR);
`});
    assert.equal(seeded.status, 0, seeded.stderr);
    const ids = JSON.parse(seeded.stdout);
    const log = fs.openSync(path.join(out, 'server.log'), 'a');
    server = spawn(php, ['-d', 'max_execution_time=180', '-S', '127.0.0.1:8081', '-t', 'public', 'tools/qa_router.php'], {cwd: root, env, windowsHide: true, stdio: ['ignore', log, log]});
    fs.closeSync(log);
    browser = await puppeteer.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, protocolTimeout: 120000});
    const page = await browser.newPage();
    page.setDefaultNavigationTimeout(120000);
    page.on('pageerror', error => errors.push(error.message));
    const visit = async route => {
        const response = await page.goto(url + route, {waitUntil: 'domcontentloaded'});
        assert.equal(response.status(), 200, route);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow: ' + route);
    };
    for (const role of ['entrenador', 'cliente']) {
        await page.setViewport({width: 1440, height: 1000});
        await visit('/login');
        await acceptPrivacyPuppeteer(page);
        if (role === 'cliente') await page.click('#tab-btn-clientes');
        const form = role === 'cliente' ? '#form-cliente-login' : '#form-usuario';
        await page.type(form + (role === 'cliente' ? ' [name=correo]' : ' [name=email]'), role + '@example.test');
        await page.type(form + ' [name=password]', cfg.password);
        await Promise.all([page.waitForNavigation({waitUntil: 'domcontentloaded'}), page.click(form + ' [type=submit]')]);
        const dashboard = role === 'cliente' ? '/cliente/dashboard' : '/dashboard';
        await visit(dashboard);
        assert.equal(new URL(page.url()).pathname, dashboard, 'The isolated account must authenticate before testing training.');
        for (const width of [1440, 390]) {
            await page.setViewport({width, height: width === 390 ? 844 : 1000, isMobile: width === 390, hasTouch: width === 390});
            await visit('/entrenamientos/' + ids[role] + '/entrenar');
            assert.equal(await page.$eval('.serie-btn', node => node.getAttribute('onclick')), 'toggleSerie(this, 0)');
            assert.equal(await page.$eval('#modal-fin-entrenamiento', node => node.textContent.includes('Registrar PRs')), role === 'cliente');
            await page.click('.serie-btn');
            assert.deepEqual(await page.evaluate(() => ({seconds: segundosRestantes, running: timerInterval !== null})), {seconds: 0, running: false});
            assert.equal(await page.$eval('.serie-btn', node => node.getAttribute('aria-pressed')), 'true');
            await page.screenshot({path: path.join(out, role + '-entrenar-' + width + '.png'), fullPage: true});
            if (width === 390) {
                await page.evaluate(() => { sonidoHabilitado = false; ajustarTimer(2); iniciarTimer(); });
                await page.waitForFunction(() => document.getElementById('timer-estado').textContent === '¡A entrenar!', {timeout: 5000});
                assert.equal(await page.evaluate(() => timerInterval === null), true);
                await page.click('#btn-finalizar-entrenamiento');
                await page.waitForFunction(() => document.getElementById('resumen-fin-entrenamiento').textContent.includes('1 de 3'));
                await page.screenshot({path: path.join(out, role + '-resumen-390.png'), fullPage: true});
            }
        }
        checks.push(role + ': 1440/390 without overflow; zero rest stays zero; two-second timer completes; summary respects permissions and saved series');
        const loggedOut = await page.evaluate(async () => {
            const token = document.querySelector('meta[name=csrf-token]').content;
            const response = await fetch('/logout', {method: 'POST', headers: {'X-CSRF-TOKEN': token}});
            return response.ok;
        });
        assert.equal(loggedOut, true, 'The isolated role must log out before switching accounts.');
    }
    assert.deepEqual(errors, []);
    fs.writeFileSync(path.join(out, 'browser.json'), JSON.stringify({date: new Date().toISOString(), checks, errors}, null, 2));
    console.log('PASS training browser: ' + checks.join('; '));
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(async () => {
    if (browser) {
        await Promise.race([browser.close().catch(() => {}), new Promise(resolve => {
            const timeout = setTimeout(resolve, 10000);
            timeout.unref();
        })]);
        if (browser.process() && !browser.process().killed) browser.process().kill();
    }
    if (server) {
        server.kill();
        await new Promise(resolve => { server.once('exit', resolve); setTimeout(resolve, 1000); });
    }
    if (fs.existsSync(database)) fs.unlinkSync(database);
    if (fs.existsSync(cache)) fs.unlinkSync(cache);
});

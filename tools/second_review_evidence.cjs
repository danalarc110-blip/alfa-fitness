// Focused visual and interaction evidence, always using the disposable review database.
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const assert = require('node:assert/strict');
const {spawn, spawnSync} = require('node:child_process');
const puppeteer = require('puppeteer-core');
const {acceptPrivacyPuppeteer} = require('./privacy_qa.cjs');
const root = path.resolve(__dirname, '..');
const cfg = JSON.parse(fs.readFileSync(path.join(root, 'storage/app/private/review-qa.json'), 'utf8'));
assert.equal(fs.realpathSync.native(path.dirname(cfg.database)).toLowerCase(), fs.realpathSync.native(os.tmpdir()).toLowerCase());
assert.ok(path.basename(cfg.database).startsWith('alpha-review-'));
assert.equal(cfg.url, 'http://127.0.0.1:8041');
const out = path.join(root, 'outputs/revision-2026-11-04/segunda-revision/capturas');
fs.mkdirSync(out, {recursive: true});
const php = 'C:/xampp/php/php.exe';
const qaConfigCache = path.join(os.tmpdir(), 'alpha-evidence-config-' + process.pid + '-' + Date.now() + '.php');
assert.equal(fs.existsSync(qaConfigCache), false);
const env = {...process.env, APP_CONFIG_CACHE: qaConfigCache, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: cfg.url, DB_CONNECTION: 'sqlite', DB_DATABASE: cfg.database, DB_URL: '', SESSION_DRIVER: 'file', SESSION_COOKIE: 'alpha_second_evidence', CACHE_STORE: 'array', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync'};
let browser, server;
const checks = [], errors = [];
(async () => {
    const seed = spawnSync(php, [], {cwd: root, env, encoding: 'utf8', windowsHide: true, input: String.raw`<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== getenv('DB_DATABASE') || config('database.connections.sqlite.url')) {
    throw new RuntimeException('The evidence runner requires its disposable SQLite database.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$cliente = App\Models\Cliente::where('correo', 'cliente@example.test')->firstOrFail();
App\Models\Asistencia::firstOrCreate(['cliente_id' => $cliente->id, 'fecha_hora' => today()->subDay()->setTime(8, 0)], ['tipo_acceso' => 'entrada']);
echo 'Disposable attendance evidence ready.';
`});
    assert.equal(seed.status, 0, seed.stderr);
    const log = fs.openSync(path.join(out, 'server.log'), 'a');
    server = spawn(php, ['-d', 'extension=zip', '-S', '127.0.0.1:8041', '-t', 'public', 'tools/qa_router.php'], {cwd: root, env, windowsHide: true, stdio: ['ignore', log, log]});
    fs.closeSync(log);
    browser = await puppeteer.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, protocolTimeout: 30000});
    const page = await browser.newPage();
    page.setDefaultNavigationTimeout(60000);
    page.on('pageerror', error => errors.push(error.message));
    const visit = async route => {
        const response = await page.goto(cfg.url + route, {waitUntil: 'domcontentloaded'});
        assert.equal(response.status(), 200, route);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow ' + route);
    };
    const clickNavigate = async selector => Promise.all([page.waitForNavigation({waitUntil: 'domcontentloaded'}), page.click(selector)]);
    const login = async client => {
        await visit('/login');
        await acceptPrivacyPuppeteer(page);
        if (client) await page.click('#tab-btn-clientes');
        const form = client ? '#form-cliente-login' : '#form-usuario';
        await page.type(form + (client ? ' [name=correo]' : ' [name=email]'), client ? 'cliente@example.test' : 'administrador@example.test');
        await page.type(form + ' [name=password]', cfg.password);
        await clickNavigate(form + ' [type=submit]');
    };
    await page.setViewport({width: 390, height: 844, isMobile: true, hasTouch: true});
    await page.emulateMediaFeatures([{name: 'prefers-reduced-motion', value: 'reduce'}]);
    await visit('/olvide-mi-contrasena');
    assert.ok((await page.$eval('main', node => node.textContent)).includes('El envío de correo todavía no está habilitado.'));
    assert.equal(await page.$eval('button[type=submit]', node => node.matches(':disabled')), true);
    await page.screenshot({path: path.join(out, 'recuperacion-390.png'), fullPage: true});
    checks.push('Recovery screen explains unavailable delivery without promising an email');
    await login(false);
    await visit('/dashboard?preset=mes_anterior');
    const chart = await page.$('.alpha-business-chart');
    assert.ok(await chart.evaluate(node => node.scrollWidth > node.clientWidth));
    await chart.focus();
    await page.keyboard.press('ArrowRight');
    await page.waitForFunction(() => document.querySelector('.alpha-business-chart').scrollLeft > 0);
    await chart.evaluate(node => {node.scrollLeft = 0; node.scrollIntoView({block: 'center'});});
    const bounds = await chart.boundingBox();
    const cdp = await page.createCDPSession();
    await cdp.send('Input.synthesizeScrollGesture', {x: bounds.x + bounds.width * .8, y: bounds.y + 60, xDistance: -160, yDistance: 0, speed: 600, gestureSourceType: 'touch'});
    const touchResult = await chart.evaluate(node => ({left: node.scrollLeft, width: node.clientWidth, scrollWidth: node.scrollWidth, box: node.getBoundingClientRect().toJSON(), maxTouchPoints: navigator.maxTouchPoints}));
    if (touchResult.left <= 20) {
        const rawSwipe = async (x, y) => {
            await cdp.send('Input.dispatchTouchEvent', {type: 'touchStart', touchPoints: [{x, y, id: 0}]});
            for (let step = 1; step <= 8; step++) {
                await cdp.send('Input.dispatchTouchEvent', {type: 'touchMove', touchPoints: [{x: x - step * 20, y, id: 0}]});
                await page.evaluate(() => new Promise(resolve => {requestAnimationFrame(resolve); setTimeout(resolve, 50);}));
            }
            await cdp.send('Input.dispatchTouchEvent', {type: 'touchEnd', touchPoints: []});
        };
        const box = touchResult.box;
        await rawSwipe(box.x + box.width * .8, box.y + 60);
        const rawLeft = await chart.evaluate(node => node.scrollLeft);
        console.log('Touch diagnostics: ' + JSON.stringify({...touchResult, rawLeft}));
        assert.ok(rawLeft > 20, 'Touch gesture did not move the financial chart');
    }
    await (await page.$('#resumen-negocio')).screenshot({path: path.join(out, 'grafica-mes-390.png')});
    await page.$eval('.alpha-business-detail summary', node => node.click());
    assert.equal(await page.$$eval('.alpha-business-detail tbody tr', nodes => nodes.length), 31);
    const earningDay = await page.$$eval('[data-finance-day]', nodes => nodes.findIndex(node => [...node.querySelectorAll('.alpha-business-bar-track>span')].some(bar => parseFloat(bar.style.height) > 0)));
    assert.ok(earningDay >= 0);
    await clickNavigate(`[data-finance-day]:nth-child(${earningDay + 1})`);
    assert.equal(await page.$$eval('[data-finance-day]', nodes => nodes.length), 1);
    checks.push('31-day mobile chart scrolls with keyboard and a synthesized touch gesture; daily table and bar navigation work');
    await page.evaluate(async () => {
        const token = document.querySelector('meta[name=csrf-token]').content;
        await fetch('/logout', {method: 'POST', headers: {'X-CSRF-TOKEN': token}});
    });
    await login(true);
    await page.setViewport({width: 1440, height: 1000});
    await visit('/mi-asistencia');
    const attendance = await page.$eval('main', node => node.textContent);
    assert.ok(attendance.includes('Salida sin registrar') && attendance.includes('Fuera del club'));
    assert.ok(!attendance.includes('Entrenando actualmente'));
    await page.screenshot({path: path.join(out, 'asistencia-cliente-1440.png'), fullPage: true});
    await page.setViewport({width: 390, height: 844, isMobile: true, hasTouch: true});
    await visit('/mi-asistencia');
    await page.screenshot({path: path.join(out, 'asistencia-cliente-390.png'), fullPage: true});
    checks.push('Old unclosed attendance is not shown as current occupancy in desktop/mobile client screens');
    assert.deepEqual(errors, []);
    fs.writeFileSync(path.join(out, 'results.json'), JSON.stringify({checks, errors, browser: 'Chrome headless', touch: 'CDP synthesized touch, not physical hardware', disposableData: true}, null, 2));
    console.log('PASS ' + checks.join('; '));
})().catch(error => {console.error(error); process.exitCode = 1;}).finally(async () => {
    if (server) server.kill();
    if (browser) await browser.close();
});

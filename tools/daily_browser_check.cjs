// Real browser checks against disposable fixtures from prepare_daily_qa.php.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const {spawn} = require('node:child_process');
const puppeteer = require('puppeteer-core');
const root = path.resolve(__dirname, '..');
const cfg = JSON.parse(fs.readFileSync(path.join(root, 'storage/app/private/modules-qa.json'), 'utf8'));
assert.equal(fs.realpathSync.native(path.dirname(cfg.database)).toLowerCase(), fs.realpathSync.native(os.tmpdir()).toLowerCase());
assert.ok(path.basename(cfg.database).startsWith('alpha-modules-qa-'));
const origin = 'http://127.0.0.1:8037';
const out = path.join(root, 'storage/app/private/revision-diaria');
fs.mkdirSync(out, {recursive: true});
const env = {...process.env, APP_ENV: 'local', APP_URL: origin, APP_DEBUG: 'false', DB_CONNECTION: 'sqlite', DB_DATABASE: cfg.database, DB_URL: '', SESSION_DRIVER: 'file', SESSION_COOKIE: 'alpha_daily_qa', CACHE_STORE: 'array', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync'};
let server, browser;
const errors = [];

(async () => {
    await new Promise((resolve, reject) => {
        const probe = net.createServer();
        probe.once('error', reject);
        probe.listen(8037, '127.0.0.1', () => probe.close(resolve));
    });
    const log = fs.openSync(path.join(out, 'browser-server.log'), 'a');
    server = spawn(process.env.ALPHA_QA_PHP || 'C:/xampp/php/php.exe', ['-S', '127.0.0.1:8037', '-t', 'public', 'tools/qa_router.php'], {cwd: root, env, windowsHide: true, stdio: ['ignore', log, log]});
    fs.closeSync(log);
    browser = await puppeteer.launch({executablePath: process.env.ALPHA_QA_BROWSER || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    const page = await browser.newPage();
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewport({width: 1440, height: 1000});
    await page.goto(origin + '/login', {waitUntil: 'networkidle0'});
    await page.type('#form-usuario [name=email]', 'administrador@example.test');
    await page.type('#form-usuario [name=password]', cfg.password);
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('#form-usuario [type=submit]')]);
    for (const width of [1440, 390]) {
        await page.setViewport({width, height: 1000});
        const response = await page.goto(origin + '/analitica?preset=hoy', {waitUntil: 'networkidle0'});
        assert.equal(response.status(), 200);
        const body = await page.$eval('body', node => node.innerText);
        assert.ok(body.includes('Revisión necesaria'));
        assert.ok(body.includes('Ventas con diferencias: 1.'));
        assert.ok(body.includes('$35.00'));
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Report page overflows at ' + width);
        await page.evaluate(() => document.fonts.ready);
        await page.screenshot({path: path.join(out, 'reportes-' + width + '.png'), fullPage: false});
        const control = await page.$('section[aria-labelledby=conciliacion-ventas]');
        await control.screenshot({path: path.join(out, 'conciliacion-' + width + '.png')});
        await page.$eval('details', node => { node.open = true; });
        assert.ok((await page.$eval('details', node => node.innerText)).includes('Total del período'));
        console.log('PASS financial report and reconciliation at ' + width + 'px');
    }
    // Exercise real autosave, including endpoints whose JSON response differs.
    await page.goto(origin + '/entrenamientos', {waitUntil: 'networkidle0'});
    const routineUrl = await page.evaluate(async () => {
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const response = await fetch('/entrenamientos/crear', {method: 'POST', headers: {'X-CSRF-TOKEN': csrf}});
        if (!response.ok) throw new Error('Routine creation failed: ' + response.status);
        return response.url;
    });
    assert.match(routineUrl, /\/entrenamientos\/\d+$/);
    await page.goto(routineUrl, {waitUntil: 'networkidle0'});
    await page.waitForSelector('#campo-nombre');
    await page.$eval('#campo-nombre', node => { node.value = 'Rutina diaria verificada'; node.dispatchEvent(new Event('input', {bubbles: true})); });
    await page.waitForFunction(() => document.getElementById('estado-guardado').textContent === 'Guardado' && !document.getElementById('estado-guardado').classList.contains('opacity-0'));
    await page.reload({waitUntil: 'networkidle0'});
    assert.equal(await page.$eval('#campo-nombre', node => node.value), 'Rutina diaria verificada');
    const previous = await page.$$eval('[data-tab]', nodes => nodes.length);
    await page.click('#btn-add-dia');
    await page.waitForFunction(count => document.querySelectorAll('[data-tab]').length === count + 1, {}, previous);
    await page.waitForSelector('[data-add]');
    await page.click('[data-add]');
    await page.waitForSelector('.campo-ej');
    await page.$eval('.campo-ej[data-campo=series]', node => { node.value = '4'; node.dispatchEvent(new Event('change', {bubbles: true})); });
    await page.waitForFunction(() => document.querySelector('.campo-ej[data-campo=series]').disabled === false);
    await page.reload({waitUntil: 'networkidle0'});
    await page.$$eval('[data-tab]', nodes => nodes.at(-1).click());
    assert.equal(await page.$eval('.campo-ej[data-campo=series]', node => node.value), '4');
    assert.deepEqual(errors, []);
    console.log('PASS real routine autosave, day creation, catalog and exercise update; no JavaScript errors');
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(async () => {
    if (server) server.kill();
    if (browser) await browser.close();
});

const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const puppeteer = require('puppeteer-core');
const {acceptPrivacyPuppeteer} = require('./privacy_qa.cjs');
const root = path.resolve(__dirname, '..');
const cfg = JSON.parse(fs.readFileSync(path.join(root, 'storage/app/private/review-qa.json'), 'utf8'));
assert.equal(fs.realpathSync.native(path.dirname(cfg.database)).toLowerCase(), fs.realpathSync.native(os.tmpdir()).toLowerCase());
assert.ok(path.basename(cfg.database).startsWith('alpha-review-'));
const stage = process.argv.includes('--after') ? 'despues' : 'antes';
const out = path.join(root, 'outputs/revision-2026-11-04', stage);
fs.mkdirSync(out, {recursive: true});
const env = {...process.env, APP_ENV: 'local', APP_URL: cfg.url, APP_DEBUG: 'false', DB_CONNECTION: 'sqlite', DB_DATABASE: cfg.database, DB_URL: '', SESSION_DRIVER: 'file', SESSION_COOKIE: 'alpha_review_qa', CACHE_STORE: 'array', MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync'};
let browser, server;
const errors = [], checks = [];
(async () => {
    const log = fs.openSync(path.join(root, 'storage/logs/review-qa-server.log'), 'a');
    server = spawn('C:/xampp/php/php.exe', ['-S', '127.0.0.1:8041', '-t', 'public', 'tools/qa_router.php'], {cwd: root, env, windowsHide: true, stdio: ['ignore', log, log]});
    fs.closeSync(log);
    browser = await puppeteer.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    for (const role of ['Administrador', 'Secretaria', 'Entrenador', 'Cliente']) {
        const context = await browser.createBrowserContext();
        const page = await context.newPage();
        page.setDefaultNavigationTimeout(60000);
        page.on('pageerror', error => errors.push(role + ': ' + error.message));
        await page.setViewport({width: 1440, height: 1000});
        await page.emulateMediaFeatures([{name: 'prefers-reduced-motion', value: 'reduce'}]);
        const visit = async (route, status = 200) => {
            const response = await page.goto(cfg.url + route, {waitUntil: 'networkidle0'});
            assert.equal(response.status(), status, role + ' ' + route);
            if (status === 200) assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow ' + role + ' ' + route);
        };
        const clickNavigate = async selector => Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click(selector)]);
        await visit('/login');
        await acceptPrivacyPuppeteer(page);
        const client = role === 'Cliente';
        if (client) await page.click('#tab-btn-clientes');
        const form = client ? '#form-cliente-login' : '#form-usuario';
        await page.type(form + (client ? ' [name=correo]' : ' [name=email]'), role.toLowerCase() + '@example.test');
        await page.type(form + ' [name=password]', cfg.password);
        await clickNavigate(form + ' [type=submit]');
        const home = client ? '/cliente/dashboard' : '/dashboard';
        assert.ok(page.url().endsWith(home));
        for (const width of [1440, 390]) {
            await page.setViewport({width, height: width === 390 ? 844 : 1000});
            await visit(home);
            if (stage === 'despues') {
                assert.equal(await page.$$eval('#resumen-negocio', nodes => nodes.length), role === 'Administrador' ? 1 : 0);
                if (role === 'Administrador' && width === 1440) await (await page.$('#resumen-negocio')).screenshot({path: path.join(out, 'administrador-panel-1440.png')});
            }
            await page.screenshot({path: path.join(out, role.toLowerCase() + '-inicio-' + width + '.png'), fullPage: true});
        }
        if (role === 'Administrador' && stage === 'despues') {
            await page.setViewport({width: 1440, height: 1000});
            await visit('/dashboard?preset=hoy');
            assert.equal(await page.$$eval('[data-finance-day]', nodes => nodes.length), 1);
            await visit('/dashboard?preset=mes&mes=' + cfg.mes);
            assert.equal(await page.$eval('[name=mes]', node => node.value), cfg.mes);
            await page.$eval('#negocio-desde', (node, value) => {node.value = value;}, cfg.today);
            await page.$eval('#negocio-hasta', (node, value) => {node.value = value;}, cfg.today);
            await clickNavigate('#negocio-rango button[type=submit]');
            assert.equal(await page.$$eval('[data-finance-day]', nodes => nodes.length), 1);
            await page.screenshot({path: path.join(out, 'administrador-filtro-dia.png'), fullPage: true});
            await visit('/dashboard?preset=personalizado&desde=2026-10-10&hasta=2026-10-01');
            assert.ok(await page.$('[data-validation-errors]'));
            await visit('/dashboard?preset=mes_anterior');
            await page.screenshot({path: path.join(out, 'administrador-mes-anterior.png'), fullPage: true});
            await (await page.$('#resumen-negocio')).screenshot({path: path.join(out, 'administrador-mes-anterior-panel.png')});
            await page.evaluate(() => window.AlphaAppearance.apply({...window.AlphaAppearance.current, mode: 'dark', design: 'green'}));
            await page.screenshot({path: path.join(out, 'administrador-verde-oscuro.png'), fullPage: true});
            checks.push('Admin financial filters: today, month, one day, reversed dates, comparison, green/dark');
        }
        if (role === 'Secretaria' || role === 'Administrador') {
            await page.setViewport({width: 1440, height: 1000});
            await visit('/ventas?vista=historial');
            if (stage === 'despues') assert.equal(await page.$$eval('[data-sales-financial-summary]', nodes => nodes.length), role === 'Administrador' ? 1 : 0);
            await page.screenshot({path: path.join(out, role.toLowerCase() + '-ventas-1440.png'), fullPage: true});
        }
        if (stage === 'despues' && role !== 'Administrador') await visit('/analitica', 403);
        checks.push(role + ': desktop/mobile layout, home and financial visibility');
        await context.close();
    }
    assert.deepEqual(errors, []);
    fs.writeFileSync(path.join(out, 'results.json'), JSON.stringify({stage, checks, errors, data: 'Disposable QA fixtures, no production records'}, null, 2));
    console.log('PASS ' + stage + ': ' + checks.join('; '));
})().catch(error => {console.error(error); process.exitCode = 1;}).finally(async () => {
    if (server) server.kill();
    if (browser) await browser.close();
});

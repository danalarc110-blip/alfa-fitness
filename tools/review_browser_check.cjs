// Run against the disposable database prepared by prepare_browser_qa.php.
const puppeteer = require('puppeteer-core');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const cfg = JSON.parse(fs.readFileSync('storage/app/private/design-browser-qa.json', 'utf8'));
assert.equal(cfg.url, 'http://127.0.0.1:8021');
const out = 'outputs/revision-2026-10-28';
fs.mkdirSync(out, {recursive: true});
(async () => {
    const browser = await puppeteer.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    const errors = [], results = [];
    const visit = async (page, route) => {
        const response = await page.goto(cfg.url + route, {waitUntil: 'networkidle0'});
        assert.equal(response.status(), 200, route);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow ' + route);
    };
    const submit = async (page, selector) => Promise.all([
        page.waitForNavigation({waitUntil: 'networkidle0'}), page.click(selector),
    ]);
    const accept = async page => {
        if (await page.$('[name=privacy_read]')) {
            await page.click('[name=privacy_read]');
            await submit(page, '.alpha-privacy-form button[type=submit]');
        }
    };
    try {
        const publicPage = await browser.newPage();
        publicPage.on('pageerror', e => errors.push(e.message));
        await publicPage.setViewport({width: 1440, height: 1000});
        await visit(publicPage, '/login');
        assert.equal(await publicPage.$('#form-usuario'), null);
        for (const width of [1440, 390]) {
            await publicPage.setViewport({width, height: width === 390 ? 844 : 1000});
            for (const mode of ['light', 'dark']) {
                await publicPage.evaluate(mode => window.AlphaAppearance.apply({...window.AlphaAppearance.current, mode}), mode);
                assert.equal(await publicPage.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
                await publicPage.screenshot({path: `${out}/privacidad-acceso-${width}-${mode}.png`, fullPage: true});
            }
        }
        await accept(publicPage);
        await publicPage.click('#tab-btn-clientes');
        await publicPage.click('#btnMostrarRegistro');
        assert.equal(await publicPage.$eval('#form-cliente-registro [name=nombre]', e => document.activeElement === e), true);
        await publicPage.screenshot({path: `${out}/registro-390.png`, fullPage: true});
        await visit(publicPage, '/privacidad');
        await publicPage.screenshot({path: `${out}/politica-390.png`, fullPage: true});
        results.push('Privacy gate desktop/mobile, light/dark, registration focus, public policy');
        const matrix = [
            ['Administrador', 'administrador@example.test', ['/dashboard','/cuentas','/entrenadores','/analitica','/membresias']],
            ['Secretaria', 'secretaria@example.test', ['/dashboard','/ventas','/asistencia','/membresias']],
            ['Entrenador', 'entrenador@example.test', ['/dashboard','/entrenador/clientes','/entrenamientos','/ejercicios']],
            ['Cliente', 'qa-client@example.test', ['/cliente/dashboard','/entrenamientos','/progreso','/membresias','/mi-asistencia']],
        ];
        for (const [role, email, routes] of matrix) {
            const context = await browser.createBrowserContext();
            const page = await context.newPage();
            page.on('pageerror', e => errors.push(`${role}: ${e.message}`));
            await page.setViewport({width:1440,height:1000});
            await visit(page, '/login');
            await accept(page);
            const client = role === 'Cliente';
            if (client) await page.click('#tab-btn-clientes');
            const form = client ? '#form-cliente-login' : '#form-usuario';
            await page.type(form + (client ? ' [name=correo]' : ' [name=email]'), email);
            await page.type(form + ' [name=password]', cfg.password);
            await submit(page, form + ' button[type=submit]');
            assert.match(page.url(), /dashboard$/);
            await page.screenshot({path:`${out}/${role}-1440.png`,fullPage:true});
            for (const route of routes) await visit(page, route);
            if (role === 'Secretaria') {
                await visit(page, '/ventas');
                await page.click('[data-anadir-producto]');
                assert.ok(await page.$('#items-contenedor .item-fila'));
            }
            await visit(page, '/configuracion');
            await page.click('#appearance-form [name=mode][value=dark]');
            await page.click('#appearance-form button[type=submit]');
            await page.waitForFunction(() => document.getElementById('appearance-message').textContent === 'Apariencia guardada en tu cuenta.');
            await page.reload({waitUntil:'networkidle0'});
            assert.equal(await page.$eval('html', e => e.dataset.theme), 'dark');
            await page.setViewport({width:390,height:844});
            await visit(page, routes[0]);
            await page.click('#alpha-hamburger-btn');
            assert.equal(await page.$eval('.alpha-workspace',e=>e.inert), true);
            await page.keyboard.press('Tab');
            assert.equal(await page.$eval('#alpha-mobile-drawer',e=>e.contains(document.activeElement)), true);
            await page.screenshot({path:`${out}/${role}-menu-390.png`,fullPage:true});
            await page.keyboard.press('Escape');
            assert.equal(await page.$eval('#alpha-hamburger-btn',e=>e===document.activeElement), true);
            assert.equal(await page.$eval('.alpha-workspace',e=>e.inert), false);
            await page.screenshot({path:`${out}/${role}-390.png`,fullPage:true});
            await page.emulateMediaFeatures([{name:'prefers-reduced-motion',value:'reduce'}]);
            await page.click('#alpha-hamburger-btn');
            await page.setViewport({width:1440,height:1000});
            await page.waitForFunction(()=>!document.querySelector('.alpha-workspace').inert);
            if (client) {
                await visit(page, '/entrenamientos');
                await submit(page, 'form[action$="/entrenamientos/crear"] button[type=submit]');
                const routine = new URL(page.url()).pathname;
                assert.match(routine, /^\/entrenamientos\/\d+$/);
                await visit(page, routine + '/entrenar');
                assert.equal(await page.$('#progreso-barra'), null);
                assert.ok(await page.$('a[href$="' + routine + '"]'));
                results.push('Empty routine training renders without JavaScript errors');
            }
            results.push(`${role}: routes, persisted appearance, mobile keyboard menu, reduced motion`);
            await context.close();
        }
        assert.deepEqual(errors, []);
        fs.writeFileSync(`${out}/browser-results.json`, JSON.stringify({results, errors}, null, 2));
        console.log(results.join('\n'));
        console.log('Browser checks passed; no JavaScript errors.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });

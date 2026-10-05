// Only run with the disposable database created by prepare_browser_qa.php.
const {chromium} = require(process.env.ALPHA_PLAYWRIGHT_MODULE || 'playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');

(async () => {
    const qa = JSON.parse(fs.readFileSync('storage/app/private/browser-qa.json', 'utf8'));
    assert.equal(qa.url, 'http://127.0.0.1:8011');
    const browser = await chromium.launch({channel: 'chrome', headless: true});
    const errors = [];
    try {
        const visit = async (page, path, status = 200) => {
            const response = await page.goto(qa.url + path, {waitUntil: 'domcontentloaded'});
            assert.equal(response.status(), status, path);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow: ' + path);
        };
        const login = async (page, role) => {
            await visit(page, '/login');
            await page.locator('#seccion-usuarios [name=email]').fill(role + '@example.test');
            await page.locator('#seccion-usuarios [name=password]').fill(qa.password);
            await Promise.all([page.waitForURL('**/dashboard'), page.locator('#seccion-usuarios button[type=submit]').click()]);
        };
        const guest = await browser.newContext({viewport: {width: 390, height: 844}});
        const page = await guest.newPage();
        page.on('pageerror', e => errors.push(e.message));
        for (const doc of ['privacidad', 'terminos', 'lesiones', 'derechos']) await visit(page, '/legal/' + doc);
        await page.screenshot({path: 'storage/app/private/qa-legal-mobile.png', fullPage: true});
        await visit(page, '/login');
        await page.locator('#tab-btn-clientes').click();
        await page.locator('#btnMostrarRegistro').click();
        const form = page.locator('#form-cliente-registro');
        await form.locator('[name=nombre]').fill('Nuevo cliente de auditoría');
        await form.locator('[name=correo]').fill('browser-audit-' + Date.now() + '@example.test');
        await form.locator('[name=password]').fill(qa.password);
        await form.locator('[name=password_confirmation]').fill(qa.password);
        assert.equal(await form.locator('[name=aceptacion_legal]').isChecked(), false);
        await form.locator('[name=aceptacion_legal]').check();
        await Promise.all([page.waitForURL('**/cliente/dashboard'), form.getByRole('button', {name: 'Crear cuenta', exact: true}).click()]);
        for (const path of ['/progreso/estadisticas', '/gestion/sesiones']) await visit(page, path);
        await visit(page, '/gestion/usuarios', 403);
        await guest.close();
        console.log('Public legal documents, mandatory unchecked consent and private new client modules: passed.');

        const paths = {
            administrador: ['/gestion/usuarios', '/gestion/usuarios/create', '/gestion/clientes', '/gestion/clientes/create', '/gestion/planes', '/gestion/planes/create', '/gestion/sesiones', '/gestion/sesiones/create', '/administracion/solicitudes-datos', '/administracion/dos-factores', '/gestion/historial/ventas', '/gestion/historial/asistencias'],
            secretaria: ['/gestion/clientes', '/gestion/clientes/create', '/gestion/sesiones', '/gestion/sesiones/create', '/gestion/historial/ventas', '/gestion/historial/asistencias'],
            entrenador: ['/gestion/sesiones', '/gestion/sesiones/create'],
        };
        for (const [role, routes] of Object.entries(paths)) {
            const context = await browser.newContext({viewport: {width: 390, height: 844}});
            const tab = await context.newPage();
            tab.on('pageerror', e => errors.push(e.message));
            await login(tab, role);
            for (const route of routes) {
                await visit(tab, route);
                await tab.evaluate(() => alphaToggleTema());
                await visit(tab, route);
            }
            await tab.screenshot({path: 'storage/app/private/qa-new-' + role + '.png', fullPage: true});
            if (role === 'administrador') {
                await tab.setViewportSize({width: 1440, height: 1000});
                await visit(tab, '/productos');
                const edit = tab.locator('form[action*="/productos/"]').first();
                await edit.locator('..').locator('summary').click();
                await edit.locator('[name=nombre]').fill('<img src=x onerror=window.__alphaXss=true>');
                await Promise.all([tab.waitForNavigation(), edit.getByRole('button', {name: 'Guardar cambios'}).click()]);
                await visit(tab, '/ventas');
                await tab.locator('button[onclick="abrirModalVenta()"]').click();
                // A second dynamic row used to interpolate the product name as HTML.
                await tab.getByRole('button', {name: /Añadir|Agregar/}).first().click();
                assert.equal(await tab.evaluate(() => window.__alphaXss === true), false);
                assert.equal(await tab.locator('img[src=x]').count(), 0);
                await tab.screenshot({path: 'storage/app/private/qa-tpv-xss.png', fullPage: true});
            }
            await context.close();
            console.log(role + ': new modules, both themes, responsive layout and permissions passed.');
        }
        assert.deepEqual(errors, []);
        console.log('Audit browser checks passed. No JavaScript exceptions.');
    } finally {await browser.close();}
})().catch(error => {console.error(error); process.exitCode = 1;});

// Run against the isolated database from prepare_browser_qa.php, never a production URL.
const { chromium } = require(process.env.ALPHA_PLAYWRIGHT_MODULE || 'playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');

(async () => {
    const config = JSON.parse(fs.readFileSync('storage/app/private/browser-qa.json', 'utf8'));
    assert.equal(config.url, 'http://127.0.0.1:8011');
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const visit = async path => {
            const response = await page.goto(config.url + path, { waitUntil: 'domcontentloaded' });
            assert.equal(response.status(), 200, path);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow: ' + path);
        };
        const login = async (email, client = false) => {
            await visit('/login');
            if (client) await page.locator('#tab-btn-clientes').click();
            const form = page.locator(client ? '#form-cliente-login' : '#seccion-usuarios form');
            await form.locator(client ? '[name=correo]' : '[name=email]').fill(email);
            await form.locator('[name=password]').fill(config.password);
            await Promise.all([page.waitForURL('**/' + (client ? 'cliente/dashboard' : 'dashboard'), { waitUntil: 'domcontentloaded' }), form.locator('button[type=submit]').click()]);
        };
        const saveTheme = async mode => {
            await page.locator(`#appearance-form [value="${mode}"]`).check();
            await page.getByRole('button', { name: 'Guardar apariencia', exact: true }).click();
            await page.waitForFunction(() => document.getElementById('appearance-message').textContent === 'Apariencia guardada en tu cuenta.');
            assert.equal(await page.locator('html').getAttribute('data-theme'), mode);
        };
        await login('qa-client@example.test', true);
        await visit('/configuracion');
        for (const mode of ['light', 'dark', 'custom']) {
            if (mode === 'custom') {
                await page.locator('#color-primary').fill('#5b35b5');
                await page.locator('#color-accent').fill('#177565');
            }
            await saveTheme(mode);
            await page.reload({ waitUntil: 'domcontentloaded' });
            assert.equal(await page.locator('html').getAttribute('data-theme'), mode);
            await page.locator('#apariencia').screenshot({ path: `storage/app/private/qa-appearance-${mode}.png` });
            for (const path of ['/cliente/dashboard', '/productos', '/ejercicios', '/entrenamientos', '/progreso', '/membresias', '/entrenadores']) await visit(path);
            await visit('/configuracion');
        }
        await page.locator('#color-text').fill('#ffffff');
        assert.equal(await page.getByRole('button', { name: 'Guardar apariencia', exact: true }).isDisabled(), true);
        assert.equal(await page.locator('html').getAttribute('data-theme'), 'custom');
        await page.getByRole('button', { name: 'Restaurar predeterminados' }).click();
        await saveTheme('light');
        const csrfStatus = await page.evaluate(async () => (await fetch('/configuracion/apariencia', {method: 'POST', headers: {Accept: 'application/json', 'Content-Type': 'application/json'}, body: '{}'})).status);
        assert.equal(csrfStatus, 419, 'CSRF is enforced by the actual server');
        console.log('Three themes, invalid contrast, persistence, client modules and CSRF: passed.');

        await page.setViewportSize({ width: 390, height: 844 });
        await visit('/configuracion');
        await page.screenshot({ path: 'storage/app/private/qa-mobile-settings.png', fullPage: true });
        await page.getByRole('button', { name: 'Abrir menú de navegación' }).click();
        assert.equal(await page.locator('#alpha-hamburger-btn').getAttribute('aria-expanded'), 'true');
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('#alpha-hamburger-btn').getAttribute('aria-expanded'), 'false');
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await visit('/entrenamientos');
        await page.getByRole('button', { name: 'Crear nueva rutina' }).click();
        await page.waitForURL(/\/entrenamientos\/\d+$/, { waitUntil: 'domcontentloaded' });
        await page.locator('[data-add]').first().click();
        await page.locator('.campo-ej[data-campo=series]').waitFor();
        await page.locator('.campo-ej[data-campo=series]').fill('4');
        await page.locator('.campo-ej[data-campo=series]').press('Tab');
        await page.waitForFunction(() => document.getElementById('estado-guardado').textContent === 'Guardado');
        await page.reload({ waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('.campo-ej[data-campo=series]').inputValue(), '4');
        assert.equal(await page.evaluate(() => document.getAnimations().length), 0);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        await page.locator('[data-add]').first().waitFor();
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({ path: 'storage/app/private/qa-mobile-builder.png', fullPage: true });
        await visit('/configuracion');
        await saveTheme('dark');
        await page.getByRole('button', { name: 'Cerrar sesión en este dispositivo' }).click();
        await page.waitForURL('**/login', { waitUntil: 'domcontentloaded' });
        assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
        await login('qa-client@example.test', true);
        assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
        console.log('Mobile menu, routine saving, reduced motion and logout persistence: passed.');
        // Use a separate browser context for each role, as real independent sessions do.
        await context.close();
        for (const [role, paths] of Object.entries({administrador: ['/dashboard', '/cuentas', '/productos', '/entrenadores'], secretaria: ['/dashboard', '/asistencia', '/membresias', '/productos'], entrenador: ['/dashboard', '/entrenamientos', '/ejercicios', '/entrenadores']})) {
            const staff = await browser.newContext({ viewport: { width: 390, height: 844 } });
            const tab = await staff.newPage();
            tab.on('pageerror', e => errors.push(e.message));
            await tab.goto(config.url + '/login');
            await tab.locator('#seccion-usuarios [name=email]').fill(role + '@example.test');
            await tab.locator('#seccion-usuarios [name=password]').fill(config.password);
            await Promise.all([tab.waitForURL('**/dashboard', { waitUntil: 'domcontentloaded' }), tab.locator('#seccion-usuarios button[type=submit]').click()]);
            for (const path of paths) {
                const response = await tab.goto(config.url + path, { waitUntil: 'domcontentloaded' });
                assert.equal(response.status(), 200, role + path);
                assert.equal(await tab.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, role + path);
                if (role === 'administrador' && path === '/productos') {
                    for (const product of ['Agua mineral 600 ml', 'Bebida isotónica', 'Barra de proteína', 'Proteína whey 2 lb', 'Creatina monohidratada 300 g']) {
                        assert.equal(await tab.getByText(product, { exact: true }).count(), 1, product);
                    }
                    assert.match(await tab.locator('main').innerText(), /Catálogo completo: 5\/5/);
                    await tab.screenshot({ path: 'storage/app/private/qa-admin-products.png', fullPage: true });
                }
                if (role === 'administrador' && path === '/cuentas') {
                    const banForm = tab.locator('form[action$="/banear"]');
                    assert.equal(await banForm.count(), 1);
                    assert.equal(await tab.getByText('Registrar empleado').count(), 0);
                    assert.equal(await tab.getByText('Registrado el', { exact: false }).count(), 1);
                    assert.match(await banForm.getAttribute('data-confirm'), /historial se conservará/);
                    let confirmationSeen = false;
                    tab.once('dialog', async dialog => {
                        confirmationSeen = true;
                        assert.match(dialog.message(), /¿Banear/);
                        await dialog.dismiss();
                    });
                    await banForm.locator('button').click();
                    await tab.waitForTimeout(100);
                    assert.equal(confirmationSeen, true);
                    await tab.screenshot({ path: 'storage/app/private/qa-admin-accounts.png', fullPage: true });
                }
            }
            if (role !== 'administrador') {
                const accountsDenied = await tab.goto(config.url + '/cuentas');
                assert.equal(accountsDenied.status(), 403, role + ' cannot administer accounts');
            }
            const denied = await tab.goto(config.url + '/progreso');
            assert.equal(denied.status(), 403);
            await staff.close();
            console.log(role + ' routes and mobile layout: passed.');
        }
        assert.deepEqual(errors, []);
        console.log('Browser checks passed. No JavaScript exceptions.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });

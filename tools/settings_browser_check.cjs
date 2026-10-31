// Browser acceptance checks use only the disposable database created by prepare_browser_qa.php.
const puppeteer = require('puppeteer-core');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const cfg = JSON.parse(fs.readFileSync('storage/app/private/design-browser-qa.json', 'utf8'));
assert.equal(cfg.url, 'http://127.0.0.1:8021');
const out = 'outputs/configuracion-2026-10-30';
fs.mkdirSync(out, {recursive: true});

(async () => {
    const browser = await puppeteer.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    const errors = [], results = [];
    const visit = async (page, route) => {
        const response = await page.goto(cfg.url + route, {waitUntil: 'networkidle0'});
        assert.equal(response.status(), 200, route);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, 'Overflow: ' + route);
    };
    const submit = async (page, selector) => Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click(selector)]);
    const setField = async (page, selector, value) => {
        await page.$eval(selector, (input, next) => { input.value = next; input.dispatchEvent(new Event('input', {bubbles:true})); }, value);
    };
    try {
        for (const role of ['Administrador', 'Secretaria', 'Entrenador', 'Cliente']) {
            const context = await browser.createBrowserContext();
            const page = await context.newPage();
            page.on('pageerror', error => errors.push(`${role}: ${error.message}`));
            await page.setViewport({width:1440,height:1000});
            await visit(page, '/login');
            if (await page.$('[name=privacy_read]')) {
                await page.click('[name=privacy_read]');
                await submit(page, '.alpha-privacy-form button[type=submit]');
            }
            const client = role === 'Cliente';
            if (client) await page.click('#tab-btn-clientes');
            const form = client ? '#form-cliente-login' : '#form-usuario';
            await page.type(form + (client ? ' [name=correo]' : ' [name=email]'), client ? 'qa-client@example.test' : role.toLowerCase() + '@example.test');
            await page.type(form + ' [name=password]', cfg.password);
            await submit(page, form + ' button[type=submit]');
            assert.match(page.url(), /dashboard$/);
            await visit(page, '/configuracion');
            assert.ok(await page.$('#acerca-de'));
            assert.equal(!!await page.$('#gimnasio'), role === 'Administrador');
            await page.screenshot({path:`${out}/${role}-configuracion-1440.png`,fullPage:true});
            await page.click('a[href="#acerca-de"]');
            assert.match(page.url(), /#acerca-de$/);
            if (role === 'Administrador') {
                await setField(page, '[name="gimnasio[nombre]"]', 'Gimnasio de prueba visual');
                await setField(page, '[name="gimnasio[descripcion]"]', 'Entrenamiento y bienestar para nuestra comunidad.');
                await setField(page, '[name="gimnasio[capacidad]"]', '120');
                await setField(page, '[name="gimnasio[horarios]"]', 'Lunes a viernes: 6:00 a. m. a 9:00 p. m.\nSábado: 7:00 a. m. a 2:00 p. m.');
                await setField(page, '[name="gimnasio[correo]"]', 'contacto@example.test');
                await submit(page, '#gimnasio button');
                assert.ok((await page.$eval('#acerca-de', el => el.textContent)).includes('Gimnasio de prueba visual'));
                await visit(page, '/informacion');
                assert.equal(await page.$eval('h1', e=>e.textContent), 'Gimnasio de prueba visual');
                await page.screenshot({path:`${out}/informacion-1440.png`,fullPage:true});
                await visit(page, '/configuracion');
                results.push('Administrador: guarda datos públicos y aforo; Acerca de refleja el nombre y descripción.');
            }
            await page.click('#appearance-form [name=design][value=green]');
            await page.click('#appearance-form [name=mode][value=dark]');
            await page.select('#appearance-form [name=motion]', 'reduced');
            await page.click('#appearance-form button[type=submit]');
            await page.waitForFunction(() => document.getElementById('appearance-message').textContent === 'Apariencia guardada en tu cuenta.');
            await page.reload({waitUntil:'networkidle0'});
            assert.deepEqual(await page.$eval('html',e=>[e.dataset.design,e.dataset.theme,e.dataset.motion]), ['green','dark','reduced']);
            await page.click('#appearance-form [name=mode][value=custom]');
            await setField(page, '#color-text', '#ffffff');
            await setField(page, '#color-background', '#ffffff');
            assert.equal(await page.$eval('#appearance-form button[type=submit]',e=>e.disabled), true);
            await page.click('#appearance-form [name=mode][value=light]');
            assert.equal(await page.$eval('#appearance-form button[type=submit]',e=>e.disabled), false);
            await page.click('#appearance-form button[type=submit]');
            await page.waitForFunction(() => document.getElementById('appearance-message').textContent === 'Apariencia guardada en tu cuenta.');
            await page.reload({waitUntil:'networkidle0'});
            assert.deepEqual(await page.$eval('html',e=>[e.dataset.theme,e.dataset.motion]), ['light','reduced']);
            if (client) {
                const photo = `${out}/avatar-fixture.png`;
                await page.screenshot({path:photo,clip:{x:0,y:0,width:64,height:64}});
                await (await page.$('#perfil-avatar')).uploadFile(photo);
                await submit(page, 'form[action$="/configuracion/avatar"] button[type=submit]');
                assert.ok(await page.$('img[alt="Tu foto de perfil"]'));
                page.once('dialog', dialog=>dialog.accept());
                await submit(page, 'form[data-confirm] button[type=submit]');
                assert.equal(await page.$('img[alt="Tu foto de perfil"]'), null);
                results.push('Cliente: sube y quita su foto de perfil.');
            }
            for (const width of [390,768]) {
                await page.setViewport({width,height:900});
                await visit(page, '/configuracion');
                await page.screenshot({path:`${out}/${role}-configuracion-${width}.png`,fullPage:true});
                await page.$eval('#acerca-de',e=>e.scrollIntoView());
                await page.screenshot({path:`${out}/${role}-acerca-${width}.png`});
                if (role === 'Administrador') {
                    await page.$eval('#gimnasio',e=>e.scrollIntoView());
                    await page.screenshot({path:`${out}/gimnasio-${width}.png`});
                    await visit(page, '/informacion');
                    await page.screenshot({path:`${out}/informacion-${width}.png`,fullPage:true});
                }
            }
            if (role !== 'Entrenador') await visit(page, '/membresias');
            results.push(`${role}: Acerca de, permisos, apariencia persistida, contraste, movimiento reducido y pantallas de 390/768/1440 px.`);
            await context.close();
        }
        assert.deepEqual(errors, []);
        fs.writeFileSync(`${out}/browser-results.json`,JSON.stringify({results, errors},null,2));
        console.log(results.join('\n'));
        console.log('Settings browser checks passed; no JavaScript errors.');
    } finally { await browser.close(); }
})().catch(error=>{console.error(error); process.exitCode=1;});

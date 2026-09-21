// Browser form checks against serve_design_qa.cjs's disposable database only.
const {chromium}=require(process.env.ALPHA_PLAYWRIGHT_MODULE || process.env.TEMP+'/alpha-fitness-qa-tooling/node_modules/playwright');
const fs=require('node:fs');
const assert=require('node:assert/strict');
const cfg=JSON.parse(fs.readFileSync('storage/app/private/design-browser-qa.json','utf8'));
assert.equal(cfg.url,'http://127.0.0.1:8021');
const out='storage/app/private/design-check';
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try {
  const context=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
  const page=await context.newPage();
  const visit=async route=>{const r=await page.goto(cfg.url+route,{waitUntil:'networkidle'});assert.equal(r.status(),200);};
  await visit('/login');await page.locator('#tab-btn-clientes').click();await page.locator('#btnMostrarRegistro').click();
  const form=page.locator('#form-cliente-registro');
  const email=`design-${Date.now()}@example.test`;
  await form.locator('[name=nombre]').fill('Verificación de diseño');
  await form.locator('[name=correo]').fill(email);
  await form.locator('[name=password]').fill(cfg.password);
  await form.locator('[name=password_confirmation]').fill(cfg.password);
  await Promise.all([page.waitForURL('**/cliente/dashboard'),form.locator('[type=submit]').click()]);
  await visit('/membresias');
  const request=page.getByRole('button',{name:'Solicitar este plan'}).first();
  await Promise.all([page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('/membresias/')),request.click()]);
  await page.waitForLoadState('networkidle');
  await page.getByRole('heading',{name:'Pendiente de confirmación'}).waitFor();
  await page.screenshot({path:out+'/elegant-membership-pending.png',fullPage:true});
  const staff=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
  const tab=await staff.newPage();await tab.goto(cfg.url+'/login');
  await tab.locator('#form-usuario [name=email]').fill('secretaria@example.test');
  await tab.locator('#form-usuario [name=password]').fill(cfg.password);
  await Promise.all([tab.waitForURL('**/dashboard'),tab.locator('#form-usuario [type=submit]').click()]);
  await tab.goto(cfg.url+'/membresias?q='+encodeURIComponent(email),{waitUntil:'networkidle'});
  await tab.screenshot({path:out+'/elegant-membership-payment.png',fullPage:true});
  await tab.locator('[name=referencia]').fill('Comprobación local de interfaz');
  await Promise.all([tab.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('/activar')),tab.getByRole('button',{name:'Confirmar pago y activar',exact:true}).click()]);
  await tab.waitForLoadState('networkidle');await tab.getByRole('heading',{name:'Solicitud activada'}).waitFor();
  assert.ok(await tab.locator('tbody tr').filter({hasText:'Verificación de diseño'}).count()>=1);
  await visit('/membresias');await page.getByRole('heading',{name:'Solicitud activada'}).waitFor();
  await Promise.all([page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('/membresias/')),request.click()]);
  await page.waitForLoadState('networkidle');
  await Promise.all([page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('/cancelar')),page.getByRole('button',{name:'Cancelar solicitud'}).click()]);
  await page.waitForLoadState('networkidle');await page.getByRole('heading',{name:'Solicitud cancelada'}).waitFor();
  await visit('/configuracion');
  await page.locator('#apariencia').screenshot({path:out+'/elegant-light-settings.png'});
  for(const width of [1440,768,390]){
   await page.setViewportSize({width,height:1000});
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
   assert.equal(await page.locator('#color-primary').evaluate(e=>e.getBoundingClientRect().width),44);
  }
  await page.setViewportSize({width:1440,height:1000});
  await page.locator('[name=design][value=green]').check();await page.getByRole('button',{name:'Guardar apariencia',exact:true}).click();
  await page.waitForFunction(()=>document.getElementById('appearance-message').textContent==='Apariencia guardada en tu cuenta.');
  await page.locator('#apariencia').screenshot({path:out+'/green-light-settings.png'});
  await visit('/cliente/dashboard');await page.screenshot({path:out+'/green-dashboard.png',fullPage:true});
  await visit('/entrenamientos');await page.getByRole('button',{name:'Crear nueva rutina',exact:true}).click();await page.waitForURL(/\/entrenamientos\/\d+$/);
  await page.locator('[data-add]').first().waitFor();
  // Every image in the visible catalogue must load, including filenames with spaces/accents.
  const thumbs=page.locator('#catalogo-resultados img');
  assert.ok(await thumbs.count()>0,'Expected catalogue thumbnails');
  for(const img of await thumbs.all()) {await img.scrollIntoViewIfNeeded();await img.evaluate(e=>e.decode());}
  await page.setViewportSize({width:390,height:844});
  await page.evaluate(()=>{document.activeElement?.blur();window.scrollTo(0,0);document.getElementById('catalogo-resultados').scrollTop=0;});
  await page.screenshot({path:out+'/green-mobile-builder.png',fullPage:true});
  assert.equal((await page.goto(cfg.url+'/pagina-inexistente')).status(),404);
  assert.equal(await page.locator('html').getAttribute('data-design'),'green');
  console.log('Registration, membership request/payment/cancellation, settings layout, theme and thumbnails passed.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});

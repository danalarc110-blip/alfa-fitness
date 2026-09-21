// Run only with the disposable database from prepare_browser_qa.php / serve_design_qa.cjs.
const {chromium}=require(process.env.ALPHA_PLAYWRIGHT_MODULE || process.env.TEMP+'/alpha-fitness-qa-tooling/node_modules/playwright');
const fs=require('node:fs');
const assert=require('node:assert/strict');
const cfg=JSON.parse(fs.readFileSync('storage/app/private/design-browser-qa.json','utf8'));
assert.equal(cfg.url,'http://127.0.0.1:8021');
const out='storage/app/private/design-check';fs.mkdirSync(out,{recursive:true});
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 const errors=[];
 try{
 const ctx=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
 const page=await ctx.newPage();page.on('pageerror',e=>errors.push(e.message));
 const visit=async(route,tab=page)=>{
  const r=await tab.goto(cfg.url+route,{waitUntil:'networkidle',timeout:60000});
  assert.equal(r.status(),200,route);
  assert.equal(await tab.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Overflow '+route);
 };
 const login=async(email,client=false,tab=page)=>{
  await visit('/login',tab);
  if(client)await tab.locator('#tab-btn-clientes').click();
  const form=tab.locator(client?'#form-cliente-login':'#form-usuario');
  await form.locator(client?'[name=correo]':'[name=email]').fill(email);
  await form.locator('[name=password]').fill(cfg.password);
  await Promise.all([tab.waitForURL('**/'+(client?'cliente/dashboard':'dashboard')),form.locator('[type=submit]').click()]);
 };
 const save=async(design,mode)=>{
  await page.locator(`[name=design][value=${design}]`).check();
  await page.locator(`[name=mode][value=${mode}]`).check();
  await page.getByRole('button',{name:'Guardar apariencia',exact:true}).click();
  await page.waitForFunction(()=>document.getElementById('appearance-message').textContent==='Apariencia guardada en tu cuenta.');
  assert.equal(await page.locator('html').getAttribute('data-design'),design);
  assert.equal(await page.locator('html').getAttribute('data-theme'),mode);
 };
 await visit('/login');
 await page.screenshot({path:out+'/elegant-login.png',fullPage:true});
 await page.locator('#tab-btn-clientes').click();
 assert.equal(await page.locator('#form-cliente-registro').isVisible(),false);
 await page.locator('#btnMostrarRegistro').click();
 assert.equal(await page.locator('#form-cliente-registro').isVisible(),true);
 assert.equal(await page.locator('#form-cliente-login').isVisible(),false);
 await page.locator('[data-password=passwordRegistro]').click();
 assert.equal(await page.locator('#passwordRegistro').getAttribute('type'),'text');
 await page.locator('#btnMostrarLogin').click();
 assert.equal(await page.locator('#form-cliente-registro').isVisible(),false);
 await login('qa-client@example.test',true);
 await visit('/entrenamientos');
 await page.getByRole('button',{name:'Crear nueva rutina',exact:true}).click();
 await page.waitForURL(/\/entrenamientos\/\d+$/);
 const routineUrl=page.url();
 await page.locator('#campo-nombre').fill('Fuerza y equilibrio');
 await Promise.all([page.waitForResponse(r=>r.url()===routineUrl && r.request().method()==='PUT' && r.status()===200),page.locator('#campo-objetivo').fill('Mejorar la técnica y la fuerza')]);
 await page.locator('[data-add]').first().click();
 await page.locator('.campo-ej[data-campo=series]').waitFor();
 await page.locator('.campo-ej[data-campo=series]').fill('4');
 await Promise.all([page.waitForResponse(r=>r.url().includes('/entrenamientos/ejercicios/') && r.request().method()==='PUT' && r.status()===200),page.locator('.campo-ej[data-campo=series]').press('Tab')]);
 await page.reload({waitUntil:'networkidle'});
 assert.equal(await page.locator('.campo-ej[data-campo=series]').inputValue(),'4');
 assert.equal(await page.locator('.alpha-topbar').count(),1);
 await page.screenshot({path:out+'/elegant-builder.png',fullPage:true});
 const routes=['/cliente/dashboard','/entrenamientos','/ejercicios','/membresias','/entrenadores','/productos','/progreso'];
 for(const design of ['elegant','green']){
  for(const mode of ['light','dark']){
   await visit('/configuracion');
   await save(design,mode);
   await page.reload({waitUntil:'networkidle'});
   assert.equal(await page.locator(`[name=design][value=${design}]`).isChecked(),true);
   await page.locator('#apariencia').screenshot({path:out+`/${design}-${mode}-settings.png`});
   for(const route of routes){
    await visit(route);
    assert.equal(await page.locator('html').getAttribute('data-design'),design);
    assert.equal(await page.locator('html').getAttribute('data-theme'),mode);
    if(mode==='light')await page.screenshot({path:out+`/${design}-${route.split('/').pop()}.png`,fullPage:true});
   }
   await page.setViewportSize({width:390,height:844});
   for(const route of routes)await visit(route);
   await page.goto(routineUrl,{waitUntil:'networkidle'});
   assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'mobile builder');
   await page.getByRole('button',{name:'Abrir menú de navegación'}).click();
   assert.equal(await page.locator('#alpha-hamburger-btn').getAttribute('aria-expanded'),'true');
   await page.keyboard.press('Escape');
   assert.equal(await page.locator('#alpha-hamburger-btn').getAttribute('aria-expanded'),'false');
   if(mode==='light')await page.screenshot({path:out+`/${design}-mobile-builder.png`,fullPage:true});
   await page.setViewportSize({width:1440,height:1000});
   console.log(design+' '+mode+': all client modules, mobile and builder passed.');
  }
 }
 await visit('/configuracion');
 await page.locator('[name=mode][value=custom]').check();
 await page.locator('#color-text').fill('#ffffff');
 assert.equal(await page.getByRole('button',{name:'Guardar apariencia',exact:true}).isDisabled(),true);
 await page.getByRole('button',{name:'Restaurar predeterminados'}).click();
 await save('green','light');
 await page.locator('#color-primary').fill('#5b35b5');
 await save('green','custom');
 await page.reload({waitUntil:'networkidle'});
 assert.equal(await page.locator('#color-primary').inputValue(),'#5b35b5');
 await save('green','dark');
 await page.getByRole('button',{name:'Cerrar sesión en este dispositivo'}).click();
 await page.waitForURL('**/login');
 assert.equal(await page.locator('html').getAttribute('data-design'),'green');
 assert.equal(await page.locator('html').getAttribute('data-theme'),'dark');
 await login('qa-client@example.test',true);
 assert.equal(await page.locator('html').getAttribute('data-design'),'green');
 await visit('/configuracion');
 await page.getByRole('button',{name:'Restaurar predeterminados'}).click();
 await save('elegant','light');
 const csrf=await page.evaluate(async()=> (await fetch('/configuracion/apariencia',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json'},body:'{}'})).status);
 assert.equal(csrf,419);
 await ctx.close();
 for(const [role,routes] of Object.entries({administrador:['/dashboard','/cuentas','/productos','/entrenadores'],secretaria:['/dashboard','/asistencia','/membresias','/productos'],entrenador:['/dashboard','/entrenamientos','/ejercicios','/entrenadores']})){
  const staff=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
  const tab=await staff.newPage();tab.on('pageerror',e=>errors.push(e.message));
  await login(role+'@example.test',false,tab);
  for(const route of routes){
   await visit(route,tab);
   await tab.screenshot({path:out+`/${role}-${route.slice(1)}.png`,fullPage:true});
   await tab.setViewportSize({width:390,height:844});await visit(route,tab);
   await tab.setViewportSize({width:1440,height:1000});
  }
  const denied=await tab.goto(cfg.url+'/progreso');assert.equal(denied.status(),403);
  await staff.close();console.log(role+': routes, desktop, mobile and permissions passed.');
 }
 assert.deepEqual(errors,[]);
 console.log('Design browser checks passed. No JavaScript exceptions.');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});

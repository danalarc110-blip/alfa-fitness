// Read-only visual regression for the public login page; creates no accounts.
const {chromium}=require(process.env.ALPHA_PLAYWRIGHT_MODULE || process.env.TEMP+'/alpha-fitness-qa-tooling/node_modules/playwright');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const out='storage/app/private/login-check';fs.mkdirSync(out,{recursive:true});
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try {
  const page=await browser.newPage({viewport:{width:1440,height:1000},reducedMotion:'reduce'});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  const response=await page.goto('http://127.0.0.1:8000/login',{waitUntil:'networkidle'});
  assert.equal(response.status(),200);
  const visible=async(id,expected)=>assert.equal(await page.locator('#'+id).isVisible(),expected,id);
  for(const width of [1440,390]) {
   await page.setViewportSize({width,height:width===1440?1000:844});
   for(const design of ['elegant','green']) for(const mode of ['light','dark']) {
    await page.evaluate(({design,mode})=>window.AlphaAppearance.apply({...window.AlphaAppearance.current,design,mode}),{design,mode});
    await page.locator('#tab-btn-usuarios').click();
    await visible('form-usuario',true);await visible('seccion-clientes',false);
    await page.locator('#tab-btn-clientes').click();
    await visible('form-usuario',false);await visible('form-cliente-login',true);await visible('form-cliente-registro',false);
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
    if(design==='elegant'&&mode==='light') {
     await page.evaluate(()=>window.scrollTo(0,0));
     await page.screenshot({path:`${out}/clientes-${width}.png`,fullPage:true});
    }
    await page.locator('#btnMostrarRegistro').click();
    await visible('form-cliente-login',false);await visible('form-cliente-registro',true);
    assert.equal(await page.locator('#form-cliente-registro [name=nombre]').evaluate(e=>e===document.activeElement),true);
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
    await page.locator('#tab-btn-usuarios').click();await visible('seccion-clientes',false);await visible('form-usuario',true);
    await page.locator('#tab-btn-clientes').click();await visible('form-cliente-registro',true);await visible('form-cliente-login',false);
    await page.locator('#btnMostrarLogin').click();
    await visible('form-cliente-login',true);await visible('form-cliente-registro',false);
    assert.equal(await page.locator('#form-cliente-login [name=correo]').evaluate(e=>e===document.activeElement),true);
   }
  }
  assert.deepEqual(errors,[]);
  console.log('Login layout passed: exclusive login/registration, unchanged staff access, focus and no overflow at desktop/mobile in both designs and color modes.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});

// Runs the actual appearance scripts without network access or a database.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const colors = {primary:'#8a6200',accent:'#176b63',background:'#f3f4f6',surface:'#ffffff',text:'#18212f'};
const initial = {design:'elegant',mode:'light',motion:'system',colors};
function environment(fetchImpl, stored = null) {
    const storage = new Map(stored ? [['alphaAppearance', JSON.stringify(stored)]] : []);
    const events = [];
    const document = {documentElement:{style:{setProperty(){}},dataset:{},classList:{toggle(){}}}};
    const window = {dispatchEvent:event=>events.push(event),matchMedia:()=>({matches:false})};
    const context = vm.createContext({window,document,structuredClone,fetch:fetchImpl,
        CustomEvent:class {constructor(type,options){this.type=type;this.detail=options.detail;}},
        localStorage:{getItem:key=>storage.get(key),setItem:(key,value)=>storage.set(key,value),removeItem:key=>storage.delete(key)}});
    vm.runInContext(read('resources/js/theme-core.js'), context);
    const api = window.AlphaAppearance;
    api.init({authenticated:true,preference:initial,url:'/configuracion/apariencia',csrf:'test',
        palettes:{light:colors,dark:colors},designPalettes:{elegant:{light:colors,dark:colors},green:{light:colors,dark:colors}}});
    return {api,events,storage,document,context,window};
}
let checks = 0;
async function check(name,test) { await test(); checks++; console.log('PASS '+name); }
(async()=> {
    await check('invalid or HTML success response preserves the saved preference',async()=> {
        for (const body of [null,{}, {appearance:{...initial,motion:'unknown'}}, {appearance:{...initial,colors:{}}}]) {
            const env = environment(async()=>({ok:true,status:200,json:async()=>body}));
            await assert.rejects(env.api.save({...initial,mode:'dark'}),/No se pudo confirmar/);
            assert.equal(env.api.current.mode,'light');
            assert.equal(env.events.length,0);
            assert.equal(JSON.parse(env.storage.get('alphaAppearance')).mode,'light');
        }
    });
    await check('network and expired-session errors have actionable Spanish messages',async()=> {
        let env = environment(async()=>{throw new TypeError('Failed to fetch');});
        await assert.rejects(env.api.save(initial),/conexión/);
        for (const status of [401,419]) {
            env = environment(async()=>({ok:false,status,json:async()=>({})}));
            await assert.rejects(env.api.save(initial),/sesión expiró/);
        }
    });
    await check('motion survives save, theme changes and legacy preferences',async()=> {
        const env = environment(async(url,options)=>({ok:true,status:200,json:async()=>({appearance:JSON.parse(options.body)})}));
        await env.api.save({...initial,motion:'reduced'});
        assert.equal(env.document.documentElement.dataset.motion,'reduced');
        await env.window.alphaToggleTema();
        assert.equal(env.api.current.mode,'dark');
        assert.equal(env.api.current.motion,'reduced');
        assert.equal(env.api.normalize({mode:'light',colors}).motion,'system');
        assert.equal(env.api.normalize({...initial,motion:'unknown'}),null);
    });
    await check('preset recovery and in-flight form controls remain usable',async()=> {
        let resolve;
        const env = environment(()=>new Promise(done=>{resolve=done;}));
        const inputs = Object.entries(colors).map(([key,value])=>({id:'color-'+key,value,nextElementSibling:{value:''},disabled:false}));
        const listeners = {}, windowListeners = {};
        const save = {disabled:false};
        const fieldset = {disabled:false,classList:{toggle(){}}};
        const message = {textContent:'',setAttribute(){}};
        const reset = {disabled:false,addEventListener:(name,handler)=>listeners.reset=handler};
        const sample = {dataset:{},style:{setProperty(){}}};
        const elements = {design:{value:'elegant',disabled:false},mode:{value:'custom',disabled:false},motion:{value:'system',disabled:false}};
        const fields = [...Object.values(elements),...inputs,save,reset];
        const form = {elements,addEventListener:(name,handler)=>listeners[name]=handler,
            querySelectorAll:selector=>selector==='input[type="color"]'?inputs:fields,
            querySelector:()=>save,setAttribute(){},removeAttribute(){}};
        const nodes = {'appearance-form':form,'appearance-message':message,'appearance-preview':sample,'appearance-colors':fieldset,'appearance-reset':reset};
        env.document.getElementById=id=>nodes[id];
        env.window.addEventListener=(name,handler)=>windowListeners[name]=handler;
        env.window.dispatchEvent=event=>windowListeners[event.type]?.(event);
        vm.runInContext(read('resources/js/appearance-settings.js'),env.context);
        inputs.find(input=>input.id==='color-text').value=colors.background;
        listeners.input();
        assert.equal(save.disabled,true);
        elements.mode.value='light';listeners.input();
        assert.equal(save.disabled,false,'preset must not depend on unused custom contrast');
        const pending = listeners.submit({preventDefault(){}});
        assert.ok(fields.every(field=>field.disabled),'lock settings while saving');
        resolve({ok:true,status:200,json:async()=>({appearance:initial})});
        await pending;
        assert.equal(save.disabled,false);
        assert.equal(elements.mode.disabled,false);
        assert.equal(message.textContent,'Apariencia guardada en tu cuenta.');
    });
    console.log(`${checks} appearance regression groups passed.`);
})().catch(error=>{console.error(error);process.exitCode=1;});

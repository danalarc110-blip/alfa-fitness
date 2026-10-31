// Offline behavioral checks against the application's scripts. No real database or browser.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const inline = file => read(file).match(/@push\('scripts'\)[\s\S]*?<script>([\s\S]*?)<\/script>/)[1];
let checks = 0;
async function check(name, test) { await test(); checks++; console.log('PASS ' + name); }

function trainingEnvironment(fetchImpl) {
    const nodes = new Map();
    const element = id => {
        if (!nodes.has(id)) nodes.set(id, {textContent:'', className:'', style:{}, disabled:false,
            classList:{add(){},remove(){}}, setAttribute(){}, removeAttribute(){}});
        return nodes.get(id);
    };
    const opened = [], toasts = [], requests = [];
    const document = {getElementById:element, addEventListener(){},
        querySelectorAll: selector => selector.includes('data-completada') ? [{}] : [{},{}]};
    const context = vm.createContext({document, navigator:{}, Date, JSON, Object,
        localStorage:{getItem(){throw new Error('Storage blocked');},setItem(){throw new Error('Storage blocked');}},
        window:{alphaAnimateModalOpen:selector=>opened.push(selector),showAlphaToast:(message,type)=>toasts.push({message,type})},
        clearInterval(){},setInterval(){return 1;},
        fetch:async(url,options)=> {requests.push(JSON.parse(options.body));return fetchImpl(url,options);}});
    const code = inline('resources/views/entrenamientos/entrenar.blade.php')
        .replace(/\{\{ \$diaSeleccionado \? \$diaSeleccionado->id : 'null' \}\}/g,'null')
        .replace(/\{\{[\s\S]*?\}\}/g,'qa-value');
    vm.runInContext(code,context);
    return {context, opened, toasts, requests, element, run:script=>vm.runInContext(script,context)};
}

(async()=> {
    const progressFunction=read('resources/views/entrenamientos/entrenar.blade.php')
        .match(/function actualizarProgreso\(\) \{[\s\S]*?\n    \}/)[0];
    await check('an empty routine has no progress controls and does not crash',()=> {
        vm.runInNewContext(progressFunction+'; actualizarProgreso();',{
            document:{getElementById:()=>null,querySelectorAll:()=>[]},
        });
    });
    await check('completed series update visual and accessible progress together',()=> {
        const attributes={};
        const bar={style:{}}, text={textContent:''}, indicator={setAttribute:(key,value)=>attributes[key]=value};
        const nodes={'progreso-barra':bar,'progreso-texto':text,'progreso-series':indicator};
        vm.runInNewContext(progressFunction+'; actualizarProgreso();',{
            document:{getElementById:id=>nodes[id],querySelectorAll:selector=>selector.includes('data-completada')?[{}]:[{},{}]},
        });
        assert.equal(bar.style.transform,'scaleX(0.5)');
        assert.equal(attributes['aria-valuenow'],'50');
        assert.equal(text.textContent,'1 / 2 series completadas (50%)');
    });
    await check('blocked localStorage does not stop timer or sound controls',()=> {
        const env=trainingEnvironment(async()=>({ok:true,json:async()=>({ok:true})}));
        env.run('ajustarTimer(90); alphaToggleSonidoTimer(); iniciarTimer();');
        assert.equal(env.element('timer-display').textContent,'01:30');
        assert.equal(env.element('timer-estado').textContent,'Descansando');
        assert.ok(env.toasts.some(item=>item.message.includes('solo durante esta visita')));
    });
    await check('401 and 419 expired sessions never display success',async()=> {
        for(const status of [401,419]) {
            const env=trainingEnvironment(async()=>({ok:false,status,json:async()=>({})}));
            await env.run('finalizarEntrenamiento()');
            assert.equal(env.opened.length,0);
            assert.match(env.toasts.at(-1).message,/sesión expiró/);
            assert.equal(env.element('btn-finalizar-entrenamiento').disabled,false);
        }
    });
    await check('network, validation, malformed JSON and false success remain retryable',async()=> {
        const failures = [async()=>{throw new Error('Offline');},
            async()=>({ok:false,status:422,json:async()=>({errors:{dia_id:['Día inválido']}})}),
            async()=>({ok:true,status:200,json:async()=>{throw new Error('HTML response');}}),
            async()=>({ok:true,status:200,json:async()=>({ok:false})})];
        for(const failure of failures) {
            const env=trainingEnvironment(failure);
            await env.run('finalizarEntrenamiento()');
            assert.equal(env.opened.length,0);
            assert.equal(env.element('btn-finalizar-entrenamiento').disabled,false);
            assert.equal(env.toasts.at(-1).type,'error');
        }
    });
    await check('retry reuses session UUID and successful session is not submitted twice',async()=> {
        let attempt=0;
        const env=trainingEnvironment(async()=> {
            if(attempt++ === 0) throw new Error('Offline');
            return {ok:true,status:200,json:async()=>({ok:true})};
        });
        await env.run('finalizarEntrenamiento()');
        await env.run('finalizarEntrenamiento()');
        await env.run('finalizarEntrenamiento()');
        assert.equal(env.requests.length,2);
        assert.equal(env.requests[0].sesion_uuid,env.requests[1].sesion_uuid);
        assert.equal(env.requests[1].series_completadas,1);
        assert.equal(env.requests[1].total_series,2);
        assert.ok(env.requests[1].duracion_segundos>=1);
        assert.equal(env.opened.length,2);
        assert.match(env.element('resumen-fin-entrenamiento').textContent,/1 de 2/);
    });
    await check('double click during pending session sends one request',async()=> {
        let resolve;
        const pending=new Promise(done=>resolve=done);
        const env=trainingEnvironment(()=>pending);
        const first=env.run('finalizarEntrenamiento()');
        await env.run('finalizarEntrenamiento()');
        assert.equal(env.requests.length,1);
        assert.equal(env.element('btn-finalizar-entrenamiento').disabled,true);
        resolve({ok:true,status:200,json:async()=>({ok:true})});
        await first;
        assert.equal(env.opened.length,1);
    });
    await check('malicious product names stay text in newly added sale rows',()=> {
        const malicious='</option><img src=x onerror=alert(1)>';
        const element=()=>({dataset:{},children:[],listeners:{},textContent:'',value:'',
            classList:{add(){},remove(){},toggle(){}},setAttribute(){},removeAttribute(){},focus(){},
            addEventListener(name,handler){this.listeners[name]=handler;},
            append(...nodes){this.children.push(...nodes);},replaceChildren(){this.children=[];},
            querySelector(selector){this.parts??=new Map();if(!this.parts.has(selector))this.parts.set(selector,element());return this.parts.get(selector);},
            querySelectorAll(){return this.children;}});
        const nodes=new Map();
        const get=id=>{if(!nodes.has(id))nodes.set(id,element());return nodes.get(id);};
        get('venta-config').textContent=JSON.stringify({productos:[{id:1,nombre:malicious,precio_centavos:450,stock:2,activo:true}],itemsAnteriores:[],recuperarVenta:false});
        const add=element();add.dataset.anadirProducto='1';
        const document={getElementById:get,createElement:element,
            querySelectorAll:selector=>selector==='[data-anadir-producto]'?[add]:[],addEventListener(){}};
        const context=vm.createContext({document,window:{addEventListener(){}}});
        vm.runInContext(read('resources/js/ventas.js'),context);
        add.listeners.click();
        let rows=get('items-contenedor').children;
        assert.equal(rows.length,1);
        assert.equal(rows[0].innerHTML.includes(malicious),false);
        assert.equal(rows[0].querySelector('.fila-nombre').textContent,malicious);
        assert.equal(rows[0].querySelector('.input-producto').value,'1');
        assert.equal(get('gran-total').textContent,'$4.50');
        add.listeners.click();
        rows=get('items-contenedor').children;
        assert.equal(rows.length,1);
        assert.equal(rows[0].querySelector('.input-cantidad').value,'2');
        assert.equal(get('gran-total').textContent,'$9.00');
        add.listeners.click();
        assert.equal(get('gran-total').textContent,'$9.00');
        assert.match(get('venta-mensaje').textContent,/Stock disponible: 2/);
    });
    await check('named submit button value survives double-submit protection and browser restoration',()=> {
        const events={},windowEvents={},hidden=[];
        const button={name:'estrellas',value:'4',disabled:false,dataset:{}};
        const form={method:'post',dataset:{},setAttribute(){},removeAttribute(){},
            querySelectorAll:()=>[button],append:node=>hidden.push(node)};
        const document={addEventListener:(name,handler)=>events[name]=handler,
            createElement:()=>({dataset:{},remove(){hidden.splice(hidden.indexOf(this),1);}}),
            querySelectorAll:selector=>selector.startsWith('input')?hidden.slice():selector.startsWith('form')?[form]:[button]};
        const window={addEventListener:(name,handler)=>windowEvents[name]=handler};
        vm.runInContext(read('resources/js/feedback.js'),vm.createContext({document,window}));
        let prevented=0;
        const event={target:form,submitter:button,defaultPrevented:false,preventDefault(){prevented++;}};
        events.submit(event);
        assert.equal(button.disabled,true);
        assert.equal(hidden.length,1);
        assert.deepEqual({name:hidden[0].name,value:hidden[0].value},{name:'estrellas',value:'4'});
        events.submit(event);
        assert.equal(prevented,1);assert.equal(hidden.length,1);
        windowEvents.pageshow();
        assert.equal(hidden.length,0);assert.equal(button.disabled,false);
        button.value='5';events.submit(event);
        assert.equal(hidden[0].value,'5');
    });
    await check('modal keyboard wraps focus, Escape restores trigger and scroll',()=> {
        const listeners={};
        const document={activeElement:null,body:{style:{overflow:'auto'}},
            addEventListener:(name,handler)=>listeners[name]=handler,querySelectorAll:()=>[]};
        const makeButton=()=>({tabIndex:0,isConnected:true,getClientRects:()=>[{}],focus(){document.activeElement=this;}});
        const trigger=makeButton(),first=makeButton(),last=makeButton(),attrs={};
        const modal={id:'qa-modal',classList:{add(){},remove(){}},
            hasAttribute:key=>key in attrs,setAttribute:(key,value)=>attrs[key]=value,
            querySelector:selector=>selector==='button:not(:disabled), [href], input:not(:disabled)'?first:null,
            querySelectorAll:()=>[first,last],contains:node=>[first,last].includes(node),
            focus(){document.activeElement=this;}};
        document.querySelector=()=>modal;document.activeElement=trigger;
        const window={matchMedia:()=>({matches:true,addEventListener(){}}),addEventListener(){}};
        const context=vm.createContext({window,document,Set});
        vm.runInContext(read('resources/js/animations.js'),context);
        window.alphaAnimateModalOpen('#qa-modal');
        assert.equal(attrs.role,'dialog');assert.equal(attrs['aria-modal'],'true');
        assert.equal(document.activeElement,first);assert.equal(document.body.style.overflow,'hidden');
        let prevented=0;
        listeners.keydown({key:'Tab',shiftKey:true,preventDefault(){prevented++;}});
        assert.equal(document.activeElement,last);
        listeners.keydown({key:'Tab',shiftKey:false,preventDefault(){prevented++;}});
        assert.equal(document.activeElement,first);assert.equal(prevented,2);
        listeners.keydown({key:'Escape'});
        assert.equal(document.activeElement,trigger);assert.equal(document.body.style.overflow,'auto');
    });
    console.log(`${checks} frontend regression groups passed (offline VM; visual/browser checks are separate).`);
})().catch(error=>{console.error(error);process.exitCode=1;});

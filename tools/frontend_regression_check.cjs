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
        const rows=[], options=[];
        const select={append:option=>options.push(option)};
        const document={getElementById:()=>({appendChild:row=>rows.push(row)}),createElement:tag=>tag==='option'
            ?{dataset:{},textContent:''}:{innerHTML:'',querySelector:()=>select}};
        const code=inline('resources/views/ventas/index.blade.php')
            .replace('@json($productos)',JSON.stringify([{id:1,nombre:malicious,precio:'4.50',stock:2}]));
        const context=vm.createContext({document,window:{}});
        vm.runInContext(code,context);vm.runInContext('agregarFila()',context);
        assert.equal(rows.length,1);assert.equal(options.length,1);
        assert.equal(rows[0].innerHTML.includes(malicious),false);
        assert.equal(options[0].textContent.includes(malicious),true);
        assert.equal(options[0].value,1);
        assert.match(read('resources/views/ventas/index.blade.php'),/old\('venta_uuid',/);
    });
    await check('modal keyboard wraps focus, Escape restores trigger and scroll',()=> {
        const listeners={};
        const document={activeElement:null,body:{style:{overflow:'auto'}},
            addEventListener:(name,handler)=>listeners[name]=handler,querySelectorAll:()=>[]};
        const makeButton=()=>({tabIndex:0,getClientRects:()=>[{}],focus(){document.activeElement=this;}});
        const trigger=makeButton(),first=makeButton(),last=makeButton(),attrs={};
        const modal={id:'qa-modal',classList:{add(){},remove(){}},
            hasAttribute:key=>key in attrs,setAttribute:(key,value)=>attrs[key]=value,
            querySelector:selector=>selector==='button'?first:null,
            querySelectorAll:()=>[first,last],contains:node=>[first,last].includes(node),
            focus(){document.activeElement=this;}};
        document.querySelector=()=>modal;document.activeElement=trigger;
        const window={matchMedia:()=>({matches:true,addEventListener(){}})};
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

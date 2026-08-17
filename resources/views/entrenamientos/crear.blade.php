<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Alpha Fitness') }} - {{ $rutina->nombre }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <script>
        if (localStorage.getItem('alphaTema') === 'light') {
            document.documentElement.classList.add('light');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black text-white min-h-screen">
<div class="min-h-screen flex">
    @include('partials.sidebar', ['active' => 'entrenamientos'])

    <div class="flex-1 flex flex-col min-w-0 px-6 sm:px-10 py-8">

        {{-- CABECERA --}}
        <div class="flex items-start justify-between gap-4 mb-6 pb-6 border-b border-white/5" data-animate="header">
            <div class="min-w-0 flex-1">
                <input id="campo-nombre" value="{{ $rutina->nombre }}"
                    class="bg-transparent text-2xl font-bold text-white outline-none border-b-2 border-transparent focus:border-yellow-400 w-full max-w-md truncate transition-colors duration-200">
                <div class="flex flex-wrap items-center gap-2.5 mt-3 text-xs text-gray-400">
                    <input id="campo-objetivo" value="{{ $rutina->objetivo }}"
                        placeholder="Objetivo"
                        class="bg-[#141414] border border-white/10 rounded-xl px-3 py-2 outline-none focus:border-yellow-400/50 w-44 transition-colors">
                    <select id="campo-nivel" class="bg-[#141414] border border-white/10 rounded-xl px-3 py-2 outline-none focus:border-yellow-400/50 transition-colors">
                        @foreach (['Principiante', 'Intermedio', 'Avanzado'] as $n)
                            <option value="{{ $n }}" @selected($rutina->nivel === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 bg-[#141414] border border-white/10 rounded-xl px-3 py-1.5">
                        <span class="text-gray-400 text-xs">días/sem:</span>
                        <input id="campo-dias" type="number" min="1" max="7" value="{{ $rutina->dias_por_semana }}"
                            class="bg-transparent text-white font-semibold outline-none w-10 text-center">
                    </label>
                    <span id="estado-guardado" class="inline-flex items-center gap-1 text-yellow-400 text-xs font-semibold px-2 py-1 rounded-md bg-yellow-400/10 border border-yellow-400/20 transition-opacity duration-300 opacity-0">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Guardado
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                    class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
                <a href="{{ route('entrenamientos.index') }}"
                    class="alpha-btn-secondary text-xs font-semibold rounded-xl px-4 py-2.5 inline-flex items-center gap-1.5">
                    ← Volver a rutinas
                </a>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6 min-w-0">

            {{-- COLUMNA IZQUIERDA: DIAS --}}
            <div class="flex-1 min-w-0">
                <div id="tabs-dias" class="flex flex-wrap items-center gap-2 mb-4"></div>
                <div id="paneles-dias"></div>
                <div id="sin-dias" class="hidden alpha-card rounded-2xl p-8 text-center">
                    <p class="text-sm text-gray-400 mb-2">Esta rutina aún no tiene días configurados.</p>
                    <p class="text-xs text-yellow-400 font-semibold">Haz clic en "+ Día" para comenzar.</p>
                </div>
            </div>

            {{-- COLUMNA DERECHA: CATALOGO --}}
            <div class="w-full lg:w-[340px] shrink-0">
                <div class="alpha-card rounded-2xl p-5 lg:sticky lg:top-8 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-sm text-white">Catálogo de Ejercicios</h3>
                        <span class="text-[11px] text-gray-500 font-medium">Buscador</span>
                    </div>
                    <input id="catalogo-buscar" type="text" placeholder="Buscar por nombre o músculo..."
                        class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-gray-500 outline-none focus:border-yellow-400/60 mb-2.5 transition-colors">
                    <select id="catalogo-grupo"
                        class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-gray-300 outline-none focus:border-yellow-400/60 mb-3 transition-colors">
                        <option value="Todos">Todos los grupos musculares</option>
                        @foreach ($gruposMusculares as $g)
                            <option value="{{ $g }}">{{ $g }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-500 mb-3 bg-white/[0.02] p-2 rounded-lg border border-white/5">
                        Tip: Haz clic en <strong class="text-yellow-400">+</strong> para añadir al día activo o en la imagen para ver músculos implicados.
                    </p>
                    <div id="catalogo-resultados" class="flex flex-col gap-2 max-h-[64vh] overflow-y-auto pr-1 custom-scroll"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF  = document.querySelector('meta[name="csrf-token"]').content;
    const URLS  = {
        rutina:             "{{ route('entrenamientos.actualizar', $rutina) }}",
        crearDia:           "{{ route('entrenamientos.dias.crear', $rutina) }}",
        renombrarDia:       (id) => `/entrenamientos/dias/${id}`,
        eliminarDia:        (id) => `/entrenamientos/dias/${id}`,
        catalogo:           "{{ route('entrenamientos.catalogo.buscar') }}",
        crearEjercicio:     (diaId) => `/entrenamientos/dias/${diaId}/ejercicios`,
        actualizarEjercicio:(id)    => `/entrenamientos/ejercicios/${id}`,
        eliminarEjercicio:  (id)    => `/entrenamientos/ejercicios/${id}`,
    };

    let DIAS = @json($diasJson);

    let diaActivoId = DIAS.length ? DIAS[0].id : null;

    /* ---- helpers ---- */
    function api(url, method, body) {
        return fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        }).then(r => { if (!r.ok) throw r; return r.json(); });
    }

    function marcarGuardado() {
        const el = document.getElementById('estado-guardado');
        if (el) {
            el.classList.remove('opacity-0');
            el.classList.add('opacity-100');
            clearTimeout(marcarGuardado._t);
            marcarGuardado._t = setTimeout(() => {
                el.classList.remove('opacity-100');
                el.classList.add('opacity-0');
            }, 1800);
        }
    }

    function h(s) {
        return String(s ?? '').replace(/[&<>"']/g, c =>
            ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    }

    function miniImg(url, tiene, cls) {
        if (!tiene) return `<div class="${cls} flex items-center justify-center bg-white/5 rounded-xl text-gray-600 shrink-0 border border-white/5">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg></div>`;
        return `<img src="${h(url)}" class="${cls} object-cover rounded-xl shrink-0 border border-white/10 hover:border-yellow-400/50 transition-colors shadow-sm" loading="lazy">`;
    }

    /* ---- render ---- */
    function render() {
        const tabs    = document.getElementById('tabs-dias');
        const paneles = document.getElementById('paneles-dias');
        const sinDias = document.getElementById('sin-dias');

        sinDias.classList.toggle('hidden', DIAS.length > 0);

        tabs.innerHTML = DIAS.map(d => `
            <button data-tab="${d.id}"
                class="tab-dia px-4 py-2 rounded-xl text-sm font-semibold border transition-all duration-200 active:scale-95
                       ${d.id === diaActivoId
                           ? 'bg-yellow-400 text-black border-yellow-400 shadow-md shadow-yellow-400/20'
                           : 'bg-[#141414] text-gray-300 border-white/10 hover:border-white/30 hover:bg-white/5'}">
                ${h(d.titulo)}
            </button>`).join('') +
            `<button id="btn-add-dia"
                class="px-4 py-2 rounded-xl text-sm font-semibold border border-dashed border-white/20 text-gray-400 hover:text-yellow-400 hover:border-yellow-400/50 hover:bg-yellow-400/5 transition-all duration-200 active:scale-95">
                + Día
            </button>`;

        paneles.innerHTML = DIAS.map(d => renderPanel(d)).join('');

        /* eventos tabs */
        tabs.querySelectorAll('.tab-dia').forEach(btn => {
            btn.addEventListener('click',   () => { diaActivoId = +btn.dataset.tab; render(); });
            btn.addEventListener('dblclick',() => renombrarPrompt(+btn.dataset.tab));
        });
        document.getElementById('btn-add-dia').addEventListener('click', crearDia);

        /* eventos paneles */
        paneles.querySelectorAll('[data-del-dia]').forEach(b =>
            b.addEventListener('click', () => eliminarDia(+b.dataset.delDia)));
        paneles.querySelectorAll('[data-del-ej]').forEach(b =>
            b.addEventListener('click', () => eliminarEjercicio(+b.dataset.delEj)));
        paneles.querySelectorAll('.campo-ej').forEach(input =>
            input.addEventListener('change', () => guardarCampo(input)));
        paneles.querySelectorAll('[data-toggle]').forEach(el =>
            el.addEventListener('click', () => toggleImg(el)));
    }

    function renderPanel(d) {
        const oculto = d.id === diaActivoId ? '' : 'hidden';
        return `
        <div data-panel="${d.id}" class="${oculto} alpha-card rounded-2xl p-6 mb-4 shadow-xl">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-white/5">
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-base text-white">${h(d.titulo)}</h2>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">${d.ejercicios.length} ejercicio${d.ejercicios.length !== 1 ? 's' : ''}</span>
                </div>
                <button data-del-dia="${d.id}" class="text-xs font-semibold text-gray-500 hover:text-red-400 transition-colors px-2.5 py-1 rounded-lg hover:bg-red-500/10">Eliminar día</button>
            </div>
            ${d.ejercicios.length
                ? `<div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[560px]">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 border-b border-white/10 pb-2">
                                <th class="py-2.5 pr-3 font-semibold">Ejercicio</th>
                                <th class="py-2.5 px-2 font-semibold w-20 text-center">Series</th>
                                <th class="py-2.5 px-2 font-semibold w-24 text-center">Reps</th>
                                <th class="py-2.5 px-2 font-semibold w-28 text-center">Peso (kg)</th>
                                <th class="py-2.5 px-2 font-semibold w-28 text-center">Descanso (s)</th>
                                <th class="py-2.5 pl-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody>${d.ejercicios.map(re => renderFila(re)).join('')}</tbody>
                    </table>
                   </div>`
                : `<div class="p-8 text-center"><p class="text-xs text-gray-500">Sin ejercicios todavía en este día. Añade ejercicios desde el catálogo de la derecha →</p></div>`}
        </div>`;
    }

    function renderFila(re) {
        const ej = re.ejercicio;
        return `
        <tr class="border-b border-white/5 last:border-0 hover:bg-white/[0.02] transition-colors" data-fila="${re.id}">
            <td class="py-3 pr-3">
                <div class="flex items-center gap-3">
                    <div data-toggle data-a="${h(ej.imagen_url)}" data-b="${h(ej.imagen_musculos_url)}"
                         data-ta="${ej.tiene_imagen ? 1 : 0}" data-tb="${ej.tiene_imagen_musculos ? 1 : 0}"
                         data-m="a" title="Click: ver músculos" class="cursor-pointer w-10 h-10 shrink-0">
                        ${miniImg(ej.imagen_url, ej.tiene_imagen, 'w-10 h-10')}
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-white truncate text-sm">${h(ej.nombre)}</p>
                        <p class="text-[11px] text-gray-400">${h(ej.grupo_muscular)}</p>
                    </div>
                </div>
            </td>
            <td class="py-3 px-2">
                <input type="number" min="1" max="20" value="${re.series}"
                    data-id="${re.id}" data-campo="series"
                    class="campo-ej w-full bg-black/60 border border-white/10 rounded-xl px-2 py-1.5 text-center text-white outline-none focus:border-yellow-400/60 font-semibold text-sm transition-colors">
            </td>
            <td class="py-3 px-2">
                <input type="text" value="${h(re.repeticiones)}"
                    data-id="${re.id}" data-campo="repeticiones"
                    class="campo-ej w-full bg-black/60 border border-white/10 rounded-xl px-2 py-1.5 text-center text-white outline-none focus:border-yellow-400/60 font-semibold text-sm transition-colors">
            </td>
            <td class="py-3 px-2">
                <input type="number" min="0" step="0.5" value="${re.peso ?? ''}" placeholder="—"
                    data-id="${re.id}" data-campo="peso"
                    class="campo-ej w-full bg-black/60 border border-white/10 rounded-xl px-2 py-1.5 text-center text-white outline-none focus:border-yellow-400/60 font-semibold text-sm transition-colors">
            </td>
            <td class="py-3 px-2">
                <input type="number" min="0" step="5" value="${re.descanso_segundos}"
                    data-id="${re.id}" data-campo="descanso_segundos"
                    class="campo-ej w-full bg-black/60 border border-white/10 rounded-xl px-2 py-1.5 text-center text-white outline-none focus:border-yellow-400/60 font-semibold text-sm transition-colors">
            </td>
            <td class="py-3 pl-2 text-right">
                <button data-del-ej="${re.id}" title="Eliminar ejercicio" class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-500 hover:text-red-400 hover:bg-red-500/10 transition-all duration-150 active:scale-90">✕</button>
            </td>
        </tr>`;
    }

    /* ---- toggle imagen ↔ músculos ---- */
    function toggleImg(el) {
        const m = el.dataset.m === 'a' ? 'b' : 'a';
        el.dataset.m = m;
        const url  = el.dataset[m];
        const tiene = el.dataset['t'+m] === '1';
        el.innerHTML = miniImg(url, tiene, 'w-10 h-10');
    }

    /* ---- acciones DÍAS ---- */
    function crearDia() {
        api(URLS.crearDia, 'POST').then(({dia}) => {
            DIAS.push({ id: dia.id, titulo: dia.titulo, ejercicios: [] });
            diaActivoId = dia.id;
            render();
        });
    }

    function renombrarPrompt(id) {
        const dia = DIAS.find(d => d.id === id);
        const nuevo = prompt('Nombre del día:', dia.titulo);
        if (!nuevo?.trim()) return;
        api(URLS.renombrarDia(id), 'PUT', { titulo: nuevo.trim() }).then(({dia: d}) => {
            dia.titulo = d.titulo;
            render();
        });
    }

    function eliminarDia(id) {
        if (!confirm('¿Eliminar este día y todos sus ejercicios?')) return;
        api(URLS.eliminarDia(id), 'DELETE').then(() => {
            DIAS = DIAS.filter(d => d.id !== id);
            if (diaActivoId === id) diaActivoId = DIAS[0]?.id ?? null;
            render();
        });
    }

    /* ---- acciones EJERCICIOS ---- */
    function agregarEjercicio(ejId) {
        if (!diaActivoId) { alert('Crea un día primero.'); return; }
        api(URLS.crearEjercicio(diaActivoId), 'POST', { ejercicio_id: ejId })
            .then(({ rutina_ejercicio: re }) => {
                re.ejercicio.tiene_imagen          = re.ejercicio.tiene_imagen         ?? false;
                re.ejercicio.tiene_imagen_musculos = re.ejercicio.tiene_imagen_musculos ?? false;
                DIAS.find(d => d.id === diaActivoId).ejercicios.push(re);
                render();
            });
    }

    function guardarCampo(input) {
        const id    = +input.dataset.id;
        const campo = input.dataset.campo;
        let valor   = input.value;
        if (campo === 'peso' && valor === '') valor = null;
        api(URLS.actualizarEjercicio(id), 'PUT', { [campo]: valor }).then(marcarGuardado);
    }

    function eliminarEjercicio(id) {
        api(URLS.eliminarEjercicio(id), 'DELETE').then(() => {
            DIAS.forEach(d => d.ejercicios = d.ejercicios.filter(re => re.id !== id));
            render();
        });
    }

    /* ---- autosave cabecera ---- */
    let saveTmt;
    function guardarRutina() {
        clearTimeout(saveTmt);
        saveTmt = setTimeout(() => {
            api(URLS.rutina, 'PUT', {
                nombre:         document.getElementById('campo-nombre').value,
                objetivo:       document.getElementById('campo-objetivo').value,
                nivel:          document.getElementById('campo-nivel').value,
                dias_por_semana:document.getElementById('campo-dias').value,
            }).then(marcarGuardado);
        }, 600);
    }
    ['campo-nombre','campo-objetivo','campo-dias'].forEach(id =>
        document.getElementById(id).addEventListener('input', guardarRutina));
    document.getElementById('campo-nivel').addEventListener('change', guardarRutina);

    /* ---- catálogo ---- */
    function renderCatalogo(items) {
        const cont = document.getElementById('catalogo-resultados');
        if (!items.length) {
            cont.innerHTML = '<p class="text-xs text-gray-600 py-4 text-center">Sin resultados.</p>';
            return;
        }
        cont.innerHTML = items.map(ej => `
            <div class="flex items-center gap-2 bg-black border border-white/10 rounded-xl p-2">
                <div data-toggle data-a="${h(ej.imagen_url)}" data-b="${h(ej.imagen_musculos_url)}"
                     data-ta="${ej.tiene_imagen ? 1 : 0}" data-tb="${ej.tiene_imagen_musculos ? 1 : 0}"
                     data-m="a" title="Click: ver músculos" class="cursor-pointer w-11 h-11">
                    ${miniImg(ej.imagen_url, ej.tiene_imagen, 'w-11 h-11')}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm truncate">${h(ej.nombre)}</p>
                    <p class="text-[11px] text-gray-600">${h(ej.grupo_muscular)}${ej.subgrupo ? ' · ' + h(ej.subgrupo) : ''}</p>
                </div>
                <button data-add="${ej.id}"
                    class="shrink-0 w-7 h-7 rounded-lg bg-yellow-400 text-black font-bold text-lg leading-none hover:bg-yellow-300 transition-colors">+</button>
            </div>`).join('');

        cont.querySelectorAll('[data-toggle]').forEach(el =>
            el.addEventListener('click', () => toggleImg(el)));
        cont.querySelectorAll('[data-add]').forEach(b =>
            b.addEventListener('click', () => agregarEjercicio(+b.dataset.add)));
    }

    let searchTmt;
    function buscar() {
        clearTimeout(searchTmt);
        searchTmt = setTimeout(() => {
            const q     = document.getElementById('catalogo-buscar').value;
            const grupo = document.getElementById('catalogo-grupo').value;
            api(`${URLS.catalogo}?q=${encodeURIComponent(q)}&grupo=${encodeURIComponent(grupo)}`, 'GET')
                .then(({ ejercicios }) => renderCatalogo(ejercicios));
        }, 250);
    }
    document.getElementById('catalogo-buscar').addEventListener('input', buscar);
    document.getElementById('catalogo-grupo').addEventListener('change', buscar);

    /* ---- init ---- */
    render();
    buscar();
})();
</script>
</body>
</html>

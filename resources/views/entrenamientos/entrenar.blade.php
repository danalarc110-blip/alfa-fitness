@extends('layouts.app', ['active' => 'entrenamientos'])
@section('title', 'Entrenar: ' . $rutina->nombre)

@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('entrenamientos.index') }}" class="text-gray-400 hover:text-yellow-400 transition-colors p-1" title="Volver a entrenamientos">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">{{ $rutina->nombre }}</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-400/10 text-emerald-400 border border-emerald-400/20">
                ● En vivo
            </span>
        </div>
        <p class="text-gray-400 text-xs mt-1 ml-7">{{ $rutina->objetivo ?: 'Entrenamiento guiado' }} · Nivel {{ $rutina->nivel }}</p>
    </div>

    <div class="flex items-center gap-3 self-end sm:self-auto">
        <a href="{{ route('entrenamientos.imprimir', $rutina) }}" target="_blank"
            class="alpha-btn-secondary px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Imprimir
        </a>
        <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
            class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
    </div>
</header>
@endsection

@section('content')
{{-- SELECTOR DE DÍAS (TABS) --}}
@if($rutina->dias->count() > 1)
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-6 -mx-4 px-4 sm:mx-0 sm:px-0 no-scrollbar whitespace-nowrap" data-animate="card">
        @foreach($rutina->dias as $d)
            <a href="{{ route('entrenamientos.entrenar', ['rutina' => $rutina, 'dia' => $d->id]) }}"
                class="px-4 py-2 rounded-xl text-xs font-semibold border shrink-0 transition-all {{ $diaSeleccionado && $diaSeleccionado->id === $d->id ? 'bg-yellow-400 text-black border-yellow-400 shadow-md shadow-yellow-400/20' : 'bg-[#141414] text-gray-300 border-white/10 hover:border-white/30 hover:bg-white/5' }}">
                {{ $d->titulo }}
            </a>
        @endforeach
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-6 items-start">
    {{-- LISTA DE EJERCICIOS DEL DÍA --}}
    <div class="space-y-4">
        @if($diaSeleccionado && $diaSeleccionado->ejercicios->isNotEmpty())
            <div class="flex items-center justify-between px-1 mb-2">
                <span class="text-xs font-semibold text-gray-400">
                    {{ $diaSeleccionado->titulo }} · {{ $diaSeleccionado->ejercicios->count() }} {{ $diaSeleccionado->ejercicios->count() === 1 ? 'ejercicio' : 'ejercicios' }}
                </span>
                <span class="text-xs font-bold text-yellow-400" id="progreso-texto">0 / 0 series completadas</span>
            </div>

            {{-- BARRA DE PROGRESO DE SERIES --}}
            <div class="w-full bg-white/5 rounded-full h-2 overflow-hidden mb-4 border border-white/5">
                <div id="progreso-barra" class="bg-gradient-to-r from-yellow-400 to-amber-500 h-2 rounded-full transition-all duration-300" style="width: 0%;"></div>
            </div>

            @foreach($diaSeleccionado->ejercicios as $index => $re)
                @php($ej = $re->ejercicio)
                <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden" data-animate="card">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-black/60 border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                            @if($ej && $ej->tiene_imagen)
                                <img src="{{ $ej->imagen_url }}" alt="{{ $ej->nombre }}" class="w-full h-full object-contain">
                            @else
                                <svg class="w-8 h-8 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <h3 class="text-base font-bold text-white truncate">
                                    {{ $index + 1 }}. {{ $ej->nombre ?? 'Ejercicio' }}
                                </h3>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-white/5 text-gray-300 border border-white/5 shrink-0">
                                    {{ $ej->grupo_muscular ?? 'General' }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400 mb-3">
                                <span><strong>Series:</strong> {{ $re->series }}</span>
                                <span><strong>Reps:</strong> {{ $re->repeticiones }}</span>
                                @if($re->peso)
                                    <span><strong>Peso objetivo:</strong> {{ $re->peso }} kg</span>
                                @endif
                                <span><strong>Descanso:</strong> {{ $re->descanso_segundos ?: 60 }}s</span>
                            </div>

                            {{-- CHECKLIST DE SERIES INTERACTIVO --}}
                            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-white/5">
                                @for($s = 1; $s <= ($re->series ?: 3); $s++)
                                    <button type="button"
                                        onclick="toggleSerie(this, {{ $re->descanso_segundos ?: 60 }})"
                                        class="serie-btn px-3 py-1.5 rounded-xl text-xs font-bold border transition-all duration-150 flex items-center gap-1.5 bg-black/40 border-white/10 text-gray-300 hover:border-yellow-400/50 hover:text-white"
                                        data-completada="false">
                                        <span class="w-3.5 h-3.5 rounded-full border border-current flex items-center justify-center text-[10px]">&check;</span>
                                        Serie {{ $s }}
                                    </button>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="pt-4 text-center">
                <button type="button" id="btn-finalizar-entrenamiento" onclick="finalizarEntrenamiento()" class="alpha-btn-primary px-8 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-yellow-400/20 active:scale-95 transition-transform">
                    ¡Finalizar Entrenamiento!
                </button>
            </div>
        @else
            <div class="alpha-card rounded-2xl p-10 text-center text-gray-400">
                <p class="font-semibold text-white">Este día no tiene ejercicios asignados.</p>
                <a href="{{ route('entrenamientos.editar', $rutina) }}" class="alpha-btn-secondary px-4 py-2 rounded-xl text-xs font-semibold inline-block mt-3">
                    Editar rutina y agregar ejercicios
                </a>
            </div>
        @endif
    </div>

    {{-- PANEL LATERAL: CRONÓMETRO DE DESCANSO INTERACTIVO --}}
    <div class="space-y-5 lg:sticky lg:top-6">
        <div class="alpha-card rounded-2xl p-6 border border-white/10 shadow-2xl relative overflow-hidden" data-animate="card">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Cronómetro de Descanso
                </h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="alphaToggleSonidoTimer()" id="btn-toggle-sonido"
                        class="text-xs p-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-yellow-400 transition-colors"
                        title="Sonido y vibración al terminar descanso" aria-label="Alternar sonido y vibración">
                        <svg id="icono-sonido-on" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                        <svg id="icono-sonido-off" class="w-3.5 h-3.5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                    </button>
                    <span id="timer-estado" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/5 text-gray-400">Detenido</span>
                </div>
            </div>

            {{-- PANTALLA DEL TIMER --}}
            <div class="text-center py-6 bg-black/60 rounded-2xl border border-white/5 mb-4 relative overflow-hidden">
                <span id="timer-display" class="font-mono text-5xl font-black text-yellow-400 tracking-wider">01:00</span>
                <p class="text-[11px] text-gray-500 mt-1" id="timer-label">Tiempo de descanso recomendado</p>
            </div>

            {{-- CONTROLES DEL TIMER --}}
            <div class="grid grid-cols-3 gap-2 mb-4">
                <button type="button" onclick="iniciarTimer()" id="btn-iniciar"
                    class="alpha-btn-primary py-2.5 rounded-xl text-xs font-bold text-center">
                    Iniciar
                </button>
                <button type="button" onclick="pausarTimer()" id="btn-pausar"
                    class="alpha-btn-secondary py-2.5 rounded-xl text-xs font-semibold text-center">
                    Pausar
                </button>
                <button type="button" onclick="reiniciarTimer()"
                    class="alpha-btn-secondary py-2.5 rounded-xl text-xs font-semibold text-center">
                    Reset
                </button>
            </div>

            {{-- BOTONES RÁPIDOS (+30s, +60s, +90s) --}}
            <div class="flex items-center justify-center gap-2 pt-3 border-t border-white/5">
                <button type="button" onclick="ajustarTimer(30)" class="px-2.5 py-1 rounded-lg text-xs bg-white/5 hover:bg-white/10 text-gray-300 border border-white/10 font-mono transition-colors">
                    30s
                </button>
                <button type="button" onclick="ajustarTimer(60)" class="px-2.5 py-1 rounded-lg text-xs bg-white/5 hover:bg-white/10 text-gray-300 border border-white/10 font-mono transition-colors">
                    60s
                </button>
                <button type="button" onclick="ajustarTimer(90)" class="px-2.5 py-1 rounded-lg text-xs bg-white/5 hover:bg-white/10 text-gray-300 border border-white/10 font-mono transition-colors">
                    90s
                </button>
                <button type="button" onclick="ajustarTimer(120)" class="px-2.5 py-1 rounded-lg text-xs bg-white/5 hover:bg-white/10 text-gray-300 border border-white/10 font-mono transition-colors">
                    2m
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL CELEBRACIÓN FINAL --}}
<div id="modal-fin-entrenamiento" aria-labelledby="titulo-fin-entrenamiento" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 hidden">
    <div class="alpha-card bg-[#141416] border border-white/10 rounded-2xl w-full max-w-md p-6 text-center shadow-2xl relative">
        <div class="w-16 h-16 rounded-full bg-yellow-400/20 text-yellow-400 flex items-center justify-center mx-auto mb-4 text-3xl">
            🏆
        </div>
        <h3 id="titulo-fin-entrenamiento" class="text-xl font-black text-white mb-1">¡Entrenamiento registrado!</h3>
        <p id="resumen-fin-entrenamiento" class="text-gray-400 text-xs mb-5">Tu sesión se guardó en el historial.</p>
        <div class="bg-black/40 rounded-xl p-4 border border-white/5 mb-5 text-left text-xs space-y-1">
            <p class="text-gray-400">Rutina: <strong class="text-white">{{ $rutina->nombre }}</strong></p>
            <p class="text-gray-400">Día: <strong class="text-white">{{ $diaSeleccionado?->titulo ?? 'Entrenamiento' }}</strong></p>
            <p class="text-gray-400">Fecha: <strong class="text-yellow-400">{{ now()->format('d/m/Y H:i') }}</strong></p>
        </div>
        <div class="flex flex-col sm:flex-row justify-center gap-2">
            <button type="button" onclick="window.alphaAnimateModalClose('#modal-fin-entrenamiento')" class="alpha-btn-secondary px-4 py-2.5 rounded-xl text-xs font-semibold">Cerrar resumen</button>
            <a href="{{ route('entrenamientos.historial') }}" class="alpha-btn-primary px-4 py-2.5 rounded-xl text-xs font-semibold">
                Ver mi Historial
            </a>
            <a href="{{ route('progreso.index') }}" class="alpha-btn-secondary px-4 py-2.5 rounded-xl text-xs font-semibold">
                Registrar PRs
            </a>
            <a href="{{ route('entrenamientos.index') }}" class="alpha-btn-secondary px-4 py-2.5 rounded-xl text-xs font-semibold">
                Volver a rutinas
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ESTADO DEL CRONÓMETRO Y ALERTA MULTIMEDIA
    let segundosRestantes = 60;
    let tiempoBase = 60;
    let timerInterval = null;
    let sonidoHabilitado = true;
    try { sonidoHabilitado = localStorage.getItem('alfa_timer_sonido') !== 'false'; }
    catch (error) { /* El cronómetro funciona aunque el navegador no permita guardar preferencias. */ }
    let sharedAudioCtx = null;

    function obtenerAudioCtx() {
        if (!sharedAudioCtx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                sharedAudioCtx = new AudioCtx();
            }
        }
        if (sharedAudioCtx && sharedAudioCtx.state === 'suspended') {
            sharedAudioCtx.resume().catch(() => {});
        }
        return sharedAudioCtx;
    }

    function actualizarIconoSonido() {
        const iconOn = document.getElementById('icono-sonido-on');
        const iconOff = document.getElementById('icono-sonido-off');
        if (iconOn && iconOff) {
            if (sonidoHabilitado) {
                iconOn.classList.remove('hidden');
                iconOff.classList.add('hidden');
            } else {
                iconOn.classList.add('hidden');
                iconOff.classList.remove('hidden');
            }
        }
    }

    function alphaToggleSonidoTimer() {
        sonidoHabilitado = !sonidoHabilitado;
        try { localStorage.setItem('alfa_timer_sonido', sonidoHabilitado ? 'true' : 'false'); }
        catch (error) { window.showAlphaToast?.('La preferencia de sonido se aplicará solo durante esta visita.', 'info'); }
        actualizarIconoSonido();
        if (sonidoHabilitado) {
            obtenerAudioCtx();
        }
        if (window.showAlphaToast) {
            window.showAlphaToast(sonidoHabilitado ? 'Alerta sonora y vibración activada' : 'Alerta sonora y vibración silenciada', 'info');
        }
    }

    function reproducirAlertaDescanso() {
        if (!sonidoHabilitado) return;

        // Vibración háptica en móviles compatibles
        if (navigator.vibrate) {
            try {
                navigator.vibrate([180, 80, 180, 80, 250]);
            } catch (error) { window.showAlphaToast?.('La vibración no está disponible en este dispositivo.', 'info'); }
        }

        // Tono armónico elegante con Web Audio API (D5 y A5)
        try {
            const ctx = obtenerAudioCtx();
            if (!ctx) return;
            const playBell = (freq, delay, dur) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);
                gain.gain.setValueAtTime(0.35, ctx.currentTime + delay);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(ctx.currentTime + delay);
                osc.stop(ctx.currentTime + delay + dur);
            };
            playBell(587.33, 0, 0.4);
            playBell(880.00, 0.2, 0.6);
        } catch (error) {
            window.showAlphaToast?.('El sonido no está disponible en este navegador. El cronómetro sigue funcionando.', 'info');
        }
    }

    document.addEventListener('DOMContentLoaded', actualizarIconoSonido);

    function formatearTiempo(s) {
        const min = Math.floor(s / 60);
        const sec = s % 60;
        return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
    }

    function actualizarDisplay() {
        document.getElementById('timer-display').textContent = formatearTiempo(segundosRestantes);
    }

    function iniciarTimer() {
        if (timerInterval) return;
        document.getElementById('timer-estado').textContent = 'Descansando';
        document.getElementById('timer-estado').className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-400/20 text-emerald-400 border border-emerald-400/30';

        timerInterval = setInterval(() => {
            if (segundosRestantes > 0) {
                segundosRestantes--;
                actualizarDisplay();
            } else {
                pausarTimer();
                document.getElementById('timer-estado').textContent = '¡A entrenar!';
                document.getElementById('timer-estado').className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-yellow-400 text-black animate-pulse';
                reproducirAlertaDescanso();
                if (window.showAlphaToast) {
                    window.showAlphaToast('¡Tiempo de descanso cumplido! Comienza la siguiente serie.', 'info');
                }
            }
        }, 1000);
    }

    function pausarTimer() {
        clearInterval(timerInterval);
        timerInterval = null;
        document.getElementById('timer-estado').textContent = 'Pausado';
        document.getElementById('timer-estado').className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/5 text-gray-400';
    }

    function reiniciarTimer() {
        pausarTimer();
        segundosRestantes = tiempoBase;
        actualizarDisplay();
        document.getElementById('timer-estado').textContent = 'Listo';
    }

    function ajustarTimer(segundos) {
        tiempoBase = segundos;
        segundosRestantes = segundos;
        actualizarDisplay();
        reiniciarTimer();
    }

    // TOGGLE DE SERIES INTERACTIVAS
    function toggleSerie(btn, descanso) {
        obtenerAudioCtx();
        const completada = btn.dataset.completada === 'true';
        if (!completada) {
            btn.dataset.completada = 'true';
            btn.classList.remove('bg-black/40', 'border-white/10', 'text-gray-300');
            btn.classList.add('bg-yellow-400', 'text-black', 'border-yellow-400', 'shadow-md', 'shadow-yellow-400/20');
            // Auto-iniciar descanso correspondiente al ejercicio
            ajustarTimer(descanso || 60);
            iniciarTimer();
        } else {
            btn.dataset.completada = 'false';
            btn.classList.remove('bg-yellow-400', 'text-black', 'border-yellow-400', 'shadow-md', 'shadow-yellow-400/20');
            btn.classList.add('bg-black/40', 'border-white/10', 'text-gray-300');
        }
        actualizarProgreso();
    }

    function actualizarProgreso() {
        const total = document.querySelectorAll('.serie-btn').length;
        const hechas = document.querySelectorAll('.serie-btn[data-completada="true"]').length;
        const pct = total > 0 ? Math.round((hechas / total) * 100) : 0;

        document.getElementById('progreso-barra').style.width = `${pct}%`;
        document.getElementById('progreso-texto').textContent = `${hechas} / ${total} series completadas (${pct}%)`;
    }

    const sesionIniciadaTimestamp = Date.now();
    const sesionUuid = '{{ (string) \Illuminate\Support\Str::uuid() }}';
    let sesionGuardada = false;
    let guardandoSesion = false;

    async function finalizarEntrenamiento() {
        if (guardandoSesion) return;
        pausarTimer();
        const total = document.querySelectorAll('.serie-btn').length;
        const hechas = document.querySelectorAll('.serie-btn[data-completada="true"]').length;
        const duracionSegundos = Math.max(1, Math.round((Date.now() - sesionIniciadaTimestamp) / 1000));

        if (!sesionGuardada) {
            const button = document.getElementById('btn-finalizar-entrenamiento');
            guardandoSesion = true;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch("{{ route('entrenamientos.finalizar', $rutina) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        sesion_uuid: sesionUuid,
                        dia_id: {{ $diaSeleccionado ? $diaSeleccionado->id : 'null' }},
                        duracion_segundos: duracionSegundos,
                        series_completadas: hechas,
                        total_series: total
                    })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.ok !== true) {
                    throw new Error(response.status === 401 || response.status === 419 ? 'Tu sesión expiró. Inicia sesión de nuevo para guardar tu entrenamiento.' : Object.values(data.errors || {}).flat()[0] || 'No se pudo registrar tu entrenamiento. Intenta nuevamente.');
                }
                sesionGuardada = true;
                document.getElementById('resumen-fin-entrenamiento').textContent = `Tu sesión se guardó con ${hechas} de ${total} series completadas.`;
            } catch (error) {
                window.showAlphaToast?.(error.message || 'Revisa tu conexión e intenta guardar nuevamente.', 'error');
                return;
            } finally {
                guardandoSesion = false;
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        }

        window.alphaAnimateModalOpen('#modal-fin-entrenamiento');
    }

    // Inicializar progreso al cargar
    document.addEventListener('DOMContentLoaded', () => {
        actualizarProgreso();
        actualizarDisplay();
    });
</script>
@endpush

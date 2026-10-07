@extends('layouts.app', ['active' => 'entrenamientos'])
@section('title', 'Entrenamientos')

@section('page-header')
<header class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Entrenamientos</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                Rutinas Personalizadas
            </span>
        </div>
        <p class="text-gray-400 text-xs mt-1">Gestiona, crea y entrena tus planes de acondicionamiento físico.</p>
    </div>

    <div class="flex flex-wrap items-center gap-3 self-end lg:self-auto">
        <a href="{{ route('entrenamientos.historial') }}" class="alpha-btn-secondary px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Mi Historial
        </a>
        <form method="POST" action="{{ route('entrenamientos.crear') }}">
            @csrf
            <button type="submit" class="alpha-btn-primary px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-lg shadow-yellow-400/15">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Crear nueva rutina
            </button>
        </form>
        <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
            class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
    </div>
</header>
@endsection

@section('content')
@if($guard === 'web' && \App\Support\Acceso::permite(auth('web')->user(), 'asignar_rutinas') && $clientes->isNotEmpty())
<div class="alpha-card rounded-2xl p-4 sm:p-5 border border-white/10 mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4" data-animate="card">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <h3 class="text-sm font-bold text-white">Monitoreo de Progreso de Clientes</h3>
            <p class="text-xs text-gray-400">Consulta los récords personales (PRs) y evolución física de los atletas.</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <select id="select-progreso-cliente" class="bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-yellow-400/60 max-w-xs">
            <option value="">-- Ver progreso de un cliente --</option>
            @foreach($clientes as $cli)
                <option value="{{ route('entrenador.cliente.progreso', $cli) }}">{{ $cli->nombre }} · #{{ $cli->id }}</option>
            @endforeach
        </select>
        <button type="button" onclick="const url = document.getElementById('select-progreso-cliente').value; if(url) window.location.href = url;" class="alpha-btn-primary px-3 py-2 rounded-xl text-xs font-semibold">
            Consultar
        </button>
    </div>
</div>
@endif

<div class="flex justify-between items-center mb-5">
    <h2 class="text-base font-bold text-white">Mis rutinas</h2>
    <span class="text-xs text-gray-400">{{ $rutinas->total() }} {{ $rutinas->total() === 1 ? 'rutina' : 'rutinas' }}</span>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
    @forelse($rutinas as $rutina)
        <div class="alpha-card rounded-2xl p-5 border border-white/10 flex flex-col justify-between relative overflow-hidden group shadow-lg" data-animate="card">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/5 text-gray-300 border border-white/5">
                        {{ $rutina->nivel ?? 'General' }}
                    </span>
                    @if($rutina->asignado_por)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                            Coach: {{ $rutina->asignado_por }}
                        </span>
                    @endif
                </div>

                <h3 class="text-base font-bold text-white group-hover:text-yellow-400 transition-colors mb-1">
                    {{ $rutina->nombre }}
                </h3>
                <p class="text-xs text-gray-400 mb-3">{{ $rutina->objetivo ?: 'Sin objetivo definido' }}</p>

                <div class="flex items-center gap-3 text-xs text-gray-400 mb-4 pb-3 border-b border-white/5">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        {{ $rutina->dias->count() }} {{ $rutina->dias->count() === 1 ? 'día' : 'días' }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 6.5 4 4M4 4l-1.5 1.5M4 4l2.5 2.5M17.5 17.5 20 20m0 0 1.5-1.5M20 20l-2.5-2.5M7 12h10M4.5 9v6M2 10.5v3M19.5 9v6M22 10.5v3M8 8l8 8"/></svg>
                        {{ $rutina->totalEjercicios() }} {{ $rutina->totalEjercicios() === 1 ? 'ejercicio' : 'ejercicios' }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                <a href="{{ route('entrenamientos.entrenar', $rutina) }}"
                    class="alpha-btn-primary px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1 shadow-sm">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Entrenar
                </a>

                <div class="flex items-center gap-2">
                    @if($guard === 'web' && \App\Support\Acceso::permite(auth('web')->user(), 'asignar_rutinas'))
                        <button type="button" onclick="abrirModalAsignar({{ $rutina->id }}, {{ json_encode($rutina->nombre) }})"
                            title="Asignar a un cliente"
                            class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-gray-300 hover:text-yellow-400 border border-white/10 transition-colors">
                            Asignar
                        </button>
                    @endif

                    <a href="{{ route('entrenamientos.imprimir', $rutina) }}" target="_blank"
                        title="Imprimir / Guardar en PDF"
                        class="p-1.5 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 border border-white/10 transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    </a>

                    <a href="{{ route('entrenamientos.editar', $rutina) }}"
                        class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white border border-white/10 transition-colors">
                        Editar
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full alpha-card rounded-2xl p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 6.5 4 4M4 4l-1.5 1.5M4 4l2.5 2.5M17.5 17.5 20 20m0 0 1.5-1.5M20 20l-2.5-2.5M7 12h10M4.5 9v6M2 10.5v3M19.5 9v6M22 10.5v3M8 8l8 8"/></svg>
            </div>
            <h3 class="text-base font-bold text-white mb-1">Aún no tienes rutinas creadas</h3>
            <p class="text-gray-400 text-xs mb-4">Haz clic en Crear nueva rutina para estructurar tu plan de entrenamiento.</p>
            <form method="POST" action="{{ route('entrenamientos.crear') }}" class="inline-block">
                @csrf
                <button type="submit" class="alpha-btn-primary px-4 py-2 text-xs font-semibold">Comenzar ahora</button>
            </form>
        </div>
    @endforelse
</div>
<div class="mt-5">{{ $rutinas->links() }}</div>

@if($guard === 'web' && \App\Support\Acceso::permite(auth('web')->user(), 'asignar_rutinas'))
{{-- MODAL ASIGNAR RUTINA A CLIENTE --}}
<div id="modal-asignar-rutina" aria-labelledby="titulo-asignar-rutina" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 hidden">
    <div class="alpha-card bg-[#141416] border border-white/10 rounded-2xl w-full max-w-md p-6 shadow-2xl relative">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
            <h3 id="titulo-asignar-rutina" class="text-base font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                Asignar Rutina a Miembro
            </h3>
            <button type="button" onclick="cerrarModalAsignar()" aria-label="Cerrar asignación de rutina" class="text-gray-400 hover:text-white text-lg font-bold">&times;</button>
        </div>
        <form id="form-asignar-rutina" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <p class="text-xs text-gray-400 mb-3">Se creará una copia personalizada de <strong id="nombre-rutina-asignar" class="text-yellow-400"></strong> en la cuenta del cliente seleccionado.</p>
                <label for="asignar-cliente" class="block text-xs font-semibold text-gray-300 mb-1">Seleccionar Cliente *</label>
                <select id="asignar-cliente" name="cliente_id" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:border-yellow-400/60 outline-none">
                    <option value="">-- Elige un cliente --</option>
                    @foreach($clientes as $cli)
                        <option value="{{ $cli->id }}">{{ $cli->nombre }} · #{{ $cli->id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                <button type="button" onclick="cerrarModalAsignar()" class="alpha-btn-secondary px-4 py-2 text-xs font-semibold">Cancelar</button>
                <button type="submit" class="alpha-btn-primary px-5 py-2 text-xs font-semibold shadow-md shadow-yellow-400/20">Asignar Rutina</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalAsignar(rutinaId, nombre) {
        document.getElementById('form-asignar-rutina').action = `/entrenamientos/${rutinaId}/asignar`;
        document.getElementById('nombre-rutina-asignar').textContent = `"${nombre}"`;
        window.alphaAnimateModalOpen('#modal-asignar-rutina');
    }

    function cerrarModalAsignar() {
        window.alphaAnimateModalClose('#modal-asignar-rutina');
    }
</script>
@endif
@endsection

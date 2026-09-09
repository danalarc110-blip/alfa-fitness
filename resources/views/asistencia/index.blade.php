@extends('layouts.app', ['active' => 'asistencia'])
@section('title', 'Asistencia')
@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Asistencia</h1>
                    <p class="text-gray-400 text-xs mt-1">Control de entrada y salida de clientes.</p>
                </div>

                <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
            </header>
@endsection
@section('content')
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5" data-animate="card">
                <div class="alpha-card rounded-2xl p-5 border border-white/10">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Entradas hoy</span>
                    <p class="text-4xl font-bold text-yellow-400 mt-2">{{ $hoy }}</p>
                    <p class="text-xs text-gray-400 mt-1">Clientes registrados hoy</p>
                </div>

                <div class="alpha-card rounded-2xl p-5 border border-white/10">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dentro ahora</span>
                    <p class="text-4xl font-bold text-white mt-2">{{ $dentro }}</p>
                    <p class="text-xs text-gray-400 mt-1">Entradas sin salida</p>
                </div>

                <div class="alpha-card rounded-2xl p-5 border border-white/10 sm:col-span-2">
                    <form method="GET" action="{{ route('asistencia.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <label class="block flex-1">
                            <span class="block text-xs font-semibold text-gray-400 mb-1.5">Buscar cliente</span>
                            <input type="text" name="q" value="{{ $busqueda }}" placeholder="Nombre o correo"
                                class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                        </label>

                        <button type="submit" class="alpha-btn-primary rounded-xl px-5 py-2.5 text-sm font-semibold">
                            Buscar
                        </button>
                    </form>
                </div>
            </section>

            <section class="alpha-card rounded-2xl p-5 mb-5" data-animate="card">
                <h2 class="text-base font-bold text-white mb-3">Dentro del gimnasio</h2>
                <div class="flex flex-wrap gap-2">@forelse($dentroAhora as $visita)<span class="px-3 py-2 rounded-xl bg-green-400/10 border border-green-400/20 text-sm text-green-200">{{ $visita->cliente->nombre }} · desde {{ $visita->fecha_hora->format('H:i') }}</span>@empty<span class="text-sm text-gray-400">No hay visitas abiertas.</span>@endforelse</div>
            </section>

            <form method="GET" action="{{ route('asistencia.index') }}" class="alpha-card alpha-form rounded-2xl p-5 mb-5 grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <label>Cliente<input name="q" value="{{ $busqueda }}" placeholder="Nombre o correo"></label>
                <label>Desde<input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"></label>
                <label>Hasta<input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></label>
                <label>Estado<select name="estado"><option value="">Todas</option><option value="abierta" @selected(($filtros['estado'] ?? '') === 'abierta')>Abiertas</option><option value="cerrada" @selected(($filtros['estado'] ?? '') === 'cerrada')>Cerradas</option></select></label>
                <div class="flex items-end gap-2"><button class="alpha-btn-primary px-4 py-3 rounded-xl">Filtrar historial</button><a href="{{ route('asistencia.index') }}" class="text-sm py-3">Limpiar</a></div>
            </form>

            <section class="grid grid-cols-1 xl:grid-cols-[1fr_430px] gap-5">
                <div class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0" data-animate="card">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-base font-bold text-white">Clientes</h2>
                        <span class="text-xs font-semibold text-gray-500">{{ $clientes->count() }} visibles</span>
                    </div>

                    @if ($clientes->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($clientes as $cliente)
                                @php
                                    $entradaAbierta = $cliente->asistencias->first();
                                    $membresia = $cliente->membresias->first();
                                    $estadoMembresia = $membresia?->estado ?? 'Sin membresia';
                                @endphp
                                <div class="bg-black/40 border border-white/10 rounded-xl p-4 flex flex-col gap-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-bold text-white truncate">{{ $cliente->nombre }}</h3>
                                            <p class="text-[11px] text-gray-500 truncate">{{ $cliente->correo }}</p>
                                            <p class="text-[11px] {{ $estadoMembresia === 'Vigente' ? 'text-yellow-400' : 'text-red-300' }}">Membresia: {{ $estadoMembresia }}</p>
                                        </div>

                                        <span class="shrink-0 inline-flex px-2 py-1 rounded-lg text-[11px] font-bold {{ $entradaAbierta ? 'bg-green-400/10 text-green-300 border border-green-400/20' : 'bg-white/5 text-gray-400 border border-white/10' }}">
                                            {{ $entradaAbierta ? 'Dentro' : 'Fuera' }}
                                        </span>
                                    </div>

                                    @if ($entradaAbierta)
                                        <div class="text-xs text-gray-400">
                                            Entrada: <span class="font-semibold text-gray-200">{{ $entradaAbierta->fecha_hora->format('H:i') }}</span>
                                        </div>

                                        <form method="POST" action="{{ route('asistencia.salida') }}">
                                            @csrf
                                            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                                            <button type="submit" class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold bg-red-500/10 text-red-300 border border-red-400/20 hover:bg-red-500/20 transition-colors">
                                                Registrar salida
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('asistencia.store') }}">
                                            @csrf
                                            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                                            <button type="submit" class="alpha-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-semibold">
                                                Registrar entrada
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <h3 class="text-base font-bold text-white mb-1">Sin clientes encontrados</h3>
                            <p class="text-xs text-gray-400">Ajusta la búsqueda para registrar entradas o salidas.</p>
                        </div>
                    @endif
                </div>

                <aside class="alpha-card rounded-2xl p-5 sm:p-6 h-fit min-w-0" data-animate="card">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-base font-bold text-white">Historial mensual</h2>
                        <span class="text-xs font-semibold text-gray-500">{{ now()->format('m/Y') }}</span>
                    </div>

                    @if ($asistencias->isNotEmpty())
                        <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-1 custom-scroll">
                            @foreach ($asistencias as $asistencia)
                                <div class="bg-black/40 border border-white/10 rounded-xl p-3">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-white truncate">{{ $asistencia->cliente->nombre }}</p>
                                            <p class="text-[11px] text-gray-500">{{ $asistencia->fecha_hora->format('d/m/Y') }}</p>
                                        </div>
                                        <span class="shrink-0 inline-flex px-2 py-1 rounded-lg text-[11px] font-bold {{ $asistencia->fecha_salida ? 'bg-white/5 text-gray-300 border border-white/10' : 'bg-green-400/10 text-green-300 border border-green-400/20' }}">
                                            {{ $asistencia->fecha_salida ? 'Cerrada' : 'Abierta' }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div class="rounded-lg bg-white/[0.03] px-3 py-2">
                                            <span class="block text-gray-500">Entrada</span>
                                            <strong class="text-gray-200">{{ $asistencia->fecha_hora->format('H:i') }}</strong>
                                        </div>
                                        <div class="rounded-lg bg-white/[0.03] px-3 py-2">
                                            <span class="block text-gray-500">Salida</span>
                                            <strong class="text-gray-200">{{ $asistencia->fecha_salida?->format('H:i') ?? 'Pendiente' }}</strong>
                                        </div>
                                    </div>

                                    <p class="text-[11px] text-gray-500 mt-2">Duracion: {{ $asistencia->duracion ?? 'En curso' }}</p>
                                    <p class="text-[11px] text-gray-500 mt-1">Entrada por: {{ $asistencia->registrador?->name ?? 'Registro anterior' }} · Salida por: {{ $asistencia->registradorSalida?->name ?? ($asistencia->fecha_salida ? 'Registro anterior' : 'Pendiente') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center">
                            <p class="text-sm text-gray-400">No hay asistencias registradas este mes.</p>
                        </div>
                    @endif
                </aside>
            </section>
<div class="mt-6 space-y-4"><div><p class="text-xs text-gray-400 mb-2">Clientes</p>{{ $clientes->links() }}</div><div><p class="text-xs text-gray-400 mb-2">Historial de visitas</p>{{ $asistencias->links() }}</div></div>
@endsection

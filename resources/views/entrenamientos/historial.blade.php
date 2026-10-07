@extends('layouts.app', ['active' => 'entrenamientos'])
@section('title', 'Historial de Entrenamientos')

@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('entrenamientos.index') }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all shadow-sm" title="Volver a entrenamientos">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Historial de Entrenamientos</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                    Registro de Sesiones
                </span>
            </div>
            <p class="text-gray-400 text-xs mt-1">Bitácora de rutinas ejecutadas y tiempo dedicado en el gimnasio.</p>
        </div>
    </div>

    <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    </button>
</header>
@endsection

@section('content')
{{-- MÉTRICAS RESUMEN --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 sm:mb-8" data-animate="card">
    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sesiones Completadas</span>
        <p class="text-2xl sm:text-3xl font-black text-yellow-400 mt-2">{{ $totalSesiones }}</p>
        <p class="text-[11px] text-gray-500 mt-1">Entrenamientos registrados</p>
    </div>

    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tiempo Invertido</span>
        <p class="text-2xl sm:text-3xl font-black text-white mt-2">
            @if($totalMinutos >= 60)
                {{ number_format($totalMinutos / 60, 1) }} <span class="text-base text-gray-400 font-normal">hrs</span>
            @else
                {{ $totalMinutos }} <span class="text-base text-gray-400 font-normal">min</span>
            @endif
        </p>
        <p class="text-[11px] text-gray-500 mt-1">Dedicación activa acumulada</p>
    </div>

    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Series Ejecutadas</span>
        <p class="text-2xl sm:text-3xl font-black text-yellow-400 mt-2">{{ number_format($totalSeries) }}</p>
        <p class="text-[11px] text-gray-500 mt-1">Series de ejercicios completadas</p>
    </div>
</div>

{{-- LISTADO DE SESIONES --}}
<section class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
    <div class="flex items-center justify-between pb-4 mb-4 border-b border-white/5">
        <h2 class="text-base font-bold text-white">Sesiones Recientes</h2>
        <span class="text-xs text-gray-400">{{ $sesiones->total() }} registradas</span>
    </div>

    @if($sesiones->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 border-b border-white/10">
                        <th class="py-2.5 pr-3 font-semibold">Rutina / Día</th>
                        <th class="py-2.5 px-3 font-semibold text-center">Duración</th>
                        <th class="py-2.5 px-3 font-semibold text-center">Series</th>
                        <th class="py-2.5 px-3 font-semibold text-center">Progreso</th>
                        <th class="py-2.5 px-3 font-semibold">Fecha y Hora</th>
                        <th class="py-2.5 pl-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sesiones as $s)
                        <tr class="border-b border-white/5 last:border-0 hover:bg-white/[0.02]">
                            <td class="py-3 pr-3">
                                <div>
                                    <p class="font-bold text-white">{{ $s->rutina_nombre }}</p>
                                    <p class="text-xs text-yellow-400/90 flex items-center gap-1 mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                                        {{ $s->dia_titulo }}
                                    </p>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center font-mono text-gray-300">
                                {{ $s->duracion_formateada }}
                            </td>
                            <td class="py-3 px-3 text-center text-gray-300">
                                {{ $s->series_completadas }} / {{ $s->total_series }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @php($pct = $s->porcentaje_completado)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold {{ $pct >= 100 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-yellow-400/10 text-yellow-400 border border-yellow-400/20' }}">
                                    {{ $pct }}%
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-400 text-xs">
                                {{ $s->finalizado_en ? $s->finalizado_en->format('d/m/Y H:i') : $s->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 pl-3 text-right">
                                @if($s->rutina_id)
                                    <a href="{{ route('entrenamientos.entrenar', ['rutina' => $s->rutina_id, 'dia' => $s->dia_id]) }}"
                                        class="alpha-btn-secondary px-3 py-1.5 rounded-xl text-xs font-semibold inline-flex items-center gap-1">
                                        <svg class="w-3 h-3 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        Repetir
                                    </a>
                                @else
                                    <span class="text-xs text-gray-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $sesiones->links() }}</div>
    @else
        <div class="py-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-3 text-gray-400">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <h3 class="text-sm font-bold text-white mb-1">Aún no tienes sesiones registradas</h3>
            <p class="text-xs text-gray-400 mb-4">Inicia una rutina con "Entrenar Ahora" y haz clic en finalizar al terminar tus series.</p>
            <a href="{{ route('entrenamientos.index') }}" class="alpha-btn-primary px-4 py-2 rounded-xl text-xs font-semibold inline-block">
                Ver mis rutinas
            </a>
        </div>
    @endif
</section>
@endsection

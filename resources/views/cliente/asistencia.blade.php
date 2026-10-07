@extends('layouts.app', ['active' => 'asistencia'])

@section('title', 'Mi Asistencia')
@section('eyebrow', 'Registro de Accesos')

@section('content')
<section class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4" data-animate="header">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Mi Historial de Asistencia</h1>
            <p class="text-sm text-gray-400 mt-1">Consulta tus ingresos y permanencia en las instalaciones de Alpha Fitness.</p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $visitaActual ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : 'bg-white/5 text-gray-400 border border-white/10' }}">
                <span class="w-2 h-2 rounded-full {{ $visitaActual ? 'bg-emerald-400 animate-pulse' : 'bg-gray-500' }}"></span>
                {{ $visitaActual ? 'Actualmente en sala' : 'Fuera del club' }}
            </span>
        </div>
    </div>

    {{-- TARJETAS DE RESUMEN --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" data-animate="card">
        <div class="alpha-card rounded-2xl p-5 border border-white/10">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Visitas</span>
                <div class="w-8 h-8 rounded-lg bg-yellow-400/10 text-yellow-400 flex items-center justify-center">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-5"/></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-white mt-3">{{ $totalVisitas }}</p>
            <p class="text-xs text-gray-500 mt-1">Sesiones de entrenamiento registradas</p>
        </div>

        <div class="alpha-card rounded-2xl p-5 border border-white/10">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Estado Actual</span>
                <div class="w-8 h-8 rounded-lg {{ $visitaActual ? 'bg-emerald-400/10 text-emerald-400' : 'bg-white/5 text-gray-400' }} flex items-center justify-center">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                </div>
            </div>
            @if ($visitaActual)
                <p class="text-xl font-bold text-emerald-300 mt-3">En el gimnasio</p>
                <p class="text-xs text-gray-400 mt-1">Ingreso: {{ $visitaActual->fecha_hora->format('H:i') }} hrs</p>
            @else
                <p class="text-xl font-bold text-gray-300 mt-3">Sin sesión activa</p>
                <p class="text-xs text-gray-500 mt-1">Registra tu entrada en recepción</p>
            @endif
        </div>

        <div class="alpha-card rounded-2xl p-5 border border-white/10 sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Miembro</span>
                <div class="w-8 h-8 rounded-lg bg-yellow-400/10 text-yellow-400 flex items-center justify-center">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
            </div>
            <p class="text-lg font-bold text-white mt-3 truncate">{{ $cliente->nombre }}</p>
            <p class="text-xs text-gray-400 mt-1 truncate">{{ $cliente->correo }}</p>
        </div>
    </div>

    {{-- LISTADO DE ASISTENCIAS --}}
    <div class="alpha-card rounded-2xl border border-white/10 overflow-hidden" data-animate="card">
        <div class="p-5 border-b border-white/10 flex items-center justify-between">
            <h2 class="font-bold text-white text-base">Historial detallado</h2>
            <span class="text-xs text-gray-400">{{ $asistencias->total() }} registros</span>
        </div>

        @if ($asistencias->isEmpty())
            <div class="p-10 text-center">
                <div class="w-12 h-12 rounded-2xl bg-white/5 text-gray-500 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-5"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-white">Aún no tienes registros de asistencia</h3>
                <p class="text-xs text-gray-400 mt-1">Tus visitas aparecerán aquí cada vez que la recepción confirme tu ingreso.</p>
            </div>
        @else
            {{-- TABLA DESKTOP --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-white/5 text-gray-400 uppercase tracking-wider text-[11px] border-b border-white/5">
                        <tr>
                            <th class="py-3 px-5">Fecha</th>
                            <th class="py-3 px-5">Hora de Entrada</th>
                            <th class="py-3 px-5">Hora de Salida</th>
                            <th class="py-3 px-5">Permanencia</th>
                            <th class="py-3 px-5">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-300">
                        @foreach ($asistencias as $asistencia)
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3.5 px-5 font-medium text-white">
                                    {{ $asistencia->fecha_hora->format('d/m/Y') }}
                                </td>
                                <td class="py-3.5 px-5">
                                    {{ $asistencia->fecha_hora->format('H:i') }} hrs
                                </td>
                                <td class="py-3.5 px-5">
                                    {{ $asistencia->fecha_salida ? $asistencia->fecha_salida->format('H:i') . ' hrs' : '—' }}
                                </td>
                                <td class="py-3.5 px-5">
                                    {{ $asistencia->duracion ?: ($asistencia->fecha_salida ? 'Menos de 1m' : 'En curso') }}
                                </td>
                                <td class="py-3.5 px-5">
                                    @if ($asistencia->fecha_salida)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/5 text-gray-400 border border-white/10">
                                            Completada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                                            ● En sala
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- TARJETAS MÓVILES (< md) --}}
            <div class="md:hidden divide-y divide-white/5">
                @foreach ($asistencias as $asistencia)
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-white text-sm">{{ $asistencia->fecha_hora->format('d/m/Y') }}</span>
                            @if ($asistencia->fecha_salida)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/5 text-gray-400">
                                    Completada
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-300">
                                    ● En sala
                                </span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-400 pt-1">
                            <div>
                                <span class="block text-[10px] text-gray-500 uppercase">Entrada</span>
                                <span class="text-white">{{ $asistencia->fecha_hora->format('H:i') }} hrs</span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-gray-500 uppercase">Salida</span>
                                <span class="text-white">{{ $asistencia->fecha_salida ? $asistencia->fecha_salida->format('H:i') . ' hrs' : 'En curso' }}</span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 pt-1">
                            <span class="text-gray-500">Duración:</span>
                            <span class="text-yellow-400 font-medium">{{ $asistencia->duracion ?: ($asistencia->fecha_salida ? 'Menos de 1m' : 'Entrenando actualmente') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($asistencias->hasPages())
                <div class="p-4 border-t border-white/10">
                    {{ $asistencias->links() }}
                </div>
            @endif
        @endif
    </div>
</section>
@endsection

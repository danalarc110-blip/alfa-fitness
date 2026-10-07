@extends('layouts.app', ['active' => 'entrenamientos'])
@section('title', 'Progreso de ' . $cliente->nombre)

@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
    <div class="flex items-center gap-3">
        <a href="{{ route('entrenamientos.index') }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white transition-all shadow-sm" title="Volver a Entrenamientos">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Progreso: {{ $cliente->nombre }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                    Seguimiento Atleta
                </span>
            </div>
            <p class="text-gray-400 text-xs mt-1">{{ $cliente->correo }} &middot; Marcas personales y evolución registrada</p>
        </div>
    </div>

    <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    </button>
</header>
@endsection

@section('content')
{{-- MÉTRICAS RESUMEN DE FUERZA --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6 sm:mb-8" data-animate="card">
    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Volumen Acumulado</span>
        <p class="text-2xl sm:text-3xl font-black text-yellow-400 mt-2">{{ number_format($totalVolumenKg, 0) }} <span class="text-base text-gray-400 font-normal">kg</span></p>
        <p class="text-[11px] text-gray-500 mt-1">Tonelaje total movido en PRs</p>
    </div>

    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Levantamiento Máximo</span>
        <p class="text-2xl sm:text-3xl font-black text-white mt-2">{{ $maximoRecord ? $maximoRecord->peso_formateado : '---' }}</p>
        <p class="text-[11px] text-gray-500 mt-1 truncate">{{ $maximoRecord ? $maximoRecord->ejercicio->nombre : 'Sin registros aún' }}</p>
    </div>

    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Récords Registrados</span>
        <p class="text-2xl sm:text-3xl font-black text-yellow-400 mt-2">{{ $records->total() }}</p>
        <p class="text-[11px] text-gray-500 mt-1">Hitos personales superados</p>
    </div>

    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rango de Atleta</span>
        <p class="text-2xl sm:text-3xl font-black text-white mt-2">
            @if($records->total() >= 15)
                Élite 👑
            @elseif($records->total() >= 8)
                Avanzado 🥇
            @elseif($records->total() >= 3)
                Intermedio 🥈
            @else
                Iniciado 🥉
            @endif
        </p>
        <p class="text-[11px] text-gray-500 mt-1">Nivel de desarrollo de fuerza</p>
    </div>
</div>

{{-- GRÁFICO DE EVOLUCIÓN EN SVG (SI HAY REGISTROS) --}}
@if($ultimosLevantamientos->count() >= 2)
    <div class="alpha-card rounded-2xl p-5 sm:p-6 border border-white/10 mb-6" data-animate="card">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/></svg>
                    Evolución de Fuerza {{ $ejercicioSeleccionadoObj ? 'en ' . $ejercicioSeleccionadoObj->nombre : 'en Sesiones Recientes' }}
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">Tendencia de peso levantado (kg) por el atleta</p>
            </div>
            <span class="text-xs font-bold text-yellow-400 bg-yellow-400/10 px-2.5 py-1 rounded-lg border border-yellow-400/20">
                Últimos {{ $ultimosLevantamientos->count() }} levantamientos
            </span>
        </div>

        @php
            $count = $ultimosLevantamientos->count();
            $maxWeight = max(1, $ultimosLevantamientos->max('peso_kg'));
            $minWeight = max(0, $ultimosLevantamientos->min('peso_kg') * 0.7);
            $range = max(1, $maxWeight - $minWeight);
            $chartHeight = 160;
            $chartWidth = 800;
            $points = [];
            foreach ($ultimosLevantamientos as $i => $rec) {
                $x = ($count === 1) ? $chartWidth / 2 : ($i / ($count - 1)) * ($chartWidth - 60) + 30;
                $y = $chartHeight - 30 - (($rec->peso_kg - $minWeight) / $range) * ($chartHeight - 60);
                $points[] = ['x' => $x, 'y' => $y, 'peso' => $rec->peso_kg, 'nombre' => $rec->ejercicio->nombre, 'fecha' => $rec->created_at->format('d/m')];
            }
            $polylinePoints = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $points));
        @endphp

        <div class="w-full overflow-x-auto">
            <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full h-44 text-yellow-400 select-none">
                <defs>
                    <linearGradient id="gradiente-linea-coach" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#eab308" />
                        <stop offset="100%" stop-color="#facc15" />
                    </linearGradient>
                    <linearGradient id="gradiente-area-coach" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#facc15" stop-opacity="0.3" />
                        <stop offset="100%" stop-color="#facc15" stop-opacity="0.0" />
                    </linearGradient>
                </defs>

                <line x1="30" y1="20" x2="{{ $chartWidth - 30 }}" y2="20" stroke="rgba(255,255,255,0.06)" stroke-dasharray="4" />
                <line x1="30" y1="{{ $chartHeight / 2 }}" x2="{{ $chartWidth - 30 }}" y2="{{ $chartHeight / 2 }}" stroke="rgba(255,255,255,0.06)" stroke-dasharray="4" />
                <line x1="30" y1="{{ $chartHeight - 30 }}" x2="{{ $chartWidth - 30 }}" y2="{{ $chartHeight - 30 }}" stroke="rgba(255,255,255,0.1)" />

                <polygon points="30,{{ $chartHeight - 30 }} {{ $polylinePoints }} {{ $points[count($points) - 1]['x'] }},{{ $chartHeight - 30 }}" fill="url(#gradiente-area-coach)" />

                <polyline fill="none" stroke="url(#gradiente-linea-coach)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" points="{{ $polylinePoints }}" />

                @foreach($points as $pt)
                    <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="5" fill="#facc15" stroke="#111" stroke-width="2" class="cursor-pointer transition-all hover:scale-150" />
                    <text x="{{ $pt['x'] }}" y="{{ $pt['y'] - 10 }}" text-anchor="middle" fill="#facc15" font-size="11" font-weight="bold">{{ $pt['peso'] }}kg</text>
                    <text x="{{ $pt['x'] }}" y="{{ $chartHeight - 12 }}" text-anchor="middle" fill="#71717a" font-size="10">{{ $pt['fecha'] }}</text>
                @endforeach
            </svg>
        </div>
    </div>
@endif

{{-- MEJORES MARCAS / PODIO --}}
<div class="mb-6">
    <h2 class="text-base font-bold text-white mb-3">Mejores Marcas del Atleta</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" data-animate="card">
        @forelse ($mejoresMarcas as $index => $record)
            <div class="alpha-card rounded-2xl p-4 border border-white/10 relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold {{ $index === 0 ? 'bg-yellow-400 text-black' : ($index === 1 ? 'bg-gray-300 text-black' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-white/5 text-gray-400')) }}">
                        #{{ $index + 1 }} Top PR
                    </span>
                    <span class="text-[11px] font-semibold text-gray-400">{{ $record->ejercicio->grupo_muscular }}</span>
                </div>

                <div class="flex items-center gap-3 my-2">
                    <div class="w-12 h-12 rounded-xl bg-black/60 border border-white/10 overflow-hidden shrink-0 flex items-center justify-center">
                        @if ($record->ejercicio->tiene_imagen)
                            <img src="{{ $record->ejercicio->imagen_url }}" alt="{{ $record->ejercicio->nombre }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M12 8v8"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-white truncate">{{ $record->ejercicio->nombre }}</h3>
                        <p class="text-xs font-black text-yellow-400">{{ $record->peso_formateado }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400">{{ $record->repeticiones }} reps &middot; {{ $record->nivel }}</p>
            </div>
        @empty
            <div class="sm:col-span-2 xl:col-span-4 alpha-card rounded-2xl p-6 text-center">
                <p class="text-xs text-gray-400">El atleta todavía no ha registrado marcas personales.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- HISTORIAL COMPLETO DE REGISTROS --}}
<section class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-white/5">
        <div>
            <h2 class="text-base font-bold text-white">Historial de levantamientos</h2>
            <span class="text-xs font-semibold text-gray-500">{{ $records->total() }} registros{{ $ejercicioSeleccionadoObj ? ' para ' . $ejercicioSeleccionadoObj->nombre : '' }}</span>
        </div>

        <form method="GET" action="{{ route('entrenador.cliente.progreso', $cliente) }}" class="flex items-center gap-2">
            <select name="ejercicio_id" onchange="this.form.submit()" class="bg-black/60 border border-white/10 rounded-xl px-3 py-1.5 text-xs text-white outline-none focus:border-yellow-400/60">
                <option value="">Todos los ejercicios</option>
                @foreach ($ejercicios as $ej)
                    <option value="{{ $ej->id }}" @selected(($ejercicioFiltro ?? '') == $ej->id)>
                        {{ $ej->nombre }}
                    </option>
                @endforeach
            </select>
            @if($ejercicioFiltro)
                <a href="{{ route('entrenador.cliente.progreso', $cliente) }}" class="alpha-btn-secondary px-2.5 py-1.5 rounded-xl text-xs">Limpiar</a>
            @endif
        </form>
    </div>

    @if ($records->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 border-b border-white/10">
                        <th class="py-2.5 pr-3 font-semibold">Ejercicio</th>
                        <th class="py-2.5 px-3 font-semibold text-center">Peso</th>
                        <th class="py-2.5 px-3 font-semibold text-center">Reps</th>
                        <th class="py-2.5 px-3 font-semibold text-center">Nivel</th>
                        <th class="py-2.5 px-3 font-semibold">Notas</th>
                        <th class="py-2.5 pl-3 font-semibold text-right">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr class="border-b border-white/5 last:border-0 hover:bg-white/[0.02]">
                            <td class="py-3 pr-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-black/60 border border-white/10 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if ($record->ejercicio->tiene_imagen)
                                            <img src="{{ $record->ejercicio->imagen_url }}" alt="{{ $record->ejercicio->nombre }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M12 8v8"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-white truncate">{{ $record->ejercicio->nombre }}</p>
                                        <p class="text-[11px] text-gray-500 truncate">{{ $record->ejercicio->grupo_muscular }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-yellow-400">{{ $record->peso_formateado }}</td>
                            <td class="py-3 px-3 text-center text-gray-300">{{ $record->repeticiones }}</td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded-lg text-[11px] font-bold bg-white/5 text-gray-300 border border-white/10">{{ $record->nivel }}</span>
                            </td>
                            <td class="py-3 px-3 text-gray-400 text-xs">{{ $record->notas ?: '—' }}</td>
                            <td class="py-3 pl-3 text-right text-gray-400 text-xs">{{ $record->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    @else
        <div class="py-8 text-center">
            <p class="text-sm text-gray-400">No hay levantamientos registrados para este atleta con los filtros seleccionados.</p>
        </div>
    @endif
</section>
@endsection

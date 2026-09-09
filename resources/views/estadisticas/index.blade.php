@extends('layouts.app', ['active' => 'estadisticas'])
@section('title', 'Estadísticas')
@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">PR / Estadísticas</h1>
                    <p class="text-gray-400 text-xs mt-1">Resumen derivado de los levantamientos registrados en Progreso.</p>
                </div>

                <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
            </header>
@endsection
@section('content')
@if ($guard === 'web')
                <form method="GET" action="{{ route('estadisticas.index') }}" class="mb-5 flex flex-col sm:flex-row sm:items-end gap-3" data-animate="card">
                    <label class="block w-full sm:max-w-xs">
                        <span class="block text-xs font-semibold text-gray-400 mb-1.5">Cliente</span>
                        <select name="cliente_id" class="w-full bg-[#141414] border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60" onchange="this.form.submit()">
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected($clienteSeleccionado === $cliente->id)>{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>
            @endif

            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5" data-animate="card">
                <div class="alpha-card rounded-2xl p-5 border border-white/10">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Registros</span>
                    <p class="text-3xl font-bold text-white mt-2">{{ $records->count() }}</p>
                    <p class="text-xs text-gray-400 mt-1">Levantamientos guardados</p>
                </div>

                <div class="alpha-card rounded-2xl p-5 border border-white/10">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mayor peso</span>
                    <p class="text-3xl font-bold text-yellow-400 mt-2">{{ $mejorPeso ? $mejorPeso->peso_formateado : '0 kg' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $mejorPeso?->ejercicio?->nombre ?? 'Sin datos' }}</p>
                </div>

                <div class="alpha-card rounded-2xl p-5 border border-white/10">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mejor volumen</span>
                    <p class="text-3xl font-bold text-yellow-400 mt-2">{{ $mejorVolumen ? number_format($mejorVolumen->volumen, 0) : '0' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $mejorVolumen ? $mejorVolumen->peso_formateado.' x '.$mejorVolumen->repeticiones : 'Sin datos' }}</p>
                </div>

                <div class="alpha-card rounded-2xl p-5 border border-white/10">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ejercicios</span>
                    <p class="text-3xl font-bold text-white mt-2">{{ $porEjercicio->count() }}</p>
                    <p class="text-xs text-gray-400 mt-1">Con progreso registrado</p>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-5">
                <div class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0" data-animate="card">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-base font-bold text-white">Mejores marcas por ejercicio</h2>
                        <a href="{{ route('progreso.index', $guard === 'web' ? ['cliente_id' => $clienteSeleccionado] : []) }}" class="text-xs font-semibold text-yellow-400 hover:text-yellow-300">
                            Registrar más
                        </a>
                    </div>

                    @if ($porEjercicio->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[680px] text-sm">
                                <thead>
                                    <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 border-b border-white/10">
                                        <th class="py-2.5 pr-3 font-semibold">Ejercicio</th>
                                        <th class="py-2.5 px-3 font-semibold text-center">Mejor peso</th>
                                        <th class="py-2.5 px-3 font-semibold text-center">Reps</th>
                                        <th class="py-2.5 px-3 font-semibold text-center">Nivel</th>
                                        <th class="py-2.5 px-3 font-semibold text-center">Cambio</th>
                                        <th class="py-2.5 pl-3 font-semibold text-center">Registros</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($porEjercicio as $item)
                                        <tr class="border-b border-white/5 last:border-0 hover:bg-white/[0.02]">
                                            <td class="py-3 pr-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-black/60 border border-white/10 overflow-hidden shrink-0 flex items-center justify-center">
                                                        @if ($item['ejercicio']->tiene_imagen)
                                                            <img src="{{ $item['ejercicio']->imagen_url }}" alt="{{ $item['ejercicio']->nombre }}" class="w-full h-full object-cover">
                                                        @else
                                                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M12 8v8"/></svg>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-white truncate">{{ $item['ejercicio']->nombre }}</p>
                                                        <p class="text-[11px] text-gray-500 truncate">{{ $item['ejercicio']->grupo_muscular }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center font-bold text-yellow-400">{{ $item['mejor']->peso_formateado }}</td>
                                            <td class="py-3 px-3 text-center text-gray-300">{{ $item['mejor']->repeticiones }}</td>
                                            <td class="py-3 px-3 text-center">
                                                <span class="inline-flex px-2 py-1 rounded-lg text-[11px] font-bold bg-white/5 text-gray-300 border border-white/10">{{ $item['mejor']->nivel }}</span>
                                            </td>
                                            <td class="py-3 px-3 text-center {{ $item['diferencia_kg'] >= 0 ? 'text-green-400' : 'text-red-300' }}">
                                                {{ $item['diferencia_kg'] >= 0 ? '+' : '' }}{{ number_format($item['diferencia_kg'], 1) }} kg
                                            </td>
                                            <td class="py-3 pl-3 text-center text-gray-400">{{ $item['registros'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <h3 class="text-base font-bold text-white mb-1">Todavía no hay estadísticas</h3>
                            <p class="text-xs text-gray-400">Registra levantamientos en Progreso para ver tus PR.</p>
                        </div>
                    @endif
                </div>

                <aside class="alpha-card rounded-2xl p-5 sm:p-6 h-fit" data-animate="card">
                    <h2 class="text-base font-bold text-white mb-4">Actividad reciente</h2>

                    @forelse ($ultimos as $record)
                        <div class="flex items-center justify-between gap-3 py-3 border-b border-white/5 last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $record->ejercicio->nombre }}</p>
                                <p class="text-[11px] text-gray-500">{{ $record->created_at->format('d/m/Y') }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-yellow-400">{{ $record->peso_formateado }}</p>
                                <p class="text-[11px] text-gray-400">{{ $record->repeticiones }} reps</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">Sin actividad reciente.</p>
                    @endforelse
                </aside>
            </section>
@endsection

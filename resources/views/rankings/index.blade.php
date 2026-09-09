@extends('layouts.app', ['active' => 'rankings'])
@section('title', 'Rankings')
@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Rankings</h1>
                    <p class="text-gray-400 text-xs mt-1">Tabla provisional basada en las mejores marcas registradas.</p>
                </div>

                <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
            </header>
@endsection
@section('content')
<form method="GET" action="{{ route('rankings.index') }}" class="mb-5 flex flex-col sm:flex-row sm:items-end gap-3" data-animate="card">
                <label class="block w-full sm:max-w-xs">
                    <span class="block text-xs font-semibold text-gray-400 mb-1.5">Ejercicio</span>
                    <select name="ejercicio_id" class="w-full bg-[#141414] border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60" onchange="this.form.submit()">
                        <option value="">Todos los ejercicios</option>
                        @foreach ($ejercicios as $ejercicio)
                            <option value="{{ $ejercicio->id }}" @selected($ejercicioSeleccionado === $ejercicio->id)>{{ $ejercicio->nombre }}</option>
                        @endforeach
                    </select>
                </label>
            </form>

            @if ($misPosiciones->isNotEmpty())
                <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-5" data-animate="card">
                    @foreach ($misPosiciones->take(3) as $record)
                        <div class="alpha-card rounded-2xl p-5 border border-yellow-400/20 bg-yellow-400/[0.03]">
                            <span class="text-xs font-semibold text-yellow-400 uppercase tracking-wider">Mi posición</span>
                            <p class="text-lg font-bold text-white mt-2 truncate">{{ $record->ejercicio->nombre }}</p>
                            <p class="text-sm text-gray-300 mt-1">{{ $record->peso_formateado }} x {{ $record->repeticiones }} reps</p>
                        </div>
                    @endforeach
                </section>
            @endif

            <section class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0" data-animate="card">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-base font-bold text-white">Mejores marcas</h2>
                    <span class="text-xs font-semibold text-gray-500">{{ $ranking->count() }} posiciones</span>
                </div>

                @if ($ranking->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead>
                                <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 border-b border-white/10">
                                    <th class="py-2.5 pr-3 font-semibold text-center w-16">#</th>
                                    <th class="py-2.5 px-3 font-semibold">Cliente</th>
                                    <th class="py-2.5 px-3 font-semibold">Ejercicio</th>
                                    <th class="py-2.5 px-3 font-semibold text-center">Peso</th>
                                    <th class="py-2.5 px-3 font-semibold text-center">Reps</th>
                                    <th class="py-2.5 px-3 font-semibold text-center">Puntos</th>
                                    <th class="py-2.5 pl-3 font-semibold text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ranking as $record)
                                    <tr class="border-b border-white/5 last:border-0 hover:bg-white/[0.02]">
                                        <td class="py-3 pr-3 text-center">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-xs font-bold {{ $loop->first ? 'bg-yellow-400 text-black' : 'bg-white/5 text-gray-300 border border-white/10' }}">
                                                {{ $loop->iteration }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <p class="font-semibold text-white truncate">{{ $record->cliente->nombre }}</p>
                                            <p class="text-[11px] text-gray-500">{{ $record->nivel }}</p>
                                        </td>
                                        <td class="py-3 px-3">
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
                                        <td class="py-3 px-3 text-center text-gray-300">{{ number_format($record->volumen, 0) }}</td>
                                        <td class="py-3 pl-3 text-center">
                                            <span class="inline-flex px-2 py-1 rounded-lg text-[11px] font-bold {{ $record->verificado ? 'bg-green-400/10 text-green-300 border border-green-400/20' : 'bg-white/5 text-gray-400 border border-white/10' }}">
                                                {{ $record->verificado ? 'Verificado' : 'Provisional' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center">
                        <h3 class="text-base font-bold text-white mb-1">No hay marcas para rankear</h3>
                        <p class="text-xs text-gray-400 mb-5">Cuando existan registros en Progreso aparecerán aquí.</p>
                        <a href="{{ route('progreso.index') }}" class="alpha-btn-primary inline-flex rounded-xl px-4 py-2.5 text-sm font-semibold">
                            Ir a Progreso
                        </a>
                    </div>
                @endif
            </section>
@endsection

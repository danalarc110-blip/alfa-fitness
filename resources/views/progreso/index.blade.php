@extends('layouts.app', ['active' => 'progreso'])
@section('title', 'Progreso')
@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Progreso</h1>
                    <p class="text-gray-400 text-xs mt-1">Registra levantamientos por ejercicio y conserva el historial de PR.</p>
                </div>

                <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
            </header>
@endsection
@section('content')
@if ($guard === 'web')
                <form method="GET" action="{{ route('progreso.index') }}" class="mb-5 flex flex-col sm:flex-row sm:items-end gap-3" data-animate="card">
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

            <section class="grid grid-cols-1 xl:grid-cols-[380px_1fr] gap-5">
                <form method="POST" action="{{ route('progreso.store') }}" class="alpha-card rounded-2xl p-5 sm:p-6 h-fit" data-animate="card">
                    @csrf
                    @if ($guard === 'web')
                        <input type="hidden" name="cliente_id" value="{{ $clienteSeleccionado }}">
                    @endif

                    <div class="mb-5">
                        <h2 class="text-base font-bold text-white">Nuevo levantamiento</h2>
                        <p class="text-xs text-gray-400 mt-1">Guarda peso, repeticiones y ejercicio.</p>
                    </div>

                    <div class="space-y-4">
                        <label class="block">
                            <span class="block text-xs font-semibold text-gray-400 mb-1.5">Ejercicio</span>
                            <select name="ejercicio_id" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                                <option value="">Seleccionar ejercicio</option>
                                @foreach ($ejercicios as $ejercicio)
                                    <option value="{{ $ejercicio->id }}" @selected(old('ejercicio_id') == $ejercicio->id)>
                                        {{ $ejercicio->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="block text-xs font-semibold text-gray-400 mb-1.5">Peso kg</span>
                                <input type="number" name="peso_kg" min="0.5" max="999" step="0.5" value="{{ old('peso_kg') }}" required
                                    class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                            </label>

                            <label class="block">
                                <span class="block text-xs font-semibold text-gray-400 mb-1.5">Reps</span>
                                <input type="number" name="repeticiones" min="1" max="100" value="{{ old('repeticiones', 1) }}" required
                                    class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                            </label>
                        </div>

                        <label class="block">
                            <span class="block text-xs font-semibold text-gray-400 mb-1.5">Notas</span>
                            <textarea name="notas" rows="3" maxlength="255"
                                class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60 resize-none"
                                placeholder="Opcional">{{ old('notas') }}</textarea>
                        </label>

                        <button type="submit" class="alpha-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-semibold inline-flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            Guardar registro
                        </button>
                    </div>
                </form>

                <div class="space-y-5 min-w-0">
                    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" data-animate="card">
                        @forelse ($mejoresMarcas as $record)
                            <div class="alpha-card rounded-2xl p-4 border border-white/10">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-12 h-12 rounded-xl bg-black/60 border border-white/10 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if ($record->ejercicio->tiene_imagen)
                                            <img src="{{ $record->ejercicio->imagen_url }}" alt="{{ $record->ejercicio->nombre }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M12 8v8"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-white truncate">{{ $record->ejercicio->nombre }}</h3>
                                        <p class="text-[11px] text-gray-500 truncate">{{ $record->ejercicio->grupo_muscular }}</p>
                                    </div>
                                </div>
                                <p class="text-2xl font-bold text-yellow-400">{{ $record->peso_formateado }}</p>
                                <p class="text-xs text-gray-400">{{ $record->repeticiones }} reps · {{ $record->nivel }}</p>
                            </div>
                        @empty
                            <div class="sm:col-span-2 xl:col-span-4 alpha-card rounded-2xl p-8 text-center">
                                <h3 class="text-base font-bold text-white mb-1">Sin registros todavia</h3>
                                <p class="text-xs text-gray-400">Agrega tu primer levantamiento para comenzar a ver tus mejores marcas.</p>
                            </div>
                        @endforelse
                    </section>

                    <section class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-base font-bold text-white">Historial de levantamientos</h2>
                            <span class="text-xs font-semibold text-gray-500">{{ $records->total() }} registros</span>
                        </div>

                        @if ($records->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[720px] text-sm">
                                    <thead>
                                        <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 border-b border-white/10">
                                            <th class="py-2.5 pr-3 font-semibold">Ejercicio</th>
                                            @if ($guard === 'web')
                                                <th class="py-2.5 px-3 font-semibold">Cliente</th>
                                            @endif
                                            <th class="py-2.5 px-3 font-semibold text-center">Peso</th>
                                            <th class="py-2.5 px-3 font-semibold text-center">Reps</th>
                                            <th class="py-2.5 px-3 font-semibold text-center">Nivel</th>
                                            <th class="py-2.5 px-3 font-semibold">Fecha</th>
                                            <th class="py-2.5 pl-3"></th>
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
                                                            <p class="text-[11px] text-gray-500 truncate">{{ $record->notas ?: $record->ejercicio->grupo_muscular }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                @if ($guard === 'web')
                                                    <td class="py-3 px-3 text-gray-300">{{ $record->cliente->nombre }}</td>
                                                @endif
                                                <td class="py-3 px-3 text-center font-bold text-yellow-400">{{ $record->peso_formateado }}</td>
                                                <td class="py-3 px-3 text-center text-gray-300">{{ $record->repeticiones }}</td>
                                                <td class="py-3 px-3 text-center">
                                                    <span class="inline-flex px-2 py-1 rounded-lg text-[11px] font-bold bg-white/5 text-gray-300 border border-white/10">{{ $record->nivel }}</span>
                                                </td>
                                                <td class="py-3 px-3 text-gray-400">{{ $record->created_at->format('d/m/Y') }}</td>
                                                <td class="py-3 pl-3 text-right">
                                                    <form method="POST" action="{{ route('progreso.destroy', $record) }}" onsubmit="return confirm('Eliminar este registro?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="w-8 h-8 rounded-lg text-gray-500 hover:text-red-400 hover:bg-red-500/10 transition-colors" title="Eliminar">
                                                            <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="py-10 text-center">
                                <p class="text-sm text-gray-400">No hay levantamientos guardados para este perfil.</p>
                            </div>
                        @endif
                    </section>
                </div>
            </section>
        <div class="mt-5">{{ $records->links() }}</div>
@endsection

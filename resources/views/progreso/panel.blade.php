@php $rutaFiltro = $esPropietario ? route('progreso.index') : route('entrenador.cliente.progreso', $cliente); @endphp
<div class="progress-space space-y-5">
    <section class="alpha-card rank-hero" aria-labelledby="rango-titulo">
        <img src="{{ asset('images/rangos/'.$rango['imagen']) }}" width="144" height="160" alt="Insignia del rango {{ $rango['nombre'] }}" class="rank-current">
        <div class="min-w-0 flex-1">
            <p class="alpha-eyebrow">{{ $esPropietario ? 'Tu rango de constancia' : 'Rango de constancia' }}</p>
            <h2 id="rango-titulo" class="text-2xl font-bold mt-1" data-rango="{{ $rango['nombre'] }}">{{ $rango['nombre'] }}</h2>
            <p class="text-sm text-gray-400 mt-1">{{ $rango['descripcion'] }} {{ $rango['total'] }} {{ $rango['total'] === 1 ? 'registro guardado' : 'registros guardados' }} en total.</p>
            <p class="text-sm mt-3">{{ $rango['siguiente'] ? 'Faltan '.$rango['faltan'].' registros para '.$rango['siguiente']['nombre'].'.' : 'Has alcanzado el último rango. Sigue registrando tus avances.' }}</p>
            <progress class="rank-progress mt-2" max="100" value="{{ $rango['porcentaje'] }}" aria-label="Progreso al siguiente rango">{{ $rango['porcentaje'] }}%</progress>
            <p class="text-xs text-gray-400 mt-2">El rango cuenta todos los registros y se mantiene al filtrar. Representa constancia, no una evaluación de fuerza o técnica.</p>
        </div>
    </section>
    <details class="alpha-card p-4 sm:p-5">
        <summary class="font-semibold cursor-pointer">Conoce los cuatro rangos</summary>
        <div class="rank-collection mt-4">
            @foreach (\App\Support\RangoAtleta::RANGOS as $nivel)
                <div class="text-center min-w-0">
                    <img src="{{ asset('images/rangos/'.$nivel['imagen']) }}" width="72" height="80" loading="lazy" alt="Insignia {{ $nivel['nombre'] }}" class="mx-auto">
                    <p class="text-sm font-semibold mt-1">{{ $nivel['nombre'] }}</p><p class="text-xs text-gray-400">{{ $nivel['minimo'] }} registros</p>
                </div>
            @endforeach
        </div>
    </details>
    <form method="GET" action="{{ $rutaFiltro }}" class="alpha-card alpha-form p-4 sm:p-5 flex flex-wrap gap-3 items-end" role="search">
        <label for="progreso-ejercicio" class="flex-1 min-w-0">Consultar ejercicio
            <select id="progreso-ejercicio" name="ejercicio_id" class="w-full">
                <option value="">Todos los registros</option>
                @foreach ($ejercicios as $ejercicio)
                    <option value="{{ $ejercicio->id }}" @selected((int) $ejercicioFiltro === $ejercicio->id)>{{ $ejercicio->nombre }}{{ $ejercicio->activo ? '' : ' (Archivado)' }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="alpha-btn-primary px-4 py-2">Consultar</button>
        @if ($ejercicioFiltro)<a href="{{ $rutaFiltro }}" class="alpha-btn-secondary px-4 py-2">Limpiar</a>@endif
    </form>
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3" aria-label="Resumen de registros">
        <article class="alpha-card p-4"><p class="text-xs text-gray-400">Volumen registrado</p><strong class="text-xl block mt-2">{{ number_format($totalVolumenKg, 1) }} kg</strong><p class="text-xs text-gray-400 mt-1">Peso × repeticiones</p></article>
        <article class="alpha-card p-4"><p class="text-xs text-gray-400">Mayor peso registrado</p><strong class="text-xl block mt-2">{{ $maximoRecord?->peso_formateado ?? '—' }}</strong><p class="text-xs text-gray-400 mt-1 break-words">{{ $maximoRecord?->ejercicio?->nombre ?? 'Sin registros todavía' }}</p></article>
        <article class="alpha-card p-4"><p class="text-xs text-gray-400">Levantamientos guardados</p><strong class="text-xl block mt-2">{{ $records->total() }}</strong><p class="text-xs text-gray-400 mt-1">{{ $ejercicioFiltro ? 'En este ejercicio' : 'En todos los ejercicios' }}</p></article>
        <article class="alpha-card p-4"><p class="text-xs text-gray-400">Ejercicios con registros</p><strong class="text-xl block mt-2">{{ $totalEjercicios }}</strong><p class="text-xs text-gray-400 mt-1">En esta selección</p></article>
    </section>
    @if ($esPropietario)
        <details id="nuevo-registro" class="alpha-card p-4 sm:p-6" @if ($errors->any() || $rango['total'] === 0) open @endif>
            <summary class="font-semibold cursor-pointer text-yellow-400">＋ Registrar levantamiento</summary>
            <form method="POST" action="{{ route('progreso.store') }}" class="alpha-form grid sm:grid-cols-2 gap-4 mt-5">
                @csrf
                <label class="sm:col-span-2">Ejercicio
                    <select name="ejercicio_id" required><option value="">Selecciona un ejercicio</option>@foreach ($ejerciciosRegistro as $ejercicio)<option value="{{ $ejercicio->id }}" @selected((string) old('ejercicio_id') === (string) $ejercicio->id)>{{ $ejercicio->nombre }}</option>@endforeach</select>
                </label>
                <label>Peso en kg<input name="peso_kg" type="number" min="0.5" max="999" step="0.01" inputmode="decimal" value="{{ old('peso_kg') }}" required placeholder="Ej: 20"></label>
                <label>Repeticiones<input name="repeticiones" type="number" min="1" max="100" inputmode="numeric" value="{{ old('repeticiones', 1) }}" required></label>
                <label class="sm:col-span-2">Notas opcionales<textarea name="notas" maxlength="255" rows="2" placeholder="Cómo te sentiste o un detalle de la serie">{{ old('notas') }}</textarea></label>
                <button type="submit" class="alpha-btn-primary px-4 py-3 sm:col-span-2" @disabled($ejerciciosRegistro->isEmpty())>Guardar registro</button>
                @if ($ejerciciosRegistro->isEmpty())<p class="text-sm text-gray-400 sm:col-span-2">No hay ejercicios activos. Solicita al administrador que habilite el catálogo.</p>@endif
            </form>
        </details>
    @endif
    <section class="alpha-card p-4 sm:p-6" aria-labelledby="grafico-progreso-titulo">
        <h2 id="grafico-progreso-titulo" class="text-lg font-semibold">Evolución por ejercicio</h2>
        @if ($ejercicioGrafico && $ultimosLevantamientos->count() >= 2)
            <p class="text-sm text-gray-400 mt-1">{{ $ejercicioGrafico->nombre }} · últimos {{ $ultimosLevantamientos->count() }} registros. Cada barra representa el peso de una serie.</p>
            <ol class="progress-chart" aria-label="Pesos registrados en {{ $ejercicioGrafico->nombre }}">
                @foreach ($ultimosLevantamientos as $registro)
                    <li aria-label="{{ $registro->created_at->format('d/m/Y') }}: {{ $registro->peso_formateado }}, {{ $registro->repeticiones }} repeticiones">
                        <div class="progress-chart-column"><div style="height: {{ max(4, round((float) $registro->peso_kg / max(1, (float) $ultimosLevantamientos->max('peso_kg')) * 100)) }}%"><span>{{ rtrim(rtrim($registro->peso_kg, '0'), '.') }}</span></div></div>
                        <time datetime="{{ $registro->created_at->toDateString() }}">{{ $registro->created_at->format('d/m') }}</time>
                    </li>
                @endforeach
            </ol>
            <p class="text-xs text-gray-400 mt-3">Pesos en kg. Se compara el mismo ejercicio; las repeticiones pueden variar. Cambia el filtro para ver otro ejercicio.</p>
        @else
            <p class="text-sm text-gray-400 mt-3">Se necesitan al menos dos registros del mismo ejercicio para mostrar su evolución.</p>
        @endif
    </section>
    <section class="alpha-card p-4 sm:p-6">
        <h2 class="text-lg font-semibold">Mejores series por volumen</h2>
        <p class="text-xs text-gray-400 mt-1">Una por ejercicio, según peso × repeticiones. No compara la fuerza entre ejercicios distintos.</p>
        <div class="grid sm:grid-cols-2 gap-3 mt-4">
            @forelse ($mejoresMarcas as $registro)
                <article class="rounded-xl border border-white/10 p-4 min-w-0"><h3 class="font-semibold break-words">{{ $registro->ejercicio->nombre }}</h3><p class="text-lg text-yellow-400 font-bold mt-2">{{ $registro->peso_formateado }} <span class="text-sm text-gray-400 font-normal">× {{ $registro->repeticiones }} reps</span></p><p class="text-xs text-gray-400 mt-1">{{ number_format($registro->volumen, 1) }} kg de volumen registrado</p></article>
            @empty
                <p class="text-sm text-gray-400">Agrega tu primer levantamiento para comenzar.</p>
            @endforelse
        </div>
    </section>
    <section class="alpha-card p-4 sm:p-6">
        <h2 class="text-lg font-semibold mb-4">Historial de levantamientos</h2>
        <ol class="progress-history">
            @forelse ($records as $registro)
                <li class="progress-record" data-record-id="{{ $registro->id }}">
                    <div class="min-w-0 flex-1"><h3 class="font-semibold break-words">{{ $registro->ejercicio->nombre }}</h3><p class="text-sm text-yellow-400 mt-1">{{ $registro->peso_formateado }} × {{ $registro->repeticiones }} reps</p><time class="text-xs text-gray-400" datetime="{{ $registro->created_at->toIso8601String() }}">{{ $registro->created_at->format('d/m/Y · H:i') }}</time>@if ($registro->notas)<p class="text-sm text-gray-400 mt-2 break-words">{{ $registro->notas }}</p>@endif</div>
                    @if ($esPropietario)
                        <form method="POST" action="{{ route('progreso.destroy', $registro) }}" data-confirm="¿Eliminar este levantamiento? Se actualizarán el resumen y el rango.">
                            @csrf @method('DELETE')
                            <button type="submit" class="alpha-btn-secondary px-3 py-2 text-sm" aria-label="Eliminar registro de {{ $registro->ejercicio->nombre }} del {{ $registro->created_at->format('d/m/Y') }}">Eliminar</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-400 py-4">No hay levantamientos para esta selección.</li>
            @endforelse
        </ol>
        <div class="mt-4">{{ $records->links() }}</div>
    </section>
</div>

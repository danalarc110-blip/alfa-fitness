@extends('layouts.app', ['active' => 'progreso'])
@section('title', 'Estadísticas por ejercicio')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <p class="text-sm text-gray-400">Tus registros por ejercicio, incluidos los de máquinas y poleas. Solo se muestran tus datos.</p>
    <a href="{{ route('progreso.index') }}" class="alpha-btn-secondary rounded-xl px-4 py-2 text-sm">Volver a mis récords</a>
</div>

<form method="GET" action="{{ route('progreso.estadisticas') }}" class="alpha-card rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <label class="text-sm">Ejercicio
        <select name="ejercicio_id" class="alpha-select w-full mt-1">
            <option value="">Todos mis ejercicios</option>
            @foreach($ejercicios as $ejercicio)
                <option value="{{ $ejercicio->id }}" @selected((string) ($filtros['ejercicio_id'] ?? '') === (string) $ejercicio->id)>{{ $ejercicio->nombre }}{{ $ejercicio->activo ? '' : ' (histórico)' }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">Desde<input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="alpha-input w-full mt-1"></label>
    <label class="text-sm">Hasta<input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="alpha-input w-full mt-1"></label>
    <div class="flex items-end gap-2"><button type="submit" class="alpha-btn-primary rounded-xl px-4 py-2">Filtrar</button><a href="{{ route('progreso.estadisticas') }}" class="alpha-btn-secondary rounded-xl px-4 py-2">Limpiar</a></div>
</form>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
    <div class="alpha-card rounded-2xl p-4"><p class="text-xs text-gray-400">Registros en el período</p><p class="text-2xl font-bold">{{ number_format($resumen->registros) }}</p></div>
    <div class="alpha-card rounded-2xl p-4"><p class="text-xs text-gray-400">Ejercicios registrados</p><p class="text-2xl font-bold">{{ number_format($resumen->ejercicios) }}</p></div>
    <div class="alpha-card rounded-2xl p-4"><p class="text-xs text-gray-400">Volumen registrado (kg × repeticiones)</p><p class="text-2xl font-bold">{{ number_format($resumen->volumen, 2) }}</p></div>
</div>

<div class="alpha-card rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <caption class="sr-only">Estadísticas privadas de tus levantamientos por ejercicio</caption>
            <thead class="text-xs text-gray-400"><tr><th scope="col" class="p-4">Ejercicio / grupo muscular</th><th scope="col" class="p-4">Registros</th><th scope="col" class="p-4">Carga máxima</th><th scope="col" class="p-4">Volumen total</th><th scope="col" class="p-4">Nivel de mejor registro</th><th scope="col" class="p-4">1RM estimado</th></tr></thead>
            <tbody>
            @forelse($estadisticas as $dato)
                @php($nivel = $dato->mejor_volumen >= 600 ? 'Avanzado' : ($dato->mejor_volumen >= 300 ? 'Intermedio' : 'Inicial'))
                <tr class="border-t border-white/10">
                    <th scope="row" class="p-4 font-medium"><span class="block">{{ $dato->nombre }}</span><span class="block text-xs text-gray-400">{{ $dato->grupo_muscular }}{{ $dato->activo ? '' : ' · Histórico' }}</span></th>
                    <td class="p-4">{{ $dato->registros }}</td>
                    <td class="p-4 whitespace-nowrap">{{ number_format($dato->peso_maximo, 2) }} kg</td>
                    <td class="p-4">{{ number_format($dato->volumen, 2) }}</td>
                    <td class="p-4">{{ $nivel }}</td>
                    <td class="p-4 whitespace-nowrap">{{ number_format($dato->estimacion_1rm, 2) }} kg</td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-8 text-center text-gray-400">No hay registros para estos filtros. Agrega un levantamiento desde Mis Récords.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $estadisticas->links() }}</div>
</div>
<p class="mt-4 text-xs text-gray-400">El volumen es la carga multiplicada por las repeticiones de los registros guardados, no de todas tus sesiones. La estimación 1RM usa carga × (1 + mínimo de repeticiones y 30 / 30). Es orientativa: no indica una carga segura ni reemplaza la supervisión o una evaluación de salud. Los niveles mantienen la clasificación existente y no comparan personas.</p>
@endsection

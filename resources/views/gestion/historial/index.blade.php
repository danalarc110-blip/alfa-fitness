@extends('layouts.app')
@section('title', $tipo === 'ventas' ? 'Historial de ventas' : 'Historial de asistencias')
@section('content')
@include('gestion.nav')
<nav aria-label="Historial operativo" class="flex flex-wrap gap-3 mb-5 text-sm"><a class="alpha-btn-secondary px-4 py-2 rounded-xl" href="{{ route('gestion.historial.ventas.index') }}">Ventas</a><a class="alpha-btn-secondary px-4 py-2 rounded-xl" href="{{ route('gestion.historial.asistencias.index') }}">Asistencias</a></nav>
<p class="text-sm text-gray-400 mb-5">Corrige registros erróneos con motivo y conserva la trazabilidad. Las anulaciones no eliminan datos ni realizan reembolsos.</p>
<form method="GET" class="alpha-form flex flex-wrap items-end gap-3 mb-6">
<label>Buscar<input name="q" value="{{ $filtros['q'] ?? '' }}" maxlength="100" placeholder="Nombre{{ $tipo === 'ventas' ? ' o nota' : '' }}"></label>
<label>Desde<input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"></label><label>Hasta<input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></label>
<label>Estado<select name="estado"><option value="">Todos</option><option value="vigente" @selected(($filtros['estado'] ?? '') === 'vigente')>Vigentes</option><option value="anulada" @selected(($filtros['estado'] ?? '') === 'anulada')>Anuladas</option></select></label>
<button class="alpha-btn-secondary px-4 py-3 rounded-xl">Buscar</button>
</form>
<div class="alpha-card rounded-2xl overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left"><th class="p-4">ID / cliente</th><th class="p-4">{{ $tipo === 'ventas' ? 'Fecha / total' : 'Entrada / salida' }}</th><th class="p-4">Estado</th><th class="p-4">Acciones</th></tr></thead><tbody>
@forelse($registros as $registro)<tr class="border-t border-white/10"><td class="p-4">#{{ $registro->id }}<br>{{ $registro->cliente?->nombre ?? 'Sin cliente asociado' }}</td><td class="p-4">
@if($tipo === 'ventas'){{ $registro->created_at->format('d/m/Y H:i') }}<br>${{ number_format((float) $registro->total, 2) }}@else{{ $registro->fecha_hora->format('d/m/Y H:i') }}<br>{{ $registro->fecha_salida?->format('d/m/Y H:i') ?? 'En curso' }}@endif
</td><td class="p-4">{{ $registro->anulada_en ? 'Anulada' : 'Vigente' }}</td><td class="p-4"><a class="alpha-btn-secondary px-3 py-2 rounded-lg" href="{{ route('gestion.historial.'.$tipo.'.edit', $registro->id) }}">{{ $registro->anulada_en ? 'Ver historial' : 'Revisar / corregir' }}</a></td></tr>
@empty<tr><td colspan="4" class="p-8 text-center">No se encontraron registros.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $registros->links() }}</div>
@endsection

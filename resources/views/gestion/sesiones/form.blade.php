@extends('layouts.app')
@section('title', $sesion->exists ? 'Editar sesión' : 'Programar sesión')
@section('content')
@include('gestion.nav')
<form method="POST" action="{{ $sesion->exists ? route('gestion.sesiones.update', $sesion) : route('gestion.sesiones.store') }}" class="alpha-card rounded-2xl p-5 max-w-xl grid gap-4">
@csrf @if($sesion->exists) @method('PUT') @endif
<label class="grid gap-2">Cliente<select name="cliente_id" required class="alpha-input rounded-xl px-3 py-2"><option value="">Seleccionar cliente</option>@foreach($clientes as $cliente)<option value="{{ $cliente->id }}" @selected((string) old('cliente_id', $sesion->cliente_id) === (string) $cliente->id)>{{ $cliente->nombre }}</option>@endforeach</select></label>
<label class="grid gap-2">Entrenador<select name="entrenador_id" required class="alpha-input rounded-xl px-3 py-2"><option value="">Seleccionar entrenador</option>@foreach($entrenadores as $entrenador)<option value="{{ $entrenador->id }}" @selected((string) old('entrenador_id', $sesion->entrenador_id) === (string) $entrenador->id)>{{ $entrenador->name }}</option>@endforeach</select></label>
<label class="grid gap-2">Inicio ({{ config('app.timezone') }})<input name="fecha_inicio" type="datetime-local" value="{{ old('fecha_inicio', $sesion->fecha_inicio?->format('Y-m-d\TH:i')) }}" required class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Finalización<input name="fecha_fin" type="datetime-local" value="{{ old('fecha_fin', $sesion->fecha_fin?->format('Y-m-d\TH:i')) }}" required class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Estado<select name="estado" required class="alpha-input rounded-xl px-3 py-2">@foreach(['programada' => 'Programada', 'completada' => 'Completada', 'cancelada' => 'Cancelada'] as $valor => $texto)<option value="{{ $valor }}" @selected(old('estado', $sesion->estado) === $valor)>{{ $texto }}</option>@endforeach</select></label>
<label class="grid gap-2">Notas de coordinación<textarea name="notas" maxlength="500" rows="3" class="alpha-input rounded-xl px-3 py-2">{{ old('notas', $sesion->notas) }}</textarea></label>
<p class="text-sm text-gray-400">No incluyas diagnósticos médicos ni información sensible en las notas.</p>
<div class="flex gap-3"><button class="alpha-btn-primary rounded-xl px-4 py-2">Guardar</button><a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.sesiones.index') }}">Cancelar</a></div>
</form>
@endsection

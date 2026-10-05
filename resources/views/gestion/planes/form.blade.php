@extends('layouts.app')
@section('title', $plan->exists ? 'Editar plan' : 'Crear plan')
@section('content')
@include('gestion.nav')
<form method="POST" action="{{ $plan->exists ? route('gestion.planes.update', $plan) : route('gestion.planes.store') }}" class="alpha-card rounded-2xl p-5 max-w-xl grid gap-4">
@csrf @if($plan->exists) @method('PUT') @endif
<label class="grid gap-2">Nombre<input name="nombre" value="{{ old('nombre', $plan->nombre) }}" required maxlength="100" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Precio ($)<input name="precio" type="number" value="{{ old('precio', $plan->precio) }}" required min="0.01" max="99999.99" step="0.01" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Duración (días)<input name="duracion_dias" type="number" value="{{ old('duracion_dias', $plan->duracion_dias) }}" required min="1" max="3650" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Condiciones<textarea name="condiciones" maxlength="255" rows="3" class="alpha-input rounded-xl px-3 py-2">{{ old('condiciones', $plan->condiciones) }}</textarea></label>
<div class="flex gap-3"><button class="alpha-btn-primary rounded-xl px-4 py-2">Guardar</button><a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.planes.index') }}">Cancelar</a></div>
</form>
@endsection

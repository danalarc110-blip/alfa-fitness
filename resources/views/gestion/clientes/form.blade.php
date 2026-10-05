@extends('layouts.app')
@section('title', $cliente->exists ? 'Editar cliente' : 'Crear cliente')
@section('content')
@include('gestion.nav')
<form method="POST" action="{{ $cliente->exists ? route('gestion.clientes.update', $cliente) : route('gestion.clientes.store') }}" class="alpha-card rounded-2xl p-5 max-w-xl grid gap-4">
@csrf @if($cliente->exists) @method('PUT') @endif
<label class="grid gap-2">Nombre<input name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required maxlength="100" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Correo electrónico<input name="correo" type="email" value="{{ old('correo', $cliente->correo) }}" required maxlength="255" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Entrenador<select name="entrenador_id" class="alpha-input rounded-xl px-3 py-2"><option value="">Sin asignar</option>@foreach($entrenadores as $entrenador)<option value="{{ $entrenador->id }}" @selected((string) old('entrenador_id', $cliente->entrenador_id) === (string) $entrenador->id)>{{ $entrenador->name }}</option>@endforeach</select></label>
@unless($cliente->exists)
<label class="grid gap-2">Contraseña<input name="password" type="password" required minlength="12" autocomplete="new-password" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Confirmar contraseña<input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password" class="alpha-input rounded-xl px-3 py-2"></label>
<p class="text-sm text-gray-400">Usa al menos 12 caracteres con mayúsculas, minúsculas, números y símbolos. El titular debe introducir su contraseña y aceptar personalmente los documentos legales. El empleado no registra consentimiento en su nombre.</p>
@endunless
<div class="flex gap-3"><button class="alpha-btn-primary rounded-xl px-4 py-2">Guardar</button><a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.clientes.index') }}">Cancelar</a></div>
</form>
@endsection

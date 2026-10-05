@extends('layouts.app')
@section('title', $usuario->exists ? 'Editar empleado' : 'Crear empleado')
@section('content')
@include('gestion.nav')
<form method="POST" action="{{ $usuario->exists ? route('gestion.usuarios.update', $usuario) : route('gestion.usuarios.store') }}" class="alpha-card rounded-2xl p-5 max-w-xl grid gap-4">
@csrf @if($usuario->exists) @method('PUT') @endif
<label class="grid gap-2">Nombre<input name="name" value="{{ old('name', $usuario->name) }}" required maxlength="100" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Correo electrónico<input name="email" type="email" value="{{ old('email', $usuario->email) }}" required maxlength="255" class="alpha-input rounded-xl px-3 py-2"></label>
<label class="grid gap-2">Rol<select name="rol" required class="alpha-input rounded-xl px-3 py-2">@foreach(['Secretaria', 'Entrenador'] as $rol)<option value="{{ $rol }}" @selected(old('rol', $usuario->rol) === $rol)>{{ $rol }}</option>@endforeach</select></label>
@unless($usuario->exists)<p class="text-sm text-gray-400">El empleado recibirá un enlace privado por correo para crear su propia contraseña.</p>@endunless
<div class="flex gap-3"><button class="alpha-btn-primary rounded-xl px-4 py-2">Guardar</button><a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.usuarios.index') }}">Cancelar</a></div>
</form>
@endsection

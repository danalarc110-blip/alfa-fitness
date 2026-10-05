@extends('layouts.app')
@section('title', 'Gestión de usuarios')
@section('content')
@include('gestion.nav')
<div class="flex flex-wrap justify-between gap-4 mb-5">
    <form method="GET" class="flex gap-2"><label class="sr-only" for="q">Buscar usuario</label><input id="q" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar por nombre o correo" class="alpha-input rounded-xl px-3 py-2"><button class="alpha-btn-secondary rounded-xl px-4 py-2">Buscar</button></form>
    <a class="alpha-btn-primary rounded-xl px-4 py-2" href="{{ route('gestion.usuarios.create') }}">Crear empleado</a>
</div>
<div class="alpha-card rounded-2xl overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left"><th class="p-4">Nombre / correo</th><th class="p-4">Rol</th><th class="p-4">Estado</th><th class="p-4">Acciones</th></tr></thead><tbody>
@forelse($usuarios as $usuario)
<tr class="border-t border-white/10"><td class="p-4">{{ $usuario->name }}<br><span class="text-xs text-gray-400">{{ $usuario->email }}</span></td><td class="p-4">{{ $usuario->rol }}</td><td class="p-4">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</td><td class="p-4"><div class="flex flex-wrap gap-2">
@if($usuario->rol === 'Administrador')<span>Cuenta protegida</span>@else
<a class="alpha-btn-secondary rounded-lg px-3 py-2" href="{{ route('gestion.usuarios.edit', $usuario) }}">Editar</a>
@if($usuario->activo)
<form method="POST" action="{{ route('gestion.usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Desactivar este empleado? Se conservará su historial.')">@csrf @method('DELETE')<button class="alpha-btn-secondary rounded-lg px-3 py-2">Desactivar</button></form>
@else
<form method="POST" action="{{ route('gestion.usuarios.reactivar', $usuario) }}">@csrf @method('PATCH')<button class="alpha-btn-secondary rounded-lg px-3 py-2">Reactivar</button></form>
@endif
@if(!$usuario->password_establecida && $usuario->activo)<form method="POST" action="{{ route('gestion.usuarios.invitar', $usuario) }}">@csrf<button class="alpha-btn-secondary rounded-lg px-3 py-2">Reenviar invitación</button></form>@endif
@endif
</div></td></tr>
@empty<tr><td colspan="4" class="p-8 text-center">No se encontraron usuarios.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $usuarios->links() }}</div>
@endsection

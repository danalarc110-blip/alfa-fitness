@extends('layouts.app')
@section('title', 'Gestión de clientes')
@section('content')
@include('gestion.nav')
<div class="flex flex-wrap justify-between gap-4 mb-5">
<form method="GET" class="flex gap-2"><label class="sr-only" for="q">Buscar cliente</label><input id="q" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar por nombre o correo" class="alpha-input rounded-xl px-3 py-2"><button class="alpha-btn-secondary rounded-xl px-4 py-2">Buscar</button></form>
<a class="alpha-btn-primary rounded-xl px-4 py-2" href="{{ route('gestion.clientes.create') }}">Crear cliente</a>
</div>
<div class="alpha-card rounded-2xl overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left"><th class="p-4">Cliente / correo</th><th class="p-4">Entrenador</th><th class="p-4">Estado</th><th class="p-4">Acciones</th></tr></thead><tbody>
@forelse($clientes as $cliente)<tr class="border-t border-white/10"><td class="p-4">{{ $cliente->nombre }}<br><span class="text-xs text-gray-400">{{ $cliente->correo }}</span></td><td class="p-4">{{ $entrenadores[$cliente->entrenador_id] ?? 'Sin asignar' }}</td><td class="p-4">{{ $cliente->activo ? 'Activo' : 'Inactivo' }}</td><td class="p-4"><div class="flex flex-wrap gap-2">
<a class="alpha-btn-secondary rounded-lg px-3 py-2" href="{{ route('gestion.clientes.edit', $cliente) }}">Editar</a>
@if($cliente->activo)<form method="POST" action="{{ route('gestion.clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Desactivar este cliente? Se conservará su historial.')">@csrf @method('DELETE')<button class="alpha-btn-secondary rounded-lg px-3 py-2">Desactivar</button></form>
@else<form method="POST" action="{{ route('gestion.clientes.reactivar', $cliente) }}">@csrf @method('PATCH')<button class="alpha-btn-secondary rounded-lg px-3 py-2">Reactivar</button></form>@endif
</div></td></tr>@empty<tr><td colspan="4" class="p-8 text-center">No se encontraron clientes.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $clientes->links() }}</div>
@endsection

@extends('layouts.app')
@section('title', 'Gestión de planes')
@section('content')
@include('gestion.nav')
<div class="flex flex-wrap justify-between gap-4 mb-5"><form method="GET" class="flex gap-2"><label class="sr-only" for="q">Buscar plan</label><input id="q" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar plan" class="alpha-input rounded-xl px-3 py-2"><button class="alpha-btn-secondary rounded-xl px-4 py-2">Buscar</button></form><a class="alpha-btn-primary rounded-xl px-4 py-2" href="{{ route('gestion.planes.create') }}">Crear plan</a></div>
<div class="alpha-card rounded-2xl overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left"><th class="p-4">Plan</th><th class="p-4">Precio / duración</th><th class="p-4">Estado</th><th class="p-4">Acciones</th></tr></thead><tbody>
@forelse($planes as $plan)<tr class="border-t border-white/10"><td class="p-4">{{ $plan->nombre }}</td><td class="p-4">${{ number_format((float) $plan->precio, 2) }} / {{ $plan->duracion_dias }} días</td><td class="p-4">{{ $plan->activo ? 'Activo' : 'Inactivo' }}</td><td class="p-4"><div class="flex flex-wrap gap-2"><a class="alpha-btn-secondary rounded-lg px-3 py-2" href="{{ route('gestion.planes.edit', $plan) }}">Editar</a>
@if($plan->activo)<form method="POST" action="{{ route('gestion.planes.destroy', $plan) }}" onsubmit="return confirm('¿Desactivar este plan? Se conservará su historial.')">@csrf @method('DELETE')<button class="alpha-btn-secondary rounded-lg px-3 py-2">Desactivar</button></form>@else<form method="POST" action="{{ route('gestion.planes.reactivar', $plan) }}">@csrf @method('PATCH')<button class="alpha-btn-secondary rounded-lg px-3 py-2">Reactivar</button></form>@endif
</div></td></tr>@empty<tr><td colspan="4" class="p-8 text-center">No se encontraron planes.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $planes->links() }}</div>
@endsection

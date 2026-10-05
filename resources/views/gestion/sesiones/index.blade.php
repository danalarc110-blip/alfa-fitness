@extends('layouts.app')
@section('title', 'Sesiones con entrenador')
@section('content')
@include('gestion.nav')
<div class="flex flex-wrap justify-between gap-4 mb-5">
<form method="GET" class="flex flex-wrap gap-2"><label class="sr-only" for="q">Buscar sesión</label><input id="q" name="q" value="{{ $q }}" maxlength="100" placeholder="Buscar cliente o entrenador" class="alpha-input rounded-xl px-3 py-2"><label class="sr-only" for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ $desde }}" class="alpha-input rounded-xl px-3 py-2"><label class="sr-only" for="estado">Estado</label><select id="estado" name="estado" class="alpha-input rounded-xl px-3 py-2"><option value="">Todos los estados</option>@foreach(['programada' => 'Programada', 'completada' => 'Completada', 'cancelada' => 'Cancelada'] as $valor => $texto)<option value="{{ $valor }}" @selected($estado === $valor)>{{ $texto }}</option>@endforeach</select><button class="alpha-btn-secondary rounded-xl px-4 py-2">Buscar</button></form>
@if($puedeEditar)<a class="alpha-btn-primary rounded-xl px-4 py-2" href="{{ route('gestion.sesiones.create') }}">Programar sesión</a>@endif
</div>
<div class="alpha-card rounded-2xl overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left"><th class="p-4">Cliente</th><th class="p-4">Entrenador</th><th class="p-4">Horario</th><th class="p-4">Estado</th>@if($puedeEditar)<th class="p-4">Acciones</th>@endif</tr></thead><tbody>
@forelse($sesiones as $sesion)<tr class="border-t border-white/10"><td class="p-4">{{ $sesion->cliente->nombre }}</td><td class="p-4">{{ $sesion->entrenador->name }}</td><td class="p-4">{{ $sesion->fecha_inicio->format('d/m/Y H:i') }}<br>{{ $sesion->fecha_fin->format('d/m/Y H:i') }}</td><td class="p-4">{{ ucfirst($sesion->estado) }}</td>@if($puedeEditar)<td class="p-4"><div class="flex flex-wrap gap-2"><a class="alpha-btn-secondary rounded-lg px-3 py-2" href="{{ route('gestion.sesiones.edit', $sesion) }}">Editar</a>@if($sesion->estado !== 'cancelada')<form method="POST" action="{{ route('gestion.sesiones.destroy', $sesion) }}" onsubmit="return confirm('¿Cancelar esta sesión? Se conservará su historial.')">@csrf @method('DELETE')<button class="alpha-btn-secondary rounded-lg px-3 py-2">Cancelar sesión</button></form>@endif</div></td>@endif</tr>
@empty<tr><td colspan="{{ $puedeEditar ? 5 : 4 }}" class="p-8 text-center">No se encontraron sesiones.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $sesiones->links() }}</div>
@endsection

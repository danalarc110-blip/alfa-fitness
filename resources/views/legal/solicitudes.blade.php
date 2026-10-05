@extends('layouts.app')
@section('title', 'Solicitudes sobre datos personales')
@section('content')
<p class="mb-5 text-sm text-gray-400">Verifica la identidad antes de entregar o modificar datos. No elimines registros sujetos a conservación obligatoria.</p>
<form method="GET" class="alpha-form mb-5"><label>Estado<select name="estado"><option value="">Todos</option>@foreach(['pendiente','en_revision','resuelta'] as $estado)<option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>@endforeach</select></label><button class="alpha-btn-secondary px-3 py-2">Filtrar</button></form>
@forelse($solicitudes as $solicitud)
<article class="alpha-card p-5 mb-4"><h2 class="font-semibold">#{{ $solicitud->id }} · {{ $solicitud->tipo }} · {{ $solicitud->nombre }}</h2><p class="text-sm">{{ $solicitud->correo }} · {{ $solicitud->created_at->format('d/m/Y H:i') }}</p><p class="whitespace-pre-wrap my-4">{{ $solicitud->detalle }}</p>
<form method="POST" action="{{ route('legal.resolver', $solicitud) }}" class="alpha-form">@csrf @method('PATCH')<label>Estado<select name="estado">@foreach(['pendiente','en_revision','resuelta'] as $estado)<option value="{{ $estado }}" @selected($solicitud->estado === $estado)>{{ $estado }}</option>@endforeach</select></label><label>Seguimiento interno<textarea name="notas_internas" maxlength="3000">{{ $solicitud->notas_internas }}</textarea></label><button class="alpha-btn-primary px-4 py-2">Guardar seguimiento</button></form></article>
@empty<p class="alpha-card p-5">No hay solicitudes para este filtro.</p>@endforelse
{{ $solicitudes->links() }}
@endsection

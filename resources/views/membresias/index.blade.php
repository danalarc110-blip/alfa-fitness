@extends('layouts.app', ['active' => 'membresias'])
@section('title', 'Membresías')
@section('content')
<p class="text-gray-400 text-sm mb-6">{{ $guard === 'cliente' ? 'Elige un plan y presenta el pago en recepción. Solicitar no activa la membresía.' : 'Consulta solicitudes y membresías. Solo la secretaria puede confirmar cobros y activaciones.' }}</p>
@if($guard === 'cliente')
<section class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
    @foreach($planes as $plan)<article class="alpha-card p-6"><h2 class="alpha-editorial text-2xl">{{ $plan->nombre }}</h2><p class="text-3xl font-semibold text-yellow-400 my-4">${{ number_format((float)$plan->precio, 2) }}</p><p class="text-sm text-gray-400">{{ $plan->duracion_dias }} días · {{ $plan->condiciones }}</p><form method="POST" action="{{ route('membresias.solicitar') }}" class="mt-5">@csrf<input type="hidden" name="plan_id" value="{{ $plan->id }}"><button class="alpha-btn-primary px-5 py-3 w-full">Solicitar este plan</button></form></article>@endforeach
</section>
@else
<form method="GET" class="alpha-card alpha-form p-5 mb-7 grid sm:grid-cols-3 gap-4"><label>Cliente<input name="q" value="{{ $busqueda }}" placeholder="Nombre o correo"></label><label>Estado<select name="estado"><option value="">Todos</option>@foreach(['pendiente','activada','cancelada'] as $opcion)<option value="{{ $opcion }}" @selected($estado === $opcion)>{{ ucfirst($opcion) }}</option>@endforeach</select></label><div class="flex items-end gap-3"><button class="alpha-btn-primary px-5 py-3">Filtrar</button><a href="{{ route('membresias.index') }}" class="alpha-btn-secondary px-5 py-3">Limpiar</a></div></form>
@endif
<div class="flex justify-between items-center mb-4"><h2 class="text-lg font-semibold">Solicitudes</h2><span class="text-xs text-gray-400">{{ $solicitudes->total() }} {{ $solicitudes->total() === 1 ? 'solicitud' : 'solicitudes' }}</span></div>
<div class="alpha-requests">
@forelse($solicitudes as $solicitud)
    <article class="alpha-card alpha-request">
        <div class="alpha-request-summary"><span class="alpha-status {{ $solicitud->estado === 'activada' ? 'alpha-status-active' : '' }}">{{ ucfirst($solicitud->estado) }}</span><h3>{{ $solicitud->plan_nombre }}</h3><p class="text-sm text-gray-400">{{ $solicitud->cliente->nombre }}</p><p class="alpha-request-price">${{ number_format((float)$solicitud->precio_acordado, 2) }} <span class="text-xs text-gray-400">USD / {{ $solicitud->duracion_dias }} días</span></p><p class="text-xs text-gray-400">Condiciones guardadas al solicitar.</p></div>
        <div class="alpha-request-detail">
        @if($solicitud->estado === 'pendiente' && auth('web')->user()?->rol === 'Secretaria')
            <h3 class="font-semibold">Confirmar pago y activar</h3><p class="text-xs text-gray-400 mt-2">Registra el cobro recibido en recepción para activar la membresía.</p>
            <form method="POST" action="{{ route('membresias.activar', $solicitud) }}" class="alpha-form">@csrf @method('PATCH')<div class="alpha-payment-fields"><label>Importe cobrado<input type="number" name="importe" min="0.01" step="0.01" value="{{ old('importe', $solicitud->precio_acordado) }}" required></label><label>Referencia opcional<input name="referencia" maxlength="100" value="{{ old('referencia') }}"></label></div><button class="alpha-btn-primary px-5 py-3">Confirmar pago y activar</button></form>
        @elseif($solicitud->estado === 'pendiente')
            <h3 class="font-semibold">Pendiente de confirmación</h3><p class="text-sm text-gray-400 mt-3">Presenta el pago en recepción. Secretaría confirmará el cobro y activará el plan.</p>
        @else
            <h3 class="font-semibold">{{ $solicitud->estado === 'activada' ? 'Solicitud activada' : 'Solicitud cancelada' }}</h3><p class="text-sm text-gray-400 mt-3">Consulta la vigencia y el importe en el historial de membresías.</p>
        @endif
        @if($solicitud->estado === 'pendiente' && ($guard === 'cliente' || auth('web')->user()?->rol === 'Secretaria'))<form method="POST" action="{{ route('membresias.solicitudes.cancelar', $solicitud) }}" class="mt-4">@csrf @method('PATCH')<button class="text-sm text-red-300">Cancelar solicitud</button></form>@endif
        @if($solicitud->membresia?->pago)<p class="text-xs text-gray-400 mt-4">Cobrado {{ $solicitud->membresia->pago->pagado_en->format('d/m/Y H:i') }} por {{ $solicitud->membresia->pago->registrador?->name ?? 'Personal anterior' }}</p>@endif
        </div>
    </article>
@empty
    <div class="alpha-card p-8 text-gray-400">No hay solicitudes con estos filtros.</div>
@endforelse
</div>
{{ $solicitudes->links() }}
<h2 class="text-lg font-semibold mt-8 mb-4">Historial de membresías</h2>
<div class="overflow-x-auto alpha-card"><table class="w-full text-sm"><thead><tr class="text-left border-b border-white/10"><th class="p-4">Cliente</th><th class="p-4">Plan</th><th class="p-4">Vigencia</th><th class="p-4">Estado</th><th class="p-4">Pago</th></tr></thead><tbody>@forelse($membresias as $membresia)<tr class="border-b border-white/5"><td class="p-4">{{ $membresia->cliente->nombre }}</td><td class="p-4">{{ $membresia->plan }}</td><td class="p-4 whitespace-nowrap">{{ $membresia->inicio->format('d/m/Y') }} - {{ $membresia->fin->format('d/m/Y') }}</td><td class="p-4"><span class="alpha-status {{ $membresia->estado === 'Vigente' ? 'alpha-status-active' : '' }}">{{ $membresia->estado }}</span></td><td class="p-4">${{ number_format((float)$membresia->importe, 2) }}@if($membresia->pago)<br><span class="text-xs text-gray-400">{{ $membresia->pago->pagado_en->format('d/m/Y H:i') }}</span>@endif</td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-gray-400">No hay membresías.</td></tr>@endforelse</tbody></table></div><div class="mt-5">{{ $membresias->links() }}</div>
@endsection

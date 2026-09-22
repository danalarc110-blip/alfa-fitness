@extends('layouts.app', ['active' => 'inicio'])
@section('title', $titulo)
@section('content')
<section class="alpha-hero">
    <div class="alpha-hero-copy">
        <p class="alpha-eyebrow mb-4">{{ $guard === 'web' ? 'Control del gimnasio' : 'Tu entrenamiento, a tu ritmo' }}</p>
        <h2 class="mb-3">Hola, {{ $nombre }}.</h2>
        <p class="alpha-hero-description text-gray-400 text-sm mb-6">Organiza tu día, sigue tus avances y haz que cada entrenamiento cuente.</p>
        <div class="flex flex-wrap gap-3">@foreach($acciones as [$ruta, $etiqueta])<a href="{{ route($ruta) }}" class="{{ $loop->first ? 'alpha-btn-primary' : 'alpha-btn-secondary' }} px-5 py-3">{{ $etiqueta }}</a>@endforeach</div>
    </div>
    <div class="alpha-hero-seal" aria-hidden="true"><img src="{{ asset('images/logo-sidebar.webp') }}" alt="" width="110" height="94"><p>Tu mejor versión</p></div>
</section>
<section class="alpha-metrics" aria-label="Resumen de actividad">
    @foreach($metricas as [$etiqueta, $valor])<article class="alpha-metric"><p>{{ $etiqueta }}</p><strong>{{ $valor }}</strong></article>@endforeach
</section>
<div class="alpha-home-panels">
    <section class="alpha-card overflow-hidden">
        <div class="alpha-panel-heading"><h2>Mis rutinas</h2><a href="{{ route('entrenamientos.index') }}">Ver todas →</a></div>
        @forelse($rutinas as $rutina)
            <a class="alpha-list-row" href="{{ route('entrenamientos.editar', $rutina) }}"><div class="flex items-center gap-4 min-w-0"><span class="alpha-row-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div class="min-w-0"><strong class="text-sm break-words">{{ $rutina->nombre }}</strong><p class="text-xs text-gray-400 mt-1">{{ $rutina->dias_count }} días · {{ $rutina->nivel }}</p></div></div><span aria-hidden="true">→</span></a>
        @empty
            <div class="p-8 text-center"><p class="font-medium mb-2">Tu primera rutina te espera</p><p class="text-sm text-gray-400 mb-5">Organiza tus ejercicios y días de entrenamiento.</p><form method="POST" action="{{ route('entrenamientos.crear') }}">@csrf<button class="alpha-btn-primary px-5 py-3">Crear mi rutina</button></form></div>
        @endforelse
    </section>
    @if($guard === 'cliente' || auth('web')->user()?->can('asistencia'))
    <section class="alpha-card overflow-hidden">
        <div class="alpha-panel-heading">
            <h2>{{ $guard === 'cliente' ? 'Mis visitas recientes' : 'Actividad reciente' }}</h2>
            @can('asistencia')<a href="{{ route('asistencia.index') }}">Ver historial →</a>@endcan
        </div>
        @forelse($actividad as $visita)
            <div class="alpha-list-row"><div><strong class="text-sm">{{ $guard === 'cliente' ? 'Entrada al gimnasio' : ($visita->cliente?->nombre ?? 'Miembro') }}</strong><p class="text-xs text-gray-400 mt-1">{{ $visita->fecha_hora->format('d/m/Y · H:i') }}</p></div><span class="alpha-status {{ !$visita->fecha_salida ? 'alpha-status-active' : '' }}">{{ $visita->fecha_salida ? 'Visita completada' : 'En el gimnasio' }}</span></div>
        @empty<p class="text-sm text-gray-400 py-10 text-center">{{ $guard === 'cliente' ? 'Tus visitas al gimnasio se registrarán aquí.' : 'Las próximas visitas aparecerán aquí.' }}</p>@endforelse
    </section>
    @endif
</div>
@if($porVencer->isNotEmpty())
<section class="alpha-card alpha-expiry p-6 mt-6"><h2 class="font-semibold mb-3">Planes que vencen en los próximos 7 días</h2>@foreach($porVencer as $plan)<a href="{{ route('membresias.index') }}" class="alpha-list-row text-sm"><span>{{ $plan->cliente->nombre }} · {{ $plan->plan }}</span><span class="text-yellow-400">{{ $plan->fin->format('d/m/Y') }}</span></a>@endforeach</section>
@endif
@endsection

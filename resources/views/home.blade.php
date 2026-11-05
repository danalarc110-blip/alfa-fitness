@extends('layouts.app', ['active' => 'inicio'])
@section('title', $titulo)
@section('content')
<section @class(['alpha-hero', 'alpha-hero-operaciones' => in_array($perfil, ['administrador', 'recepcion'], true)])>
    <div class="alpha-hero-copy">
        <h2 class="mb-3">Hola, {{ $nombre }}.</h2>
        <p class="alpha-hero-description text-gray-400 text-sm mb-6">{{ match ($perfil) {
            'administrador' => 'Consulta los ingresos, revisa las membresías y mantén el control de las operaciones del gimnasio.',
            'recepcion' => 'Registra las visitas, atiende las membresías y gestiona las ventas del día.',
            'entrenador' => 'Organiza tus rutinas y acompaña el progreso de tus clientes.',
            default => 'Organiza tu día, sigue tus avances y haz que cada entrenamiento cuente.',
        } }}</p>
        <div class="flex flex-wrap gap-3">@foreach($acciones as [$ruta, $etiqueta])<a href="{{ route($ruta) }}" class="{{ $loop->first ? 'alpha-btn-primary' : 'alpha-btn-secondary' }} px-5 py-3">{{ $etiqueta }}</a>@endforeach</div>
    </div>
    <div class="alpha-hero-seal" aria-hidden="true"><img src="{{ asset('images/logo-sidebar.webp') }}" alt="" width="110" height="94"><p>Tu mejor versión</p></div>
</section>
@if($perfil === 'administrador' && isset($finanzas))
    @include('partials.business-summary')
@endif
<section class="alpha-metrics" aria-label="Resumen de actividad">
    @foreach($metricas as [$etiqueta, $valor])<article class="alpha-metric"><p>{{ $etiqueta }}</p><strong>{{ $valor }}</strong></article>@endforeach
</section>

@if(isset($aforo))
<section class="alpha-card p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-white/10">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $aforo['bg'] }}" aria-hidden="true"></span>
                <span class="text-xs uppercase tracking-wider font-semibold {{ $aforo['color'] }}">Aforo actual</span>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">
                {{ $aforo['actual'] }} <span class="text-gray-400 font-normal text-base">/ {{ $aforo['capacidad'] }} personas en sala</span>
            </h2>
            <p class="text-xs text-gray-400 mt-1">Capacidad máxima orientativa: {{ $aforo['capacidad'] }} socios simultáneos.</p>
            <p class="text-xs text-gray-400 mt-1">Consulta de las {{ now()->format('H:i') }} · Recarga la página para actualizar.</p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $aforo['badge'] }}">
                {{ $aforo['estado'] }} ({{ $aforo['porcentaje'] }}%)
            </span>
            <div class="w-full sm:w-48 bg-white/5 rounded-full h-3 overflow-hidden border border-white/10 p-0.5" role="progressbar" aria-label="Porcentaje de aforo ocupado" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $aforo['porcentaje'] }}">
                <div class="h-full rounded-full transition-all duration-500 {{ $aforo['bar'] }}" style="width: {{ $aforo['porcentaje'] }}%"></div>
            </div>
        </div>
    </div>

    @if(isset($horasPico) && count($horasPico) > 0)
    <details class="alpha-peak-hours">
        <summary>Distribución de Horas Pico</summary>
        @if(array_sum(array_column($horasPico, 'total')) > 0)
    <div class="pt-2">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <p class="text-xs text-gray-400">Frecuencia histórica de asistencia (06:00 a 22:00) en los últimos 30 días</p>
            </div>
            <span class="text-[11px] text-amber-400/90 font-medium hidden sm:inline-flex items-center gap-1">
                ★ Pico histórico
            </span>
        </div>

        <div class="flex items-end gap-1 sm:gap-2 h-28 pt-4 pb-2 border-b border-white/5">
            @foreach($horasPico as $item)
                <div class="flex-1 flex flex-col items-center h-full justify-end group relative" title="{{ $item['hora'] }}: {{ $item['total'] }} asistencias">
                    <div class="w-full rounded-t transition-all duration-300 {{ $item['es_pico'] ? 'bg-yellow-400 shadow-sm shadow-yellow-400/30' : ($item['total'] > 0 ? 'bg-amber-400/40 hover:bg-amber-400/70' : 'bg-white/5') }}"
                         style="height: {{ $item['porcentaje'] }}%;"></div>
                    <span class="mt-2 text-[10px] text-gray-500 group-hover:text-white transition-colors {{ $item['es_pico'] ? 'text-yellow-400 font-bold' : '' }}">
                        {{ $item['hora_corta'] }}
                    </span>
                </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between text-[11px] text-gray-500 mt-2">
            <span>06:00</span>
            <span>18:00 - 20:00</span>
            <span>22:00</span>
        </div>
    </div>
        @else
            <p class="alpha-peak-empty">Aún no hay asistencias entre las 06:00 y las 22:00 en los últimos 30 días para mostrar los horarios habituales.</p>
        @endif
    </details>
    @endif
</section>
@endif

<div class="alpha-home-panels">
    @if(in_array($perfil, ['administrador', 'recepcion'], true))
    <section class="alpha-card overflow-hidden">
        <div class="alpha-panel-heading"><h2>Membresías por vencer</h2><a href="{{ route('membresias.index') }}">Ver membresías →</a></div>
        @forelse($porVencer as $plan)
            <a href="{{ route('membresias.index', ['q' => $plan->cliente->nombre]) }}" class="alpha-list-row text-sm"><div class="min-w-0"><strong>{{ $plan->cliente->nombre }}</strong><p class="text-xs text-gray-400 mt-1">{{ $plan->plan }}</p></div><span class="shrink-0 text-yellow-400">{{ $plan->fin->format('d/m/Y') }}</span></a>
        @empty
            <p class="p-6 text-sm text-gray-400">No hay membresías que venzan en los próximos 7 días.</p>
        @endforelse
    </section>
    @else
    <section class="alpha-card overflow-hidden">
        <div class="alpha-panel-heading"><h2>Mis rutinas</h2><a href="{{ route('entrenamientos.index') }}">Ver todas →</a></div>
        @forelse($rutinas as $rutina)
            <a class="alpha-list-row" href="{{ route('entrenamientos.editar', $rutina) }}"><div class="flex items-center gap-4 min-w-0"><span class="alpha-row-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div class="min-w-0"><strong class="text-sm break-words">{{ $rutina->nombre }}</strong><p class="text-xs text-gray-400 mt-1">{{ $rutina->dias_count }} días · {{ $rutina->nivel }}</p></div></div><span aria-hidden="true">→</span></a>
        @empty
            <div class="p-8 text-center"><p class="font-medium mb-2">Tu primera rutina te espera</p><p class="text-sm text-gray-400 mb-5">Organiza tus ejercicios y días de entrenamiento.</p><form method="POST" action="{{ route('entrenamientos.crear') }}">@csrf<button class="alpha-btn-primary px-5 py-3">Crear mi rutina</button></form></div>
        @endforelse
    </section>
    @endif
    @if($perfil === 'administrador')
    <section class="alpha-card overflow-hidden">
        <div class="alpha-panel-heading">
            <h2>Últimas ventas (TPV)</h2>
            <a href="{{ route('ventas.index') }}">Ver mostrador →</a>
        </div>
        @forelse($actividad as $venta)
            <a class="alpha-list-row" href="{{ route('ventas.comprobante', $venta) }}">
                <div class="min-w-0">
                    <strong class="text-sm font-semibold text-white">
                        #{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }} · {{ $venta->cliente?->nombre ?? 'Público general' }}
                    </strong>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $venta->created_at->format('d/m/Y · H:i') }} · {{ $venta->metodo_pago }}
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-sm font-bold text-yellow-400">${{ number_format($venta->total, 2) }}</span>
                </div>
            </a>
        @empty
            <div class="p-8 text-center">
                <p class="text-sm text-gray-400">No hay ventas registradas aún.</p>
                <a href="{{ route('ventas.index') }}" class="alpha-btn-primary px-4 py-2 text-xs font-semibold inline-block mt-3">Registrar primera venta</a>
            </div>
        @endforelse
    </section>
    @elseif($perfil === 'entrenador')
    <section class="alpha-card overflow-hidden">
        <div class="alpha-panel-heading">
            <h2>Ejercicios populares</h2>
            <a href="{{ route('ejercicios.index') }}">Catálogo completo →</a>
        </div>
        @forelse($actividad as $ej)
            <div class="alpha-list-row">
                <div class="min-w-0">
                    <strong class="text-sm font-semibold text-white truncate block">{{ $ej->nombre }}</strong>
                    <p class="text-xs text-gray-400 mt-1">{{ $ej->grupo_muscular }} · {{ $ej->conteo_votos }} {{ $ej->conteo_votos === 1 ? 'voto' : 'votos' }}</p>
                </div>
                <span class="alpha-status alpha-status-active shrink-0">
                    ★ {{ number_format($ej->promedio_estrellas, 1) }}
                </span>
            </div>
        @empty
            <div class="p-8 text-center">
                <p class="text-sm text-gray-400">No hay calificaciones de ejercicios registradas aún.</p>
            </div>
        @endforelse
    </section>
    @else
    <section class="alpha-card overflow-hidden">
        <div class="alpha-panel-heading">
            <h2>{{ $guard === 'cliente' ? 'Mis visitas recientes' : 'Actividad reciente' }}</h2>
            @if($guard === 'cliente')
                <a href="{{ route('cliente.asistencia') }}">Ver historial →</a>
            @else
                @can('asistencia')<a href="{{ route('asistencia.index') }}">Ver historial →</a>@endcan
            @endif
        </div>
        @forelse($actividad as $visita)
            <div class="alpha-list-row"><div><strong class="text-sm">{{ $guard === 'cliente' ? 'Entrada al gimnasio' : ($visita->cliente?->nombre ?? 'Miembro') }}</strong><p class="text-xs text-gray-400 mt-1">{{ $visita->fecha_hora->format('d/m/Y · H:i') }}</p></div><span class="alpha-status {{ !$visita->fecha_salida && !$visita->es_huerfana ? 'alpha-status-active' : '' }}">{{ $visita->fecha_salida ? 'Visita completada' : ($visita->es_huerfana ? 'Salida sin registrar' : 'En el gimnasio') }}</span></div>
        @empty<p class="text-sm text-gray-400 py-10 text-center">{{ $guard === 'cliente' ? 'Tus visitas al gimnasio se registrarán aquí.' : 'Las próximas visitas aparecerán aquí.' }}</p>@endforelse
    </section>
    @endif
</div>
@if($perfil === 'cliente' && $porVencer->isNotEmpty())
<section class="alpha-card alpha-expiry p-6 mt-6"><h2 class="font-semibold mb-3">Planes que vencen en los próximos 7 días</h2>@foreach($porVencer as $plan)<a href="{{ route('membresias.index') }}" class="alpha-list-row text-sm"><span>{{ $plan->cliente->nombre }} · {{ $plan->plan }}</span><span class="text-yellow-400">{{ $plan->fin->format('d/m/Y') }}</span></a>@endforeach</section>
@endif
@endsection

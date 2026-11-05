@php
    $negocio = $finanzas['datos'];
    $kpis = $negocio['kpis'];
    $periodoNegocio = $negocio['periodo'];
    $maximoDiario = max(1, ...array_column($negocio['serie_diaria'], 'total'));
    $consultaNegocio = $finanzas['preset'] === 'mes'
        ? ['preset' => 'mes', 'mes' => $finanzas['mes']]
        : ['preset' => $finanzas['preset'], 'desde' => $finanzas['desde'], 'hasta' => $finanzas['hasta']];
@endphp
<section id="resumen-negocio" class="alpha-card alpha-business" aria-labelledby="negocio-titulo">
    <div class="alpha-business-heading">
        <div>
            <h2 id="negocio-titulo">Cómo va el negocio</h2>
            <p>Ingresos del {{ $periodoNegocio['desde'] }} al {{ $periodoNegocio['hasta'] }} · USD</p>
        </div>
        <a href="{{ route('analitica.index', $consultaNegocio) }}">Ver informe completo →</a>
    </div>

    <nav class="alpha-business-presets" aria-label="Período de ingresos">
        @foreach(['hoy' => 'Hoy', 'este_mes' => 'Este mes', 'mes_anterior' => 'Mes anterior'] as $preset => $etiqueta)
            <a href="{{ route('dashboard', ['preset' => $preset]) }}#resumen-negocio"
                @if($finanzas['preset'] === $preset) aria-current="true" @endif>{{ $etiqueta }}</a>
        @endforeach
    </nav>
    <div class="alpha-business-filters">
        <form method="GET" action="{{ route('dashboard') }}#resumen-negocio" class="alpha-business-month">
            <input type="hidden" name="preset" value="mes">
            <label for="negocio-mes">Elegir un mes
                <input id="negocio-mes" name="mes" type="month" value="{{ $finanzas['mes'] }}" max="{{ now()->format('Y-m') }}" required>
            </label>
            <button type="submit" class="alpha-btn-secondary">Ver mes</button>
        </form>
        <form id="negocio-rango" method="GET" action="{{ route('dashboard') }}#resumen-negocio" aria-describedby="negocio-ayuda">
            <input type="hidden" name="preset" value="personalizado">
            <label for="negocio-desde">Desde
                <input id="negocio-desde" name="desde" type="date" value="{{ \App\Support\Formulario::anterior('desde', $finanzas['desde']) }}" max="{{ today()->toDateString() }}" required>
            </label>
            <label for="negocio-hasta">Hasta
                <input id="negocio-hasta" name="hasta" type="date" value="{{ \App\Support\Formulario::anterior('hasta', $finanzas['hasta']) }}" max="{{ today()->toDateString() }}" required>
            </label>
            <button type="submit" class="alpha-btn-primary">Comparar</button>
        </form>
    </div>
    <p id="negocio-ayuda" class="alpha-business-help">Para consultar un día, elige la misma fecha en Desde y Hasta. Máximo 90 días.</p>

    <dl class="alpha-business-totals">
        <div><dt>Ingresos del período</dt><dd>${{ number_format($kpis['ingresos_totales'], 2) }}</dd></div>
        <div><dt>Membresías cobradas</dt><dd>${{ number_format($kpis['ingresos_membresias'], 2) }}</dd></div>
        <div><dt>Ventas de productos</dt><dd>${{ number_format($kpis['ingresos_ventas'], 2) }}</dd></div>
        <div><dt>Entradas diarias</dt><dd>${{ number_format($kpis['ingresos_entradas'], 2) }}</dd></div>
    </dl>
    <div class="alpha-business-comparison">
        <p>
            <strong>{{ $kpis['diferencia_monetaria'] > 0 ? '+' : ($kpis['diferencia_monetaria'] < 0 ? '−' : '') }}${{ number_format(abs($kpis['diferencia_monetaria']), 2) }}</strong>
            @if($kpis['variacion_porcentual'] !== null)
                <span>({{ $kpis['variacion_porcentual'] > 0 ? '+' : '' }}{{ number_format($kpis['variacion_porcentual'], 1) }}%)</span>
            @else
                <span>· El período anterior no tuvo ingresos</span>
            @endif
            frente al período anterior
        </p>
        <p>Anterior: <strong>${{ number_format($kpis['prev_ingresos_totales'], 2) }}</strong> · {{ $periodoNegocio['prev_desde'] }} al {{ $periodoNegocio['prev_hasta'] }}. Se compara la misma cantidad de días{{ $periodoNegocio['abierto'] ? ' y hasta la misma hora' : '' }}.</p>
    </div>

    <div class="alpha-business-chart-heading">
        <h3>Ingresos por día</h3>
        <div class="alpha-business-legend" aria-hidden="true"><span>Membresías</span><span>Productos</span><span>Entradas</span></div>
    </div>
    @if($kpis['transacciones_totales'] > 0)
        <p class="alpha-business-help">Selecciona una barra para consultar ese día. Desplaza la gráfica para ver más fechas.</p>
        <div class="alpha-business-chart" tabindex="0" role="region" aria-label="Gráfica diaria de ingresos con desplazamiento horizontal">
            <div class="alpha-business-bars" style="min-width: {{ count($negocio['serie_diaria']) * 30 }}px">
                @foreach($negocio['serie_diaria'] as $dia)
                    <a data-finance-day class="alpha-business-day" href="{{ route('dashboard', ['preset' => 'personalizado', 'desde' => $dia['fecha'], 'hasta' => $dia['fecha']]) }}#resumen-negocio"
                        aria-label="{{ $dia['fecha_corta'] }}: total ${{ number_format($dia['total'], 2) }}, membresías ${{ number_format($dia['membresias'], 2) }}, productos ${{ number_format($dia['ventas'], 2) }}, entradas ${{ number_format($dia['entradas'], 2) }}. Consultar este día."
                        title="{{ $dia['fecha_corta'] }} · ${{ number_format($dia['total'], 2) }}">
                        <span class="alpha-business-bar-track" aria-hidden="true">
                            <span class="alpha-business-bar-memberships" style="height: {{ ($dia['membresias'] / $maximoDiario) * 100 }}%"></span>
                            <span class="alpha-business-bar-sales" style="height: {{ ($dia['ventas'] / $maximoDiario) * 100 }}%"></span>
                            <span class="alpha-business-bar-entries" style="height: {{ ($dia['entradas'] / $maximoDiario) * 100 }}%"></span>
                        </span>
                        <span class="alpha-business-date" aria-hidden="true">{{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m') }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <p class="alpha-business-empty">No hay cobros ni ventas registrados en estas fechas. Prueba otro mes o cambia el rango.</p>
    @endif
    <details class="alpha-business-detail">
        <summary>Ver importes por día</summary>
        <div class="alpha-business-table-scroll" tabindex="0" role="region" aria-label="Detalle diario de ingresos">
            <table>
                <caption class="sr-only">Ingresos diarios del período seleccionado en dólares estadounidenses</caption>
                <thead><tr><th scope="col">Fecha</th><th scope="col">Membresías</th><th scope="col">Productos</th><th scope="col">Entradas</th><th scope="col">Total</th></tr></thead>
                <tbody>@foreach($negocio['serie_diaria'] as $dia)<tr><th scope="row">{{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m/Y') }}</th><td>${{ number_format($dia['membresias'], 2) }}</td><td>${{ number_format($dia['ventas'], 2) }}</td><td>${{ number_format($dia['entradas'], 2) }}</td><td>${{ number_format($dia['total'], 2) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </details>
    <p class="alpha-business-note">Estos son ingresos cobrados. La ganancia neta requiere registrar también los costes y gastos del negocio.</p>
</section>

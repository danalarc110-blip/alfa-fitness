<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero Gerencial - Alpha Fitness</title>
    <style>
        @page {
            margin: 32px 32px 58px 32px;
            size: letter portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #e11d48;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .brand {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .brand span {
            color: #e11d48;
        }
        .subtitle {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-text {
            font-size: 10px;
            color: #64748b;
            text-align: right;
        }
        .disclaimer {
            background-color: #f8fafc;
            border-left: 3px solid #0f172a;
            padding: 6px 10px;
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            page-break-after: avoid;
        }
        /* Grid de KPIs */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 14px;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
            vertical-align: top;
            width: 25%;
        }
        .kpi-label {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        .kpi-sub {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }
        /* Tablas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table thead { display: table-header-group; }
        .data-table tr { page-break-inside: avoid; }
        .data-table tbody th {
            background-color: #ffffff;
            color: #1e293b;
            border: 0;
            border-bottom: 1px solid #e2e8f0;
            text-transform: none;
            font-size: 10px;
            padding: 5px 8px;
        }
        .data-table .totals td { border-top: 2px solid #0f172a; font-weight: 700; }
        .notes { padding-left: 16px; margin: 6px 0 14px; }
        .notes li { margin-bottom: 4px; page-break-inside: avoid; }
        .period-details { background-color: #f8fafc; padding: 8px 10px; margin-bottom: 12px; }
        .muted { color: #64748b; }
        .no-wrap { white-space: nowrap; }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: 700;
        }
        .footer {
            position: fixed;
            bottom: -36px;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 9px;
            color: #475569;
            text-align: center;
        }
        .page-number:after {
            content: counter(page);
        }
        .badge-positive {
            color: #059669;
            font-weight: 700;
        }
        .badge-negative {
            color: #dc2626;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="footer">
        Alpha Fitness · {{ $datos['reporte']['codigo'] }} · Uso gerencial · Página <span class="page-number"></span>
    </div>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    <div class="brand">ALPHA <span>FITNESS</span></div>
                    <div class="subtitle">Reporte Financiero Gerencial</div>
                </td>
                <td class="meta-text" style="width: 45%;">
                    <div><strong>{{ $datos['reporte']['codigo'] }}</strong></div>
                    <div><strong>Generado:</strong> {{ $datos['reporte']['generado_en'] }}</div>
                    <div>{{ $datos['reporte']['zona_horaria'] }} · Versión {{ $datos['reporte']['version'] }}</div>
                    <div><strong>Estado:</strong> {{ $datos['periodo']['abierto'] ? 'En curso' : 'Cerrado' }} · Importes en {{ $datos['reporte']['moneda'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="period-details">
        <div><strong>Período efectivo:</strong> {{ $datos['periodo']['desde'] }} - {{ $datos['periodo']['hasta'] }} ({{ $datos['periodo']['dias'] }} días). <strong>Hasta solicitado:</strong> {{ $datos['periodo']['solicitado_hasta'] }}.</div>
        <div><strong>Corte de datos:</strong> {{ $datos['periodo']['corte'] }}.</div>
        <div><strong>Período anterior:</strong> {{ $datos['periodo']['prev_desde'] }} - {{ $datos['periodo']['prev_hasta'] }}. <strong>Corte comparativo:</strong> {{ $datos['periodo']['comparacion_hasta'] }}.</div>
    </div>

    <div class="disclaimer">
        <strong>Aviso de Confidencialidad y Privacidad:</strong> Este reporte contiene información financiera agregada para la toma de decisiones gerenciales. No incluye nombres, teléfonos, correos ni datos personales de socios.
    </div>

    <h2 class="section-title">Resumen ejecutivo</h2>
    <ul class="notes">
        @foreach($datos['resumen_ejecutivo'] as $conclusion)
            <li>{{ $conclusion }}</li>
        @endforeach
    </ul>

    <!-- KPIS -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <div class="kpi-label">Ingresos Totales</div>
                <div class="kpi-value">${{ number_format($datos['kpis']['ingresos_totales'], 2) }}</div>
                <div class="kpi-sub {{ $datos['kpis']['variacion_porcentual'] === null ? 'muted' : ($datos['kpis']['diferencia_monetaria'] >= 0 ? 'badge-positive' : 'badge-negative') }}">
                    {{ $datos['kpis']['variacion_texto'] }}
                </div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Membresías</div>
                <div class="kpi-value">${{ number_format($datos['kpis']['ingresos_membresias'], 2) }}</div>
                <div class="kpi-sub">{{ $datos['kpis']['porcentaje_membresias'] }}% del total</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Ventas Mostrador</div>
                <div class="kpi-value">${{ number_format($datos['kpis']['ingresos_ventas'], 2) }}</div>
                <div class="kpi-sub">{{ $datos['kpis']['porcentaje_ventas'] }}% del total</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Ticket Promedio</div>
                <div class="kpi-value">${{ number_format($datos['kpis']['ticket_promedio'], 2) }}</div>
                <div class="kpi-sub">{{ $datos['kpis']['transacciones_totales'] }} transacciones</div>
            </td>
        </tr>
    </table>

    <h2 class="section-title">Comparativo de períodos</h2>
    <p class="muted">Diferencia = actual - anterior. La variación porcentual requiere una base anterior mayor a cero.</p>
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">Indicador</th>
                <th scope="col" class="text-right">Actual</th>
                <th scope="col" class="text-right">Anterior</th>
                <th scope="col" class="text-right">Diferencia</th>
                <th scope="col" class="text-right">Variación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['comparativo'] as $indicador)
                <tr>
                    <th scope="row">{{ $indicador['indicador'] }}</th>
                    @foreach(['actual', 'anterior', 'diferencia'] as $valor)
                        <td class="text-right no-wrap">{{ $indicador['formato'] === 'moneda' ? '$'.number_format($indicador[$valor], 2) : number_format($indicador[$valor], 0) }}</td>
                    @endforeach
                    <td class="text-right no-wrap">{{ $indicador['variacion'] === null ? 'No calculable' : number_format($indicador['variacion'], 1).'%' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- INDICADORES DESTACADOS -->
    <div class="section-title">Indicadores Gerenciales Clave</div>
    <table class="data-table">
        <tr>
            <td style="width: 25%;"><strong>Día con mayor ingreso:</strong></td>
            <td style="width: 25%;">{{ $datos['kpis']['dia_mayor_ingreso']['fecha'] }} (${{ number_format($datos['kpis']['dia_mayor_ingreso']['monto'], 2) }})</td>
            <td style="width: 25%;"><strong>Plan más recaudador:</strong></td>
            <td style="width: 25%;">{{ $datos['kpis']['plan_mayor_recaudacion'] }} (${{ number_format($datos['kpis']['plan_mayor_recaudacion_monto'], 2) }})</td>
        </tr>
        <tr>
            <td><strong>Día con menor ingreso positivo:</strong></td>
            <td>{{ $datos['kpis']['dia_menor_ingreso']['fecha'] }} (${{ number_format($datos['kpis']['dia_menor_ingreso']['monto'], 2) }})</td>
            <td><strong>Plan con más pagos:</strong></td>
            <td>{{ $datos['kpis']['plan_mayor_pagos'] }} ({{ $datos['kpis']['plan_mayor_pagos_cantidad'] }} operaciones)</td>
        </tr>
        <tr>
            <td><strong>Promedio por día con ingresos:</strong></td>
            <td>${{ number_format($datos['kpis']['promedio_ingreso_dia_activo'], 2) }}</td>
            <td><strong>Producto mayor facturación:</strong></td>
            <td>{{ $datos['kpis']['producto_mayor_facturacion'] }} (${{ number_format($datos['kpis']['producto_mayor_facturacion_monto'], 2) }})</td>
        </tr>
        <tr>
            <td><strong>Días con ingresos:</strong></td>
            <td>{{ $datos['kpis']['dias_activos'] }} de {{ $datos['periodo']['dias'] }} días</td>
            <td><strong>Producto más vendido en unidades:</strong></td>
            <td>{{ $datos['kpis']['producto_mas_vendido'] }} ({{ $datos['kpis']['producto_mas_vendido_unidades'] }} unid.)</td>
        </tr>
    </table>

    <!-- RENDIMIENTO DE MEMBRESÍAS -->
    <div class="section-title">Rendimiento por Plan de Membresía</div>
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">Plan de Membresía</th>
                <th scope="col" class="text-center">Pagos Registrados</th>
                <th scope="col" class="text-right">Total Ingresos</th>
                <th scope="col" class="text-right">% Membresías</th>
                <th scope="col" class="text-right">Promedio por Cobro</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos['planes'] as $plan)
                <tr>
                    <td class="font-bold">{{ $plan['plan'] }}</td>
                    <td class="text-center">{{ $plan['pagos'] }}</td>
                    <td class="text-right font-bold">${{ number_format($plan['ingresos'], 2) }}</td>
                    <td class="text-right">{{ $plan['porcentaje'] }}%</td>
                    <td class="text-right">${{ number_format($plan['promedio_operacion'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No se registraron cobros de membresías en este período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- RENDIMIENTO DE PRODUCTOS -->
    <div class="section-title">Rendimiento de Ventas por Producto</div>
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">Producto</th>
                <th scope="col" class="text-center">Unidades Vendidas</th>
                <th scope="col" class="text-center">Operaciones</th>
                <th scope="col" class="text-right">Total Ingresos</th>
                <th scope="col" class="text-right">% de Ventas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos['productos'] as $prod)
                <tr>
                    <td class="font-bold">{{ $prod['producto'] }}</td>
                    <td class="text-center">{{ $prod['unidades'] }}</td>
                    <td class="text-center">{{ $prod['operaciones'] }}</td>
                    <td class="text-right font-bold">${{ number_format($prod['ingresos'], 2) }}</td>
                    <td class="text-right">{{ $prod['porcentaje'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No se registraron ventas en el mostrador en este período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- MÉTODOS DE PAGO -->
    <div class="section-title">Distribución por Método de Pago / Origen</div>
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">Método / Canal registrado</th>
                <th scope="col">Origen</th>
                <th scope="col" class="text-center">Operaciones</th>
                <th scope="col" class="text-right">Total Ingresos</th>
                <th scope="col" class="text-right">% del Total General</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos['metodos'] as $m)
                <tr>
                    <td class="font-bold">{{ $m['metodo'] }}</td>
                    <td>{{ $m['tipo'] }}</td>
                    <td class="text-center">{{ $m['operaciones'] }}</td>
                    <td class="text-right font-bold">${{ number_format($m['ingresos'], 2) }}</td>
                    <td class="text-right">{{ $m['porcentaje'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Sin movimientos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section-title">Detalle diario de ingresos</h2>
    <p class="muted">Incluye todos los días del período efectivo, incluso aquellos sin operaciones.</p>
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">Fecha</th>
                <th scope="col" class="text-right">Membresías</th>
                <th scope="col" class="text-right">Ventas</th>
                <th scope="col" class="text-right">Total</th>
                <th scope="col" class="text-right">Operaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['serie_diaria'] as $dia)
                <tr>
                    <th scope="row">{{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m/Y') }}</th>
                    <td class="text-right">${{ number_format($dia['membresias'], 2) }}</td>
                    <td class="text-right">${{ number_format($dia['ventas'], 2) }}</td>
                    <td class="text-right font-bold">${{ number_format($dia['total'], 2) }}</td>
                    <td class="text-right">{{ number_format($dia['transacciones'], 0) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <th scope="row">Total del período</th>
                <td class="text-right">${{ number_format($datos['kpis']['ingresos_membresias'], 2) }}</td>
                <td class="text-right">${{ number_format($datos['kpis']['ingresos_ventas'], 2) }}</td>
                <td class="text-right">${{ number_format($datos['kpis']['ingresos_totales'], 2) }}</td>
                <td class="text-right">{{ number_format($datos['kpis']['transacciones_totales'], 0) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 class="section-title">Metodología y alcance</h2>
    <ul class="notes">
        @foreach($datos['metodologia'] as $nota)
            <li>{{ $nota }}</li>
        @endforeach
    </ul>
</body>
</html>

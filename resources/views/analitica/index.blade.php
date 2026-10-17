@extends('layouts.app', ['active' => 'analitica'])

@section('title', 'Analítica Financiera')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- CABECERA GERENCIAL Y BOTONES DE EXPORTACIÓN --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-5" style="border-color: var(--alpha-border);">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-500 border border-rose-500/20">
                    Módulo Gerencial
                </span>
                <span class="text-xs font-semibold" style="color: var(--alpha-muted);">{{ $datos['periodo']['abierto'] ? 'En curso' : 'Cerrado' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-1" style="color: var(--alpha-text);">
                Analítica <span class="text-rose-500">Financiera</span>
            </h1>
            <p class="text-sm mt-1" style="color: var(--alpha-muted);">
                Período efectivo: <strong style="color: var(--alpha-text);">{{ $datos['periodo']['desde'] }}</strong> al <strong style="color: var(--alpha-text);">{{ $datos['periodo']['hasta'] }}</strong>
                ({{ $datos['periodo']['dias'] }} días) · Comparado con: {{ $datos['periodo']['prev_desde'] }} al {{ $datos['periodo']['prev_hasta'] }}
            </p>
            <p class="text-xs mt-2" style="color: var(--alpha-muted);">
                Hasta solicitado: {{ $datos['periodo']['solicitado_hasta'] }} · Corte de datos: {{ $datos['periodo']['corte'] }}
                · Corte comparativo: {{ $datos['periodo']['comparacion_hasta'] }}
            </p>
            <p class="text-xs mt-1" style="color: var(--alpha-muted);">
                {{ $datos['reporte']['codigo'] }} · Generado: {{ $datos['reporte']['generado_en'] }}
                · {{ $datos['reporte']['zona_horaria'] }} · Importes en {{ $datos['reporte']['moneda'] }}
            </p>
        </div>

        {{-- BOTONES DE TEMA Y EXPORTACIÓN --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" onclick="alphaToggleTema()" title="Alternar tema claro / oscuro"
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center transition-all duration-150 active:scale-95 shadow-sm"
                style="background-color: var(--alpha-surface); color: var(--alpha-muted); border: 1px solid var(--alpha-border);">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            </button>
            <a href="{{ route('analitica.pdf', request()->query()) }}" target="_blank"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-red-600/10 text-red-500 border border-red-500/30 hover:bg-red-600 hover:text-white transition-all shadow-sm active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar PDF
            </a>
            <a href="{{ route('analitica.excel', request()->query()) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-emerald-600/10 text-emerald-600 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white transition-all shadow-sm active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel (.xlsx)
            </a>
        </div>
    </div>

    {{-- SELECTOR DE PERÍODOS Y FILTROS --}}
    <div class="alpha-card rounded-2xl p-4 sm:p-5">
        @if ($errors->has('periodo'))
            <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 text-xs sm:text-sm flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ $errors->first('periodo') }}</span>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Presets rápidos --}}
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                @php
                    $presets = [
                        'hoy' => 'Hoy',
                        'ayer' => 'Ayer',
                        'esta_semana' => 'Esta Semana',
                        'semana_anterior' => 'Semana Anterior',
                        'este_mes' => 'Este Mes',
                        'mes_anterior' => 'Mes Anterior',
                    ];
                @endphp

                @foreach($presets as $pKey => $pLabel)
                    <a href="{{ route('analitica.index', ['preset' => $pKey]) }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $presetActual === $pKey ? 'bg-rose-600 text-white shadow-sm font-semibold' : 'hover:opacity-80' }}"
                        @if($presetActual !== $pKey) style="background-color: var(--alpha-subtle); color: var(--alpha-text); border: 1px solid var(--alpha-border);" @endif>
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Formulario para Período Personalizado (Máximo 90 días) --}}
            <form action="{{ route('analitica.index') }}" method="GET" id="form-periodo" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="preset" value="personalizado">
                <div class="flex items-center gap-1.5 text-xs" style="color: var(--alpha-text);">
                    <label for="input-desde" class="font-medium">Desde:</label>
                    <input type="date" id="input-desde" name="desde" value="{{ $desdeInput }}" required
                        class="rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-rose-500"
                        style="background-color: var(--alpha-surface); color: var(--alpha-text); border: 1px solid var(--alpha-border);">
                </div>
                <div class="flex items-center gap-1.5 text-xs" style="color: var(--alpha-text);">
                    <label for="input-hasta" class="font-medium">Hasta:</label>
                    <input type="date" id="input-hasta" name="hasta" value="{{ $hastaInput }}" required
                        class="rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-rose-500"
                        style="background-color: var(--alpha-surface); color: var(--alpha-text); border: 1px solid var(--alpha-border);">
                </div>
                <button type="submit"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:opacity-90"
                    style="background-color: var(--alpha-primary); color: var(--alpha-on-primary);">
                    Consultar
                </button>
            </form>
        </div>
    </div>

    <section class="alpha-card rounded-2xl p-5 sm:p-6" aria-labelledby="resumen-ejecutivo">
        <h2 id="resumen-ejecutivo" class="text-lg font-bold" style="color: var(--alpha-text);">Resumen ejecutivo</h2>
        <ul class="mt-3 space-y-2 list-disc pl-5 text-sm" style="color: var(--alpha-text);">
            @foreach($datos['resumen_ejecutivo'] as $conclusion)
                <li>{{ $conclusion }}</li>
            @endforeach
        </ul>
    </section>

    {{-- KPIS PRINCIPALES --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Tarjeta 1: Ingresos Totales --}}
        <div class="alpha-card rounded-2xl p-5 relative overflow-hidden group transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase" style="color: var(--alpha-muted);">Ingresos Totales</span>
                <span class="p-2 rounded-xl bg-rose-500/10 text-rose-500 border border-rose-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black tracking-tight" style="color: var(--alpha-text);">
                    ${{ number_format($datos['kpis']['ingresos_totales'], 2) }}
                </div>
                <div class="mt-2 flex items-center gap-1.5 text-xs">
                    @if($datos['kpis']['variacion_porcentual'] === null)
                        <span class="font-semibold" style="color: var(--alpha-muted);">{{ $datos['kpis']['variacion_texto'] }}</span>
                    @elseif($datos['kpis']['diferencia_monetaria'] >= 0)
                        <span class="inline-flex items-center font-semibold" style="color: var(--alpha-success);">
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            {{ $datos['kpis']['variacion_texto'] }}
                        </span>
                    @else
                        <span class="inline-flex items-center font-semibold" style="color: var(--alpha-danger);">
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            {{ $datos['kpis']['variacion_texto'] }}
                        </span>
                    @endif
                    <span style="color: var(--alpha-muted);">vs período anterior (${{ number_format($datos['kpis']['prev_ingresos_totales'], 2) }})</span>
                </div>
            </div>
        </div>

        {{-- Tarjeta 2: Ingresos por Membresías --}}
        <div class="alpha-card rounded-2xl p-5 relative overflow-hidden group transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase" style="color: var(--alpha-muted);">Membresías</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-500 border border-purple-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black tracking-tight" style="color: var(--alpha-text);">
                    ${{ number_format($datos['kpis']['ingresos_membresias'], 2) }}
                </div>
                <div class="mt-2 text-xs flex items-center justify-between" style="color: var(--alpha-muted);">
                    <span>{{ $datos['kpis']['porcentaje_membresias'] }}% del total</span>
                    <span class="font-medium" style="color: var(--alpha-text);">{{ $datos['kpis']['transacciones_membresias'] }} cobros</span>
                </div>
            </div>
        </div>

        {{-- Tarjeta 3: Ingresos por Ventas --}}
        <div class="alpha-card rounded-2xl p-5 relative overflow-hidden group transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase" style="color: var(--alpha-muted);">Ventas Mostrador</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black tracking-tight" style="color: var(--alpha-text);">
                    ${{ number_format($datos['kpis']['ingresos_ventas'], 2) }}
                </div>
                <div class="mt-2 text-xs flex items-center justify-between" style="color: var(--alpha-muted);">
                    <span>{{ $datos['kpis']['porcentaje_ventas'] }}% del total</span>
                    <span class="font-medium" style="color: var(--alpha-text);">{{ $datos['kpis']['transacciones_ventas'] }} tickets</span>
                </div>
            </div>
        </div>

        {{-- Tarjeta 4: Ticket Promedio --}}
        <div class="alpha-card rounded-2xl p-5 relative overflow-hidden group transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase" style="color: var(--alpha-muted);">Ticket Promedio</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-500 border border-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black tracking-tight" style="color: var(--alpha-text);">
                    ${{ number_format($datos['kpis']['ticket_promedio'], 2) }}
                </div>
                <div class="mt-2 text-xs flex items-center justify-between" style="color: var(--alpha-muted);">
                    <span>{{ $datos['kpis']['transacciones_totales'] }} operaciones</span>
                    <span class="font-medium">Por transacción</span>
                </div>
            </div>
        </div>
    </div>

    <section class="alpha-card rounded-2xl p-5 sm:p-6" aria-labelledby="comparativo-periodos">
        <h2 id="comparativo-periodos" class="text-lg font-bold" style="color: var(--alpha-text);">Comparativo de períodos</h2>
        <p class="mt-1 mb-4 text-xs" style="color: var(--alpha-muted);">Diferencia = actual − anterior. La variación porcentual requiere una base anterior mayor a cero.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <caption class="sr-only">Indicadores del período efectivo y del período anterior, con diferencias absolutas y porcentuales</caption>
                <thead>
                    <tr class="border-b uppercase font-semibold text-[10px]" style="border-color: var(--alpha-border); color: var(--alpha-muted);">
                        <th scope="col" class="pb-3 pr-3">Indicador</th>
                        <th scope="col" class="pb-3 px-3 text-right">Actual</th>
                        <th scope="col" class="pb-3 px-3 text-right">Anterior</th>
                        <th scope="col" class="pb-3 px-3 text-right">Diferencia</th>
                        <th scope="col" class="pb-3 pl-3 text-right">Variación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datos['comparativo'] as $indicador)
                        <tr class="border-b" style="border-color: var(--alpha-border); color: var(--alpha-text);">
                            <th scope="row" class="py-3 pr-3 font-medium">{{ $indicador['indicador'] }}</th>
                            @foreach(['actual', 'anterior', 'diferencia'] as $valor)
                                <td class="py-3 px-3 text-right whitespace-nowrap">
                                    {{ $indicador['formato'] === 'moneda' ? '$'.number_format($indicador[$valor], 2) : number_format($indicador[$valor], 0) }}
                                </td>
                            @endforeach
                            <td class="py-3 pl-3 text-right whitespace-nowrap">
                                {{ $indicador['variacion'] === null ? 'No calculable' : number_format($indicador['variacion'], 1).'%' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- GRÁFICA: EVOLUCIÓN DE INGRESOS (DIARIA) --}}
    <div class="alpha-card rounded-2xl p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="text-lg font-bold tracking-tight" style="color: var(--alpha-text);">Evolución de Ingresos</h2>
                <p class="text-xs" style="color: var(--alpha-muted);">Desglose cronológico diario de recaudación por membresías y ventas</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium">
                <div class="flex items-center gap-1.5" style="color: var(--alpha-text);">
                    <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                    <span>Total</span>
                </div>
                <div class="flex items-center gap-1.5" style="color: var(--alpha-text);">
                    <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                    <span>Membresías</span>
                </div>
                <div class="flex items-center gap-1.5" style="color: var(--alpha-text);">
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                    <span>Ventas</span>
                </div>
            </div>
        </div>

        @php
            $maxMontoSerie = max(1, ...array_map(fn($d) => $d['total'], $datos['serie_diaria']));
        @endphp

        {{-- Contenedor de barras / gráfico adaptativo con scroll horizontal si son muchos días --}}
        <div class="overflow-x-auto pb-2">
            <div class="min-w-[650px] flex items-end gap-2 h-56 pt-6 px-2 border-b" style="border-color: var(--alpha-border);">
                @foreach($datos['serie_diaria'] as $dia)
                    @php
                        $porcentajeBarra = min(100, round(($dia['total'] / $maxMontoSerie) * 100));
                        $alturaMem = $dia['total'] > 0 ? round(($dia['membresias'] / $dia['total']) * 100) : 0;
                        $alturaVen = $dia['total'] > 0 ? round(($dia['ventas'] / $dia['total']) * 100) : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center h-full justify-end group relative" tabindex="0"
                         aria-label="{{ $dia['fecha'] }}: total {{ number_format($dia['total'], 2) }} USD; membresías {{ number_format($dia['membresias'], 2) }} USD; ventas {{ number_format($dia['ventas'], 2) }} USD; {{ $dia['transacciones'] }} operaciones">
                        {{-- Tooltip flotante --}}
                        <div class="absolute top-2 hidden group-hover:flex group-focus:flex flex-col items-center z-20 pointer-events-none"
                             style="{{ $loop->first ? 'left: 0;' : ($loop->last ? 'right: 0;' : 'left: 50%; transform: translateX(-50%);') }}">
                            <div class="bg-gray-950 text-white text-[10px] rounded-lg py-1.5 px-2.5 shadow-2xl border border-gray-700 whitespace-nowrap">
                                <div class="font-bold text-gray-200">{{ $dia['fecha_corta'] }} ({{ $dia['fecha'] }})</div>
                                <div class="text-rose-400 font-extrabold mt-0.5">Total: ${{ number_format($dia['total'], 2) }}</div>
                                <div class="text-purple-300">Membresías: ${{ number_format($dia['membresias'], 2) }}</div>
                                <div class="text-amber-300">Ventas: ${{ number_format($dia['ventas'], 2) }}</div>
                                <div class="text-gray-400">{{ $dia['transacciones'] }} operaciones</div>
                            </div>
                        </div>

                        {{-- Barra combinada adaptada al tema --}}
                        <div class="w-full max-w-[32px] rounded-t-md overflow-hidden flex flex-col-reverse transition-all duration-200 group-hover:brightness-110"
                             style="height: {{ max(4, $porcentajeBarra) }}%; background-color: var(--alpha-subtle); border-top: 1px solid var(--alpha-border); border-inline: 1px solid var(--alpha-border);">
                            @if($dia['ventas'] > 0)
                                <div class="w-full bg-amber-500" style="height: {{ $alturaVen }}%;"></div>
                            @endif
                            @if($dia['membresias'] > 0)
                                <div class="w-full bg-purple-500" style="height: {{ $alturaMem }}%;"></div>
                            @endif
                        </div>

                        {{-- Etiqueta del día --}}
                        <span class="text-[9px] mt-2 truncate w-full text-center" style="color: var(--alpha-muted);">
                            {{ $dia['fecha_corta'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ORIGEN DE LOS INGRESOS & INDICADORES GERENCIALES --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ORIGEN DE LOS INGRESOS --}}
        <div class="alpha-card rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold tracking-tight" style="color: var(--alpha-text);">Origen de los Ingresos</h3>
                <p class="text-xs mt-0.5" style="color: var(--alpha-muted);">Proporción recaudada por canal operativo</p>

                <div class="mt-6 space-y-4">
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-purple-500">Membresías</span>
                            <span style="color: var(--alpha-text);">${{ number_format($datos['kpis']['ingresos_membresias'], 2) }} ({{ $datos['kpis']['porcentaje_membresias'] }}%)</span>
                        </div>
                        <div class="w-full rounded-full h-3 overflow-hidden p-0.5 border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                            <div class="bg-purple-500 h-2 rounded-full transition-all duration-500" style="width: {{ $datos['kpis']['porcentaje_membresias'] }}%;"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-amber-500">Ventas Mostrador (TPV)</span>
                            <span style="color: var(--alpha-text);">${{ number_format($datos['kpis']['ingresos_ventas'], 2) }} ({{ $datos['kpis']['porcentaje_ventas'] }}%)</span>
                        </div>
                        <div class="w-full rounded-full h-3 overflow-hidden p-0.5 border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                            <div class="bg-amber-500 h-2 rounded-full transition-all duration-500" style="width: {{ $datos['kpis']['porcentaje_ventas'] }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t text-xs space-y-2" style="border-color: var(--alpha-border); color: var(--alpha-muted);">
                <div class="flex justify-between">
                    <span>Recaudación Membresías:</span>
                    <strong style="color: var(--alpha-text);">{{ $datos['kpis']['transacciones_membresias'] }} cuotas cobradas</strong>
                </div>
                <div class="flex justify-between">
                    <span>Recaudación Ventas:</span>
                    <strong style="color: var(--alpha-text);">{{ $datos['kpis']['transacciones_ventas'] }} ventas mostrador</strong>
                </div>
            </div>
        </div>

        {{-- RESUMEN DE INDICADORES CLAVE (2 Columnas en lg) --}}
        <div class="lg:col-span-2 alpha-card rounded-2xl p-5 sm:p-6">
            <h3 class="text-base font-bold tracking-tight mb-4" style="color: var(--alpha-text);">Indicadores Gerenciales Clave</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                <div class="p-3.5 rounded-xl border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                    <span class="block font-medium" style="color: var(--alpha-muted);">Día con mayor ingreso</span>
                    <div class="text-base font-bold mt-1" style="color: var(--alpha-text);">${{ number_format($datos['kpis']['dia_mayor_ingreso']['monto'], 2) }}</div>
                    <span class="text-[11px] font-semibold text-rose-500">{{ $datos['kpis']['dia_mayor_ingreso']['fecha'] }}</span>
                </div>

                <div class="p-3.5 rounded-xl border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                    <span class="block font-medium" style="color: var(--alpha-muted);">Día con menor ingreso positivo</span>
                    <div class="text-base font-bold mt-1" style="color: var(--alpha-text);">${{ number_format($datos['kpis']['dia_menor_ingreso']['monto'], 2) }}</div>
                    <span class="text-[11px]" style="color: var(--alpha-muted);">{{ $datos['kpis']['dia_menor_ingreso']['fecha'] }}</span>
                </div>

                <div class="p-3.5 rounded-xl border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                    <span class="block font-medium" style="color: var(--alpha-muted);">Promedio por día con ingresos</span>
                    <div class="text-base font-bold mt-1" style="color: var(--alpha-text);">${{ number_format($datos['kpis']['promedio_ingreso_dia_activo'], 2) }}</div>
                    <span class="text-[11px]" style="color: var(--alpha-muted);">{{ $datos['kpis']['dias_activos'] }} días con ingresos</span>
                </div>

                <div class="p-3.5 rounded-xl border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                    <span class="block font-medium" style="color: var(--alpha-muted);">Promedio transacciones / día</span>
                    <div class="text-base font-bold mt-1" style="color: var(--alpha-text);">{{ $datos['kpis']['promedio_transacciones_dia'] }} op/día</div>
                    <span class="text-[11px]" style="color: var(--alpha-muted);">En {{ $datos['periodo']['dias'] }} días analizados</span>
                </div>

                <div class="p-3.5 rounded-xl border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                    <span class="block font-medium" style="color: var(--alpha-muted);">Plan con mayor recaudación</span>
                    <div class="text-base font-bold mt-1" style="color: var(--alpha-text);">{{ $datos['kpis']['plan_mayor_recaudacion'] }}</div>
                    <span class="text-[11px] font-semibold text-purple-500">${{ number_format($datos['kpis']['plan_mayor_recaudacion_monto'], 2) }} recaudados</span>
                </div>

                <div class="p-3.5 rounded-xl border" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border);">
                    <span class="block font-medium" style="color: var(--alpha-muted);">Producto mayor facturación</span>
                    <div class="text-base font-bold mt-1" style="color: var(--alpha-text);">{{ $datos['kpis']['producto_mayor_facturacion'] }}</div>
                    <span class="text-[11px] font-semibold text-amber-500">${{ number_format($datos['kpis']['producto_mayor_facturacion_monto'], 2) }} facturados</span>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLAS: RENDIMIENTO DE MEMBRESÍAS & RENDIMIENTO DE VENTAS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- RENDIMIENTO DE MEMBRESÍAS --}}
        <div class="alpha-card rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold tracking-tight" style="color: var(--alpha-text);">Rendimiento de Membresías</h3>
                        <p class="text-xs" style="color: var(--alpha-muted);">Recaudación real agregada por tipo de plan</p>
                    </div>
                    <span class="px-2 py-1 rounded-md text-[10px] font-semibold bg-purple-500/10 text-purple-500 border border-purple-500/20">
                        Total: ${{ number_format($datos['kpis']['ingresos_membresias'], 2) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b uppercase font-semibold text-[10px]" style="border-color: var(--alpha-border); color: var(--alpha-muted);">
                                <th scope="col" class="pb-3">Plan</th>
                                <th scope="col" class="pb-3 text-center">Pagos</th>
                                <th scope="col" class="pb-3 text-right">Ingresos</th>
                                <th scope="col" class="pb-3 text-right">% Membresías</th>
                                <th scope="col" class="pb-3 text-right">Prom./Cobro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--alpha-border);">
                            @forelse($datos['planes'] as $plan)
                                <tr class="transition-colors hover:bg-black/5 dark:hover:bg-white/5" style="border-color: var(--alpha-border);">
                                    <td class="py-3 font-semibold" style="color: var(--alpha-text);">{{ $plan['plan'] }}</td>
                                    <td class="py-3 text-center" style="color: var(--alpha-muted);">{{ $plan['pagos'] }}</td>
                                    <td class="py-3 text-right font-bold text-purple-500">${{ number_format($plan['ingresos'], 2) }}</td>
                                    <td class="py-3 text-right" style="color: var(--alpha-muted);">{{ $plan['porcentaje'] }}%</td>
                                    <td class="py-3 text-right" style="color: var(--alpha-muted);">${{ number_format($plan['promedio_operacion'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center" style="color: var(--alpha-muted);">Sin pagos de membresía registrados en este período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RENDIMIENTO DE VENTAS --}}
        <div class="alpha-card rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold tracking-tight" style="color: var(--alpha-text);">Rendimiento de Productos</h3>
                        <p class="text-xs" style="color: var(--alpha-muted);">Facturación y unidades vendidas en mostrador</p>
                    </div>
                    <span class="px-2 py-1 rounded-md text-[10px] font-semibold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                        Total: ${{ number_format($datos['kpis']['ingresos_ventas'], 2) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b uppercase font-semibold text-[10px]" style="border-color: var(--alpha-border); color: var(--alpha-muted);">
                                <th scope="col" class="pb-3">Producto</th>
                                <th scope="col" class="pb-3 text-center">Unidades</th>
                                <th scope="col" class="pb-3 text-center">Tickets</th>
                                <th scope="col" class="pb-3 text-right">Ingresos</th>
                                <th scope="col" class="pb-3 text-right">% Ventas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--alpha-border);">
                            @forelse($datos['productos'] as $prod)
                                <tr class="transition-colors hover:bg-black/5 dark:hover:bg-white/5" style="border-color: var(--alpha-border);">
                                    <td class="py-3 font-semibold" style="color: var(--alpha-text);">{{ $prod['producto'] }}</td>
                                    <td class="py-3 text-center" style="color: var(--alpha-muted);">{{ $prod['unidades'] }}</td>
                                    <td class="py-3 text-center" style="color: var(--alpha-muted);">{{ $prod['operaciones'] }}</td>
                                    <td class="py-3 text-right font-bold text-amber-500">${{ number_format($prod['ingresos'], 2) }}</td>
                                    <td class="py-3 text-right" style="color: var(--alpha-muted);">{{ $prod['porcentaje'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center" style="color: var(--alpha-muted);">Sin ventas en mostrador registradas en este período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- INGRESOS POR MÉTODO DE PAGO --}}
    <div class="alpha-card rounded-2xl p-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold tracking-tight" style="color: var(--alpha-text);">Ingresos por Método de Pago / Canal</h3>
                <p class="text-xs" style="color: var(--alpha-muted);">Distribución de recaudación según el medio utilizado en cada operación</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b uppercase font-semibold text-[10px]" style="border-color: var(--alpha-border); color: var(--alpha-muted);">
                        <th scope="col" class="pb-3">Método / Canal</th>
                        <th scope="col" class="pb-3">Origen</th>
                        <th scope="col" class="pb-3 text-center">Cantidad de Operaciones</th>
                        <th scope="col" class="pb-3 text-right">Monto Registrado</th>
                        <th scope="col" class="pb-3 text-right">% del Total General</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--alpha-border);">
                    @forelse($datos['metodos'] as $m)
                        <tr class="transition-colors hover:bg-black/5 dark:hover:bg-white/5" style="border-color: var(--alpha-border);">
                            <td class="py-3 font-semibold" style="color: var(--alpha-text);">{{ $m['metodo'] }}</td>
                            <td class="py-3" style="color: var(--alpha-muted);">{{ $m['tipo'] }}</td>
                            <td class="py-3 text-center" style="color: var(--alpha-muted);">{{ $m['operaciones'] }}</td>
                            <td class="py-3 text-right font-bold text-emerald-500">${{ number_format($m['ingresos'], 2) }}</td>
                            <td class="py-3 text-right" style="color: var(--alpha-muted);">{{ $m['porcentaje'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center" style="color: var(--alpha-muted);">Sin operaciones financieras registradas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <section class="alpha-card rounded-2xl p-5 sm:p-6" aria-labelledby="metodologia-reporte">
        <h2 id="metodologia-reporte" class="text-base font-bold" style="color: var(--alpha-text);">Metodología y alcance</h2>
        <ul class="mt-3 space-y-2 list-disc pl-5 text-xs" style="color: var(--alpha-muted);">
            @foreach($datos['metodologia'] as $nota)
                <li>{{ $nota }}</li>
            @endforeach
        </ul>
        <p class="mt-4 text-xs" style="color: var(--alpha-muted);">Versión del reporte: {{ $datos['reporte']['version'] }} · Información consolidada al momento de la consulta.</p>
    </section>

    {{-- AVISO DE PRIVACIDAD / DISCLAIMER --}}
    <div class="rounded-xl p-4 border text-xs flex items-center justify-between gap-4" style="background-color: var(--alpha-subtle); border-color: var(--alpha-border); color: var(--alpha-muted);">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span><strong style="color: var(--alpha-text);">Uso gerencial:</strong> Información financiera agregada, sin datos personales ni historiales individuales de socios.</span>
        </div>
        <span class="text-[11px] shrink-0" style="color: var(--alpha-muted);">Alpha Fitness</span>
    </div>

</div>

<script>
    // Validación en el Frontend: Máximo 90 días por consulta
    document.getElementById('form-periodo')?.addEventListener('submit', function (e) {
        const desde = document.getElementById('input-desde').value;
        const hasta = document.getElementById('input-hasta').value;

        if (!desde || !hasta) {
            e.preventDefault();
            alert('Por favor ingrese fecha inicial y final.');
            return;
        }

        const fDesde = new Date(desde);
        const fHasta = new Date(hasta);

        if (fDesde > fHasta) {
            e.preventDefault();
            alert('La fecha inicial no puede ser posterior a la fecha final.');
            return;
        }

        const diffTime = Math.abs(fHasta - fDesde);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

        if (diffDays > 90) {
            e.preventDefault();
            alert('El período máximo permitido para un reporte es de 90 días.');
        }
    });
</script>
@endsection

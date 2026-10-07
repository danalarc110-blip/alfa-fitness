@extends('layouts.app', ['active' => 'analitica'])

@section('title', 'Analítica Financiera')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- CABECERA GERENCIAL Y BOTONES DE EXPORTACIÓN --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/10 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    Módulo Gerencial
                </span>
                <span class="text-xs text-gray-400">Datos consolidados en tiempo real</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1">
                Analítica <span class="text-rose-500">Financiera</span>
            </h1>
            <p class="text-sm text-gray-400 mt-1">
                Período actual: <strong class="text-gray-200">{{ $datos['periodo']['desde'] }}</strong> al <strong class="text-gray-200">{{ $datos['periodo']['hasta'] }}</strong>
                ({{ $datos['periodo']['dias'] }} días) · Comparado con: {{ $datos['periodo']['prev_desde'] }} al {{ $datos['periodo']['prev_hasta'] }}
            </p>
        </div>

        {{-- BOTONES DE EXPORTACIÓN --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('analitica.pdf', request()->query()) }}" target="_blank"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-red-600/20 text-red-300 border border-red-500/30 hover:bg-red-600 hover:text-white hover:border-red-600 transition-all shadow-sm active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar PDF
            </a>
            <a href="{{ route('analitica.excel', request()->query()) }}"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all shadow-sm active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel (.xlsx)
            </a>
        </div>
    </div>

    {{-- SELECTOR DE PERÍODOS Y FILTROS --}}
    <div class="bg-gray-900/60 border border-white/10 rounded-2xl p-4 sm:p-5 backdrop-blur-sm">
        @if ($errors->has('periodo'))
            <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs sm:text-sm flex items-center gap-2">
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
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $presetActual === $pKey ? 'bg-rose-600 text-white shadow-sm font-semibold' : 'bg-white/5 text-gray-300 hover:bg-white/10 hover:text-white' }}">
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Formulario para Período Personalizado (Máximo 90 días) --}}
            <form action="{{ route('analitica.index') }}" method="GET" id="form-periodo" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="preset" value="personalizado">
                <div class="flex items-center gap-1.5 text-xs text-gray-300">
                    <label for="input-desde" class="font-medium">Desde:</label>
                    <input type="date" id="input-desde" name="desde" value="{{ $desdeInput }}" required
                        class="bg-black/60 border border-white/15 rounded-lg px-2.5 py-1 text-xs text-white focus:outline-none focus:border-rose-500">
                </div>
                <div class="flex items-center gap-1.5 text-xs text-gray-300">
                    <label for="input-hasta" class="font-medium">Hasta:</label>
                    <input type="date" id="input-hasta" name="hasta" value="{{ $hastaInput }}" required
                        class="bg-black/60 border border-white/15 rounded-lg px-2.5 py-1 text-xs text-white focus:outline-none focus:border-rose-500">
                </div>
                <button type="submit"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/10 hover:bg-white/20 text-white transition-all border border-white/10">
                    Consultar
                </button>
            </form>
        </div>
    </div>

    {{-- KPIS PRINCIPALES --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Tarjeta 1: Ingresos Totales --}}
        <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 relative overflow-hidden group hover:border-white/20 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase text-gray-400">Ingresos Totales</span>
                <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-white tracking-tight">
                    ${{ number_format($datos['kpis']['ingresos_totales'], 2) }}
                </div>
                <div class="mt-2 flex items-center gap-1.5 text-xs">
                    @if($datos['kpis']['diferencia_monetaria'] >= 0)
                        <span class="inline-flex items-center text-emerald-400 font-semibold">
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            {{ $datos['kpis']['variacion_texto'] }}
                        </span>
                    @else
                        <span class="inline-flex items-center text-red-400 font-semibold">
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            {{ $datos['kpis']['variacion_texto'] }}
                        </span>
                    @endif
                    <span class="text-gray-500">vs período anterior (${{ number_format($datos['kpis']['prev_ingresos_totales'], 2) }})</span>
                </div>
            </div>
        </div>

        {{-- Tarjeta 2: Ingresos por Membresías --}}
        <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 relative overflow-hidden group hover:border-white/20 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase text-gray-400">Membresías</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-white tracking-tight">
                    ${{ number_format($datos['kpis']['ingresos_membresias'], 2) }}
                </div>
                <div class="mt-2 text-xs text-gray-400 flex items-center justify-between">
                    <span>{{ $datos['kpis']['porcentaje_membresias'] }}% del total</span>
                    <span class="text-gray-300 font-medium">{{ $datos['kpis']['transacciones_membresias'] }} cobros</span>
                </div>
            </div>
        </div>

        {{-- Tarjeta 3: Ingresos por Ventas --}}
        <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 relative overflow-hidden group hover:border-white/20 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase text-gray-400">Ventas Mostrador</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-white tracking-tight">
                    ${{ number_format($datos['kpis']['ingresos_ventas'], 2) }}
                </div>
                <div class="mt-2 text-xs text-gray-400 flex items-center justify-between">
                    <span>{{ $datos['kpis']['porcentaje_ventas'] }}% del total</span>
                    <span class="text-gray-300 font-medium">{{ $datos['kpis']['transacciones_ventas'] }} tickets</span>
                </div>
            </div>
        </div>

        {{-- Tarjeta 4: Ticket Promedio --}}
        <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 relative overflow-hidden group hover:border-white/20 transition-all shadow-lg">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wider uppercase text-gray-400">Ticket Promedio</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-black text-white tracking-tight">
                    ${{ number_format($datos['kpis']['ticket_promedio'], 2) }}
                </div>
                <div class="mt-2 text-xs text-gray-400 flex items-center justify-between">
                    <span>{{ $datos['kpis']['transacciones_totales'] }} operaciones</span>
                    <span class="text-gray-400 font-medium">Por transacción</span>
                </div>
            </div>
        </div>
    </div>

    {{-- GRÁFICA: EVOLUCIÓN DE INGRESOS (DIARIA) --}}
    <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 sm:p-6 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight">Evolución de Ingresos</h2>
                <p class="text-xs text-gray-400">Desglose cronológico diario de recaudación por membresías y ventas</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-medium">
                <div class="flex items-center gap-1.5 text-gray-300">
                    <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                    <span>Total</span>
                </div>
                <div class="flex items-center gap-1.5 text-gray-300">
                    <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                    <span>Membresías</span>
                </div>
                <div class="flex items-center gap-1.5 text-gray-300">
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
            <div class="min-w-[650px] flex items-end gap-2 h-56 pt-6 px-2 border-b border-white/10">
                @foreach($datos['serie_diaria'] as $dia)
                    @php
                        $porcentajeBarra = min(100, round(($dia['total'] / $maxMontoSerie) * 100));
                        $alturaMem = $dia['total'] > 0 ? round(($dia['membresias'] / $dia['total']) * 100) : 0;
                        $alturaVen = $dia['total'] > 0 ? round(($dia['ventas'] / $dia['total']) * 100) : 0;
                    @endphp
                    <div class="flex-1 flex flex-col items-center h-full justify-end group relative">
                        {{-- Tooltip flotante --}}
                        <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-20 pointer-events-none">
                            <div class="bg-black/95 text-white text-[10px] rounded-lg py-1.5 px-2.5 shadow-2xl border border-white/20 whitespace-nowrap">
                                <div class="font-bold text-gray-200">{{ $dia['fecha_corta'] }} ({{ $dia['fecha'] }})</div>
                                <div class="text-rose-400 font-extrabold mt-0.5">Total: ${{ number_format($dia['total'], 2) }}</div>
                                <div class="text-purple-300">Membresías: ${{ number_format($dia['membresias'], 2) }}</div>
                                <div class="text-amber-300">Ventas: ${{ number_format($dia['ventas'], 2) }}</div>
                                <div class="text-gray-400">{{ $dia['transacciones'] }} operaciones</div>
                            </div>
                        </div>

                        {{-- Barra combinada --}}
                        <div class="w-full max-w-[32px] rounded-t-md overflow-hidden bg-white/5 flex flex-col-reverse transition-all duration-200 group-hover:brightness-125"
                             style="height: {{ max(4, $porcentajeBarra) }}%;">
                            @if($dia['ventas'] > 0)
                                <div class="w-full bg-amber-500" style="height: {{ $alturaVen }}%;"></div>
                            @endif
                            @if($dia['membresias'] > 0)
                                <div class="w-full bg-purple-500" style="height: {{ $alturaMem }}%;"></div>
                            @endif
                        </div>

                        {{-- Etiqueta del día --}}
                        <span class="text-[9px] text-gray-400 mt-2 truncate w-full text-center">
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
        <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 sm:p-6 shadow-xl flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-white tracking-tight">Origen de los Ingresos</h3>
                <p class="text-xs text-gray-400 mt-0.5">Proporción recaudada por canal operativo</p>

                <div class="mt-6 space-y-4">
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-purple-400">Membresías</span>
                            <span class="text-white">${{ number_format($datos['kpis']['ingresos_membresias'], 2) }} ({{ $datos['kpis']['porcentaje_membresias'] }}%)</span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-3 overflow-hidden">
                            <div class="bg-purple-500 h-3 rounded-full transition-all duration-500" style="width: {{ $datos['kpis']['porcentaje_membresias'] }}%;"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-amber-400">Ventas Mostrador (TPV)</span>
                            <span class="text-white">${{ number_format($datos['kpis']['ingresos_ventas'], 2) }} ({{ $datos['kpis']['porcentaje_ventas'] }}%)</span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-3 overflow-hidden">
                            <div class="bg-amber-500 h-3 rounded-full transition-all duration-500" style="width: {{ $datos['kpis']['porcentaje_ventas'] }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-white/10 text-xs text-gray-400 space-y-2">
                <div class="flex justify-between">
                    <span>Recaudación Membresías:</span>
                    <strong class="text-gray-200">{{ $datos['kpis']['transacciones_membresias'] }} cuotas cobradas</strong>
                </div>
                <div class="flex justify-between">
                    <span>Recaudación Ventas:</span>
                    <strong class="text-gray-200">{{ $datos['kpis']['transacciones_ventas'] }} ventas mostrador</strong>
                </div>
            </div>
        </div>

        {{-- RESUMEN DE INDICADORES CLAVE (2 Columnas en lg) --}}
        <div class="lg:col-span-2 bg-gray-900/80 border border-white/10 rounded-2xl p-5 sm:p-6 shadow-xl">
            <h3 class="text-base font-bold text-white tracking-tight mb-4">Indicadores Gerenciales Clave</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-gray-400 block font-medium">Día con mayor ingreso</span>
                    <div class="text-base font-bold text-white mt-1">${{ number_format($datos['kpis']['dia_mayor_ingreso']['monto'], 2) }}</div>
                    <span class="text-[11px] text-rose-400">{{ $datos['kpis']['dia_mayor_ingreso']['fecha'] }}</span>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-gray-400 block font-medium">Día con menor ingreso (activo)</span>
                    <div class="text-base font-bold text-white mt-1">${{ number_format($datos['kpis']['dia_menor_ingreso']['monto'], 2) }}</div>
                    <span class="text-[11px] text-gray-400">{{ $datos['kpis']['dia_menor_ingreso']['fecha'] }}</span>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-gray-400 block font-medium">Promedio por día activo</span>
                    <div class="text-base font-bold text-white mt-1">${{ number_format($datos['kpis']['promedio_ingreso_dia_activo'], 2) }}</div>
                    <span class="text-[11px] text-gray-400">{{ $datos['kpis']['dias_activos'] }} días con operaciones</span>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-gray-400 block font-medium">Promedio transacciones / día</span>
                    <div class="text-base font-bold text-white mt-1">{{ $datos['kpis']['promedio_transacciones_dia'] }} op/día</div>
                    <span class="text-[11px] text-gray-400">En {{ $datos['periodo']['dias'] }} días analizados</span>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-gray-400 block font-medium">Plan con mayor recaudación</span>
                    <div class="text-base font-bold text-white mt-1">{{ $datos['kpis']['plan_mayor_recaudacion'] }}</div>
                    <span class="text-[11px] text-purple-400">${{ number_format($datos['kpis']['plan_mayor_recaudacion_monto'], 2) }} recaudados</span>
                </div>

                <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <span class="text-gray-400 block font-medium">Producto mayor facturación</span>
                    <div class="text-base font-bold text-white mt-1">{{ $datos['kpis']['producto_mayor_facturacion'] }}</div>
                    <span class="text-[11px] text-amber-400">${{ number_format($datos['kpis']['producto_mayor_facturacion_monto'], 2) }} facturados</span>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLAS: RENDIMIENTO DE MEMBRESÍAS & RENDIMIENTO DE VENTAS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- RENDIMIENTO DE MEMBRESÍAS --}}
        <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 sm:p-6 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-white tracking-tight">Rendimiento de Membresías</h3>
                        <p class="text-xs text-gray-400">Recaudación real agregada por tipo de plan</p>
                    </div>
                    <span class="px-2 py-1 rounded-md text-[10px] font-semibold bg-purple-500/10 text-purple-300 border border-purple-500/20">
                        Total: ${{ number_format($datos['kpis']['ingresos_membresias'], 2) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/10 text-gray-400 uppercase font-semibold text-[10px]">
                                <th class="pb-3">Plan</th>
                                <th class="pb-3 text-center">Pagos</th>
                                <th class="pb-3 text-right">Ingresos</th>
                                <th class="pb-3 text-right">% Membresías</th>
                                <th class="pb-3 text-right">Prom./Cobro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($datos['planes'] as $plan)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="py-3 font-semibold text-white">{{ $plan['plan'] }}</td>
                                    <td class="py-3 text-center text-gray-300">{{ $plan['pagos'] }}</td>
                                    <td class="py-3 text-right font-bold text-purple-300">${{ number_format($plan['ingresos'], 2) }}</td>
                                    <td class="py-3 text-right text-gray-300">{{ $plan['porcentaje'] }}%</td>
                                    <td class="py-3 text-right text-gray-400">${{ number_format($plan['promedio_operacion'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500">Sin pagos de membresía registrados en este período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RENDIMIENTO DE VENTAS --}}
        <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 sm:p-6 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-white tracking-tight">Rendimiento de Productos (Top)</h3>
                        <p class="text-xs text-gray-400">Facturación y unidades vendidas en mostrador</p>
                    </div>
                    <span class="px-2 py-1 rounded-md text-[10px] font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                        Total: ${{ number_format($datos['kpis']['ingresos_ventas'], 2) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/10 text-gray-400 uppercase font-semibold text-[10px]">
                                <th class="pb-3">Producto</th>
                                <th class="pb-3 text-center">Unidades</th>
                                <th class="pb-3 text-center">Tickets</th>
                                <th class="pb-3 text-right">Ingresos</th>
                                <th class="pb-3 text-right">% Ventas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($datos['productos'] as $prod)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="py-3 font-semibold text-white">{{ $prod['producto'] }}</td>
                                    <td class="py-3 text-center text-gray-300">{{ $prod['unidades'] }}</td>
                                    <td class="py-3 text-center text-gray-400">{{ $prod['operaciones'] }}</td>
                                    <td class="py-3 text-right font-bold text-amber-300">${{ number_format($prod['ingresos'], 2) }}</td>
                                    <td class="py-3 text-right text-gray-300">{{ $prod['porcentaje'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500">Sin ventas en mostrador registradas en este período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- INGRESOS POR MÉTODO DE PAGO --}}
    <div class="bg-gray-900/80 border border-white/10 rounded-2xl p-5 sm:p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-tight">Ingresos por Método de Pago / Canal</h3>
                <p class="text-xs text-gray-400">Distribución de recaudación según el medio utilizado en cada operación</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-gray-400 uppercase font-semibold text-[10px]">
                        <th class="pb-3">Método / Canal</th>
                        <th class="pb-3">Origen</th>
                        <th class="pb-3 text-center">Cantidad de Operaciones</th>
                        <th class="pb-3 text-right">Monto Registrado</th>
                        <th class="pb-3 text-right">% del Total General</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($datos['metodos'] as $m)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-3 font-semibold text-white">{{ $m['metodo'] }}</td>
                            <td class="py-3 text-gray-400">{{ $m['tipo'] }}</td>
                            <td class="py-3 text-center text-gray-300">{{ $m['operaciones'] }}</td>
                            <td class="py-3 text-right font-bold text-emerald-400">${{ number_format($m['ingresos'], 2) }}</td>
                            <td class="py-3 text-right text-gray-300">{{ $m['porcentaje'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500">Sin operaciones financieras registradas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- AVISO DE PRIVACIDAD / DISCLAIMER --}}
    <div class="rounded-xl p-4 bg-white/[0.02] border border-white/5 text-gray-400 text-xs flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span><strong>Privacidad Garantizada:</strong> Este módulo gerencial no expone nombres de socios, correos, números telefónicos ni historiales individuales de entrenamiento o asistencia.</span>
        </div>
        <span class="text-[11px] text-gray-500 shrink-0">Alpha Fitness v2.0</span>
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

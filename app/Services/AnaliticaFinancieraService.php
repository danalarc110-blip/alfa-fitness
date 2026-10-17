<?php

namespace App\Services;

use App\Models\PagoMembresia;
use App\Models\Venta;
use App\Support\Dinero;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AnaliticaFinancieraService
{
    public const MAX_DIAS = 90;

    /**
     * Resuelve y valida el rango de fechas para la analítica.
     *
     * @return array{desde: Carbon, hasta: Carbon, preset: string, dias: int}
     */
    public function resolverPeriodo(?string $preset, ?string $desdeInput, ?string $hastaInput): array
    {
        Validator::make(['preset' => $preset, 'desde' => $desdeInput, 'hasta' => $hastaInput], [
            'preset' => ['nullable', 'in:hoy,ayer,esta_semana,semana_anterior,este_mes,mes_anterior,personalizado'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();
        $timezone = config('app.timezone', 'America/El_Salvador');
        $hoy = Carbon::now($timezone);

        $preset = $preset ?: 'este_mes';

        if ($preset === 'personalizado') {
            if (! $desdeInput || ! $hastaInput) {
                throw ValidationException::withMessages([
                    'periodo' => 'Para un período personalizado debe especificar fecha inicial y final.',
                ]);
            }

            try {
                $desde = Carbon::createFromFormat('!Y-m-d', $desdeInput, $timezone)->startOfDay();
                $hasta = Carbon::createFromFormat('!Y-m-d', $hastaInput, $timezone)->endOfDay();
            } catch (\Exception $e) {
                throw ValidationException::withMessages([
                    'periodo' => 'Las fechas ingresadas no tienen un formato válido.',
                ]);
            }

            if ($desde->gt($hasta)) {
                throw ValidationException::withMessages([
                    'periodo' => 'La fecha inicial no puede ser posterior a la fecha final.',
                ]);
            }

            $dias = (int) $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1;
            if ($dias > self::MAX_DIAS) {
                throw ValidationException::withMessages([
                    'periodo' => 'El período máximo permitido para un reporte es de 90 días.',
                ]);
            }

            if ($desde->gt($hoy)) {
                throw ValidationException::withMessages([
                    'periodo' => 'La fecha inicial no puede ser futura: el reporte presenta operaciones registradas, no proyecciones.',
                ]);
            }

            return [
                'desde' => $desde,
                'hasta' => $hasta,
                'preset' => 'personalizado',
                'dias' => $dias,
            ];
        }

        [$desde, $hasta] = match ($preset) {
            'hoy' => [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()],
            'ayer' => [$hoy->copy()->subDay()->startOfDay(), $hoy->copy()->subDay()->endOfDay()],
            'esta_semana' => [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfWeek()],
            'semana_anterior' => [$hoy->copy()->subWeek()->startOfWeek(), $hoy->copy()->subWeek()->endOfWeek()],
            'mes_anterior' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()], // 'este_mes'
        };

        $dias = (int) $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1;

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'preset' => in_array($preset, ['hoy', 'ayer', 'esta_semana', 'semana_anterior', 'este_mes', 'mes_anterior'], true) ? $preset : 'este_mes',
            'dias' => $dias,
        ];
    }

    /**
     * Calcula todos los datos financieros y gerenciales agregados para el período.
     */
    public function obtenerAnalitica(Carbon $desde, Carbon $hasta, int $dias): array
    {
        $generado = Carbon::now(config('app.timezone'));
        $solicitadoHasta = $hasta->copy();
        $abierto = $hasta->gte($generado);
        if ($desde->gt($generado)) {
            throw ValidationException::withMessages(['periodo' => 'No se pueden analizar períodos futuros.']);
        }
        if ($abierto) {
            $hasta = $generado->copy();
        }
        $dias = (int) $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1;

        // 1. Ingresos y transacciones del período actual
        $centavosMembresias = $this->centavosAgregados(PagoMembresia::whereBetween('pagado_en', [$desde, $hasta])->sum('importe'));
        $ingresosMembresias = $this->importe($centavosMembresias);
        $transaccionesMembresias = (int) PagoMembresia::whereBetween('pagado_en', [$desde, $hasta])->count();

        $centavosVentas = $this->centavosAgregados(Venta::whereBetween('created_at', [$desde, $hasta])->sum('total'));
        $ingresosVentas = $this->importe($centavosVentas);
        $transaccionesVentas = (int) Venta::whereBetween('created_at', [$desde, $hasta])->count();

        $centavosTotales = $centavosMembresias + $centavosVentas;
        $ingresosTotales = $this->importe($centavosTotales);
        $transaccionesTotales = $transaccionesMembresias + $transaccionesVentas;
        $ticketPromedio = $this->promedio($centavosTotales, $transaccionesTotales);

        // 2. Período anterior de la MISMA duración
        $prevDesde = $desde->copy()->subDays($dias)->startOfDay();
        $prevHasta = $abierto
            ? $hasta->copy()->subDays($dias)
            : $desde->copy()->subSecond();

        $prevCentavosMembresias = $this->centavosAgregados(PagoMembresia::whereBetween('pagado_en', [$prevDesde, $prevHasta])->sum('importe'));
        $prevCentavosVentas = $this->centavosAgregados(Venta::whereBetween('created_at', [$prevDesde, $prevHasta])->sum('total'));
        $prevCentavosTotales = $prevCentavosMembresias + $prevCentavosVentas;
        $prevIngresosTotales = $this->importe($prevCentavosTotales);
        $prevTransaccionesMembresias = (int) PagoMembresia::whereBetween('pagado_en', [$prevDesde, $prevHasta])->count();
        $prevTransaccionesVentas = (int) Venta::whereBetween('created_at', [$prevDesde, $prevHasta])->count();
        $prevTransaccionesTotales = $prevTransaccionesMembresias + $prevTransaccionesVentas;
        $prevTicketPromedio = $this->promedio($prevCentavosTotales, $prevTransaccionesTotales);

        $diferenciaMonetaria = $this->importe($centavosTotales - $prevCentavosTotales);
        if ($prevIngresosTotales > 0) {
            $variacionPorcentual = round((($ingresosTotales - $prevIngresosTotales) / $prevIngresosTotales) * 100, 1);
            $variacionTexto = ($variacionPorcentual >= 0 ? '+' : '').$variacionPorcentual.'%';
        } elseif ($ingresosTotales > 0) {
            $variacionPorcentual = null;
            $variacionTexto = 'No calculable (base anterior sin ingresos)';
        } else {
            $variacionPorcentual = 0.0;
            $variacionTexto = '0.0% vs período anterior';
        }

        // 3. Agregación diaria (Evolución de Ingresos)
        $pagosPorDia = DB::table('pagos_membresia')
            ->whereBetween('pagado_en', [$desde, $hasta])
            ->selectRaw('DATE(pagado_en) as fecha, SUM(importe) as total, COUNT(*) as cantidad')
            ->groupBy(DB::raw('DATE(pagado_en)'))
            ->get()
            ->keyBy('fecha');

        $ventasPorDia = DB::table('ventas')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('DATE(created_at) as fecha, SUM(total) as total, COUNT(*) as cantidad')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('fecha');

        $serieDiaria = [];
        $diasActivos = 0;
        $diaMayor = null;
        $diaMenor = null;
        $maxMontoDia = -1.0;
        $minMontoDia = PHP_FLOAT_MAX;

        $cursor = $desde->copy()->startOfDay();
        $finCursor = $hasta->copy()->startOfDay();

        while ($cursor->lte($finCursor)) {
            $fStr = $cursor->format('Y-m-d');
            $fLabel = $cursor->translatedFormat('d M');

            $centavosDiaMembresias = $this->centavosAgregados($pagosPorDia[$fStr]->total ?? 0);
            $centavosDiaVentas = $this->centavosAgregados($ventasPorDia[$fStr]->total ?? 0);
            $montoMembresias = $this->importe($centavosDiaMembresias);
            $montoVentas = $this->importe($centavosDiaVentas);
            $montoDia = $this->importe($centavosDiaMembresias + $centavosDiaVentas);

            $conteoMembresias = isset($pagosPorDia[$fStr]) ? (int) $pagosPorDia[$fStr]->cantidad : 0;
            $conteoVentas = isset($ventasPorDia[$fStr]) ? (int) $ventasPorDia[$fStr]->cantidad : 0;
            $conteoDia = $conteoMembresias + $conteoVentas;

            if ($montoDia > 0) {
                $diasActivos++;
                if ($montoDia > $maxMontoDia) {
                    $maxMontoDia = $montoDia;
                    $diaMayor = [
                        'fecha' => $cursor->format('d/m/Y'),
                        'monto' => $montoDia,
                    ];
                }
                if ($montoDia < $minMontoDia) {
                    $minMontoDia = $montoDia;
                    $diaMenor = [
                        'fecha' => $cursor->format('d/m/Y'),
                        'monto' => $montoDia,
                    ];
                }
            }

            $serieDiaria[] = [
                'fecha' => $fStr,
                'fecha_corta' => $fLabel,
                'membresias' => $montoMembresias,
                'ventas' => $montoVentas,
                'total' => $montoDia,
                'transacciones' => $conteoDia,
            ];

            $cursor->addDay();
        }

        // Si no hubo días con actividad, asignar valores limpios
        if ($diaMayor === null) {
            $diaMayor = ['fecha' => 'Sin actividad', 'monto' => 0.0];
        }
        if ($diaMenor === null) {
            $diaMenor = ['fecha' => 'Sin actividad', 'monto' => 0.0];
        }

        $promedioIngresoDiaActivo = $this->promedio($centavosTotales, $diasActivos);
        $promedioTransaccionesDia = $dias > 0 ? round($transaccionesTotales / $dias, 1) : 0.0;

        $porcentajeMembresias = $ingresosTotales > 0 ? round(($ingresosMembresias / $ingresosTotales) * 100, 1) : 0.0;
        $porcentajeVentas = $ingresosTotales > 0 ? round(($ingresosVentas / $ingresosTotales) * 100, 1) : 0.0;

        // 4. Rendimiento de Membresías por Plan
        $planesRendimiento = DB::table('pagos_membresia')
            ->join('membresias', 'pagos_membresia.membresia_id', '=', 'membresias.id')
            ->whereBetween('pagos_membresia.pagado_en', [$desde, $hasta])
            ->selectRaw('membresias.plan as plan_nombre, COUNT(*) as cantidad_pagos, SUM(pagos_membresia.importe) as total_ingresos')
            ->groupBy('membresias.plan')
            ->orderByDesc('total_ingresos')
            ->get()
            ->map(function ($row) use ($ingresosMembresias) {
                $centavos = $this->centavosAgregados($row->total_ingresos);
                $ing = $this->importe($centavos);
                $pagos = (int) $row->cantidad_pagos;

                return [
                    'plan' => $row->plan_nombre,
                    'pagos' => $pagos,
                    'ingresos' => $ing,
                    'porcentaje' => $ingresosMembresias > 0 ? round(($ing / $ingresosMembresias) * 100, 1) : 0.0,
                    'promedio_operacion' => $this->promedio($centavos, $pagos),
                ];
            });

        $planMayorRecaudacion = $planesRendimiento->first();
        $planMayorPagos = $planesRendimiento->sortByDesc('pagos')->first();

        // 5. Rendimiento de Ventas por Producto
        $productosRendimiento = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->whereBetween('ventas.created_at', [$desde, $hasta])
            ->selectRaw('productos.id as producto_id, productos.nombre as producto_nombre, SUM(detalle_ventas.cantidad) as unidades_vendidas, COUNT(DISTINCT detalle_ventas.venta_id) as operaciones, SUM(detalle_ventas.subtotal) as total_ingresos')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_ingresos')
            ->get()
            ->map(function ($row) use ($ingresosVentas) {
                $ing = $this->importe($this->centavosAgregados($row->total_ingresos));

                return [
                    'producto' => $row->producto_nombre,
                    'unidades' => (int) $row->unidades_vendidas,
                    'operaciones' => (int) $row->operaciones,
                    'ingresos' => $ing,
                    'porcentaje' => $ingresosVentas > 0 ? round(($ing / $ingresosVentas) * 100, 1) : 0.0,
                ];
            });

        $productoMayorFacturacion = $productosRendimiento->first();
        $productoMasVendido = $productosRendimiento->sortByDesc('unidades')->first();

        // 6. Ingresos por Método de Pago
        $metodosVentas = DB::table('ventas')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('metodo_pago as metodo, COUNT(*) as operaciones, SUM(total) as total_ingresos')
            ->groupBy('metodo_pago')
            ->orderByDesc('total_ingresos')
            ->get();

        $metodosRendimiento = collect();
        foreach ($metodosVentas as $m) {
            $monto = $this->importe($this->centavosAgregados($m->total_ingresos));
            $metodosRendimiento->push([
                'metodo' => $m->metodo,
                'tipo' => 'Ventas mostrador',
                'operaciones' => (int) $m->operaciones,
                'ingresos' => $monto,
                'porcentaje' => $ingresosTotales > 0 ? round(($monto / $ingresosTotales) * 100, 1) : 0.0,
            ]);
        }

        // Si existen cobros de membresías, registrar su origen agregado
        if ($transaccionesMembresias > 0) {
            $metodosRendimiento->push([
                'metodo' => 'Registro en Recepción (Membresías)',
                'tipo' => 'Membresías',
                'operaciones' => $transaccionesMembresias,
                'ingresos' => $ingresosMembresias,
                'porcentaje' => $ingresosTotales > 0 ? round(($ingresosMembresias / $ingresosTotales) * 100, 1) : 0.0,
            ]);
        }

        $metodosRendimiento = $metodosRendimiento->sortByDesc('ingresos')->values();

        $comparativo = [];
        foreach ([
            ['Ingresos por membresías', $ingresosMembresias, $this->importe($prevCentavosMembresias), 'moneda'],
            ['Ingresos por ventas', $ingresosVentas, $this->importe($prevCentavosVentas), 'moneda'],
            ['Ingresos totales', $ingresosTotales, $prevIngresosTotales, 'moneda'],
            ['Transacciones', $transaccionesTotales, $prevTransaccionesTotales, 'entero'],
            ['Ticket promedio', $ticketPromedio, $prevTicketPromedio, 'moneda'],
        ] as [$indicador, $actual, $anterior, $formato]) {
            $comparativo[] = [
                'indicador' => $indicador,
                'actual' => $actual,
                'anterior' => $anterior,
                'diferencia' => round($actual - $anterior, 2),
                'variacion' => $anterior > 0 ? round(($actual - $anterior) / $anterior * 100, 1) : ($actual > 0 ? null : 0.0),
                'formato' => $formato,
            ];
        }

        $resumen = [
            'Se registraron USD '.number_format($ingresosTotales, 2).' en '.$transaccionesTotales.' operaciones; el ticket promedio fue USD '.number_format($ticketPromedio, 2).'.',
            $prevIngresosTotales > 0
                ? 'La variación de ingresos fue '.$variacionTexto.'; la diferencia frente al período comparable fue USD '.number_format($diferenciaMonetaria, 2).'.'
                : ($ingresosTotales > 0 ? 'El período anterior no registra ingresos. No existe una base para calcular crecimiento porcentual.' : 'Ambos períodos carecen de ingresos registrados; no se observa variación monetaria.'),
        ];
        if ($ingresosTotales > 0) {
            $origen = $ingresosMembresias >= $ingresosVentas ? 'Las membresías' : 'Las ventas de productos';
            $participacion = max($porcentajeMembresias, $porcentajeVentas);
            $resumen[] = $origen.' representan la mayor participación: '.number_format($participacion, 1).'% de los ingresos del período.';
        }
        if ($abierto) {
            $resumen[] = 'Período en curso: cifras acumuladas al corte. La comparación utiliza el mismo número de días y la misma hora de corte; los resultados pueden cambiar con nuevos registros.';
        }

        $metodologia = [
            'Moneda: USD. Ingresos registrados = cobros de membresías (pagado_en) + ventas de productos (created_at). Las fechas usan la zona horaria configurada.',
            'No se registran costos, gastos ni devoluciones en este módulo. Los ingresos no representan utilidad, rentabilidad ni un estado financiero contable.',
            'Comparación: intervalo inmediatamente anterior con igual número de días calendario; si el período está en curso, ambos terminan a la misma hora de corte. No ajusta por estacionalidad ni días de apertura.',
            'Ticket promedio = ingresos / transacciones. Una transacción es un cobro de membresía o una venta; las operaciones por producto pueden compartir una venta y no se suman como transacciones generales.',
            'Días con ingresos: días cuyo importe total es mayor que cero. Promedio por día activo = ingresos / días con ingresos. Los días sin ingresos se conservan en el detalle diario.',
            'Variación porcentual = (actual - anterior) / anterior × 100. Con base anterior cero y valor actual positivo se indica No calculable; con ambos valores cero se muestra 0%.',
            'Los métodos de pago corresponden a ventas. Los cobros de membresías se agrupan por origen porque no registran un método de pago.',
            'Las cifras son agregadas, sin datos personales de clientes. El código identifica el rango; la fecha de generación identifica el corte de la consulta, no un cierre contable auditado.',
        ];

        return [
            'reporte' => [
                'codigo' => 'AF-GER-'.$desde->format('Ymd').'-'.$hasta->format('Ymd'),
                'generado_en' => $generado->format('d/m/Y H:i:s'),
                'zona_horaria' => config('app.timezone'),
                'moneda' => 'USD',
                'version' => '1.0',
            ],
            'periodo' => [
                'desde' => $desde->format('d/m/Y'),
                'hasta' => $hasta->format('d/m/Y'),
                'desde_iso' => $desde->format('Y-m-d'),
                'hasta_iso' => $hasta->format('Y-m-d'),
                'dias' => $dias,
                'prev_desde' => $prevDesde->format('d/m/Y'),
                'prev_hasta' => $prevHasta->format('d/m/Y'),
                'solicitado_hasta' => $solicitadoHasta->format('d/m/Y'),
                'abierto' => $abierto,
                'corte' => $hasta->format('d/m/Y H:i:s'),
                'comparacion_hasta' => $prevHasta->format('d/m/Y H:i:s'),
            ],
            'kpis' => [
                'ingresos_totales' => $ingresosTotales,
                'ingresos_membresias' => $ingresosMembresias,
                'ingresos_ventas' => $ingresosVentas,
                'transacciones_totales' => $transaccionesTotales,
                'transacciones_membresias' => $transaccionesMembresias,
                'transacciones_ventas' => $transaccionesVentas,
                'ticket_promedio' => $ticketPromedio,
                'prev_ingresos_totales' => $prevIngresosTotales,
                'diferencia_monetaria' => $diferenciaMonetaria,
                'variacion_porcentual' => $variacionPorcentual,
                'variacion_texto' => $variacionTexto,
                'porcentaje_membresias' => $porcentajeMembresias,
                'porcentaje_ventas' => $porcentajeVentas,
                'dias_activos' => $diasActivos,
                'dia_mayor_ingreso' => $diaMayor,
                'dia_menor_ingreso' => $diaMenor,
                'promedio_ingreso_dia_activo' => $promedioIngresoDiaActivo,
                'promedio_transacciones_dia' => $promedioTransaccionesDia,
                'plan_mayor_recaudacion' => $planMayorRecaudacion['plan'] ?? 'N/A',
                'plan_mayor_recaudacion_monto' => $planMayorRecaudacion['ingresos'] ?? 0.0,
                'plan_mayor_pagos' => $planMayorPagos['plan'] ?? 'N/A',
                'plan_mayor_pagos_cantidad' => $planMayorPagos['pagos'] ?? 0,
                'producto_mayor_facturacion' => $productoMayorFacturacion['producto'] ?? 'N/A',
                'producto_mayor_facturacion_monto' => $productoMayorFacturacion['ingresos'] ?? 0.0,
                'producto_mas_vendido' => $productoMasVendido['producto'] ?? 'N/A',
                'producto_mas_vendido_unidades' => $productoMasVendido['unidades'] ?? 0,
            ],
            'serie_diaria' => $serieDiaria,
            'planes' => $planesRendimiento,
            'productos' => $productosRendimiento,
            'metodos' => $metodosRendimiento,
            'comparativo' => $comparativo,
            'resumen_ejecutivo' => $resumen,
            'metodologia' => $metodologia,
        ];
    }

    private function centavosAgregados(string|int|float $importe): int
    {
        // SQLite returns SUM(DECIMAL) as a float; normalize once at the database boundary.
        $decimal = is_float($importe) ? number_format($importe, 2, '.', '') : (string) $importe;

        return Dinero::centavos($decimal);
    }

    private function importe(int $centavos): float
    {
        // Numeric presentation contract shared by charts, PDF and Excel; no persistence uses it.
        return (float) Dinero::decimal($centavos);
    }

    private function promedio(int $centavos, int $cantidad): float
    {
        return $cantidad > 0 ? $this->importe(intdiv($centavos + intdiv($cantidad, 2), $cantidad)) : 0.0;
    }
}

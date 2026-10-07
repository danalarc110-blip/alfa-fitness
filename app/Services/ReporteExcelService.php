<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteExcelService
{
    private const FORMATO_MONEDA = '"$"#,##0.00';

    /**
     * Genera un StreamedResponse con el archivo .xlsx de analítica financiera.
     */
    public function descargar(array $datos, string $nombreArchivo): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Alpha Fitness Management')
            ->setTitle('Reporte Financiero Gerencial')
            ->setSubject('Analítica Financiera Agregada');

        // Color institucional: Negro grafito (#111827 / #0f172a), Acento Carmesí (#e11d48)
        $estiloCabecera = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        // -------------------------------------------------------------
        // HOJA 1: Resumen
        // -------------------------------------------------------------
        $hojaResumen = $spreadsheet->getActiveSheet();
        $hojaResumen->setTitle('Resumen');

        $hojaResumen->setCellValue('A1', 'ALPHA FITNESS - REPORTE FINANCIERO GERENCIAL');
        $hojaResumen->mergeCells('A1:C1');
        $hojaResumen->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $hojaResumen->setCellValue('A2', 'Nota: Información financiera agregada. No incluye datos personales de clientes.');
        $hojaResumen->mergeCells('A2:C2');
        $hojaResumen->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('64748B');

        $kpis = $datos['kpis'];
        $periodo = $datos['periodo'];

        $filasResumen = [
            ['Indicador', 'Valor', 'Detalle'],
            ['Período analizado', "{$periodo['desde']} al {$periodo['hasta']}", "{$periodo['dias']} días"],
            ['Período de comparación', "{$periodo['prev_desde']} al {$periodo['prev_hasta']}", 'Misma duración'],
            ['Ingresos totales', $kpis['ingresos_totales'], 'Membresías + Ventas'],
            ['Ingresos por membresías', $kpis['ingresos_membresias'], "{$kpis['porcentaje_membresias']}% del total"],
            ['Ingresos por ventas', $kpis['ingresos_ventas'], "{$kpis['porcentaje_ventas']}% del total"],
            ['Transacciones totales', $kpis['transacciones_totales'], "{$kpis['transacciones_membresias']} mem. + {$kpis['transacciones_ventas']} ventas"],
            ['Ticket promedio', $kpis['ticket_promedio'], 'Ingresos / transacciones'],
            ['Ingresos período anterior', $kpis['prev_ingresos_totales'], 'Período previo inmediato'],
            ['Diferencia monetaria', $kpis['diferencia_monetaria'], 'Variación neta'],
            ['Variación porcentual', $kpis['variacion_texto'], 'vs período anterior'],
            ['Días con actividad', $kpis['dias_activos'], "de {$periodo['dias']} días"],
            ['Día con mayor ingreso', $kpis['dia_mayor_ingreso']['monto'], $kpis['dia_mayor_ingreso']['fecha']],
            ['Día con menor ingreso (activo)', $kpis['dia_menor_ingreso']['monto'], $kpis['dia_menor_ingreso']['fecha']],
            ['Promedio por día activo', $kpis['promedio_ingreso_dia_activo'], 'Solo días con ingresos'],
            ['Plan más recaudador', $kpis['plan_mayor_recaudacion_monto'], $kpis['plan_mayor_recaudacion']],
            ['Producto mayor facturación', $kpis['producto_mayor_facturacion_monto'], $kpis['producto_mayor_facturacion']],
            ['Producto más vendido', $kpis['producto_mas_vendido_unidades'], $kpis['producto_mas_vendido']],
        ];

        $fila = 4;
        foreach ($filasResumen as $index => $datosFila) {
            $hojaResumen->setCellValueExplicit('A'.$fila, $datosFila[0], DataType::TYPE_STRING);
            $hojaResumen->setCellValueExplicit('B'.$fila, $datosFila[1], is_string($datosFila[1]) ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC);
            $hojaResumen->setCellValueExplicit('C'.$fila, $datosFila[2], DataType::TYPE_STRING);

            if ($index === 0) {
                $hojaResumen->getStyle("A{$fila}:C{$fila}")->applyFromArray($estiloCabecera);
            } else {
                // Formato numérico para importes
                if (in_array($index, [3, 4, 5, 7, 8, 9, 12, 13, 14, 15, 16], true) && is_numeric($datosFila[1])) {
                    $hojaResumen->getStyle('B'.$fila)->getNumberFormat()->setFormatCode(self::FORMATO_MONEDA);
                }
            }
            $fila++;
        }

        // -------------------------------------------------------------
        // HOJA 2: Ingresos por día
        // -------------------------------------------------------------
        $hojaDias = $spreadsheet->createSheet();
        $hojaDias->setTitle('Ingresos por día');

        $hojaDias->setCellValue('A1', 'Fecha');
        $hojaDias->setCellValue('B1', 'Membresías ($)');
        $hojaDias->setCellValue('C1', 'Ventas ($)');
        $hojaDias->setCellValue('D1', 'Total ($)');
        $hojaDias->setCellValue('E1', 'Transacciones');
        $hojaDias->getStyle('A1:E1')->applyFromArray($estiloCabecera);

        $f = 2;
        foreach ($datos['serie_diaria'] as $dia) {
            $hojaDias->setCellValueExplicit('A'.$f, $dia['fecha'], DataType::TYPE_STRING);
            $hojaDias->setCellValue('B'.$f, $dia['membresias']);
            $hojaDias->setCellValue('C'.$f, $dia['ventas']);
            $hojaDias->setCellValue('D'.$f, $dia['total']);
            $hojaDias->setCellValue('E'.$f, $dia['transacciones']);

            $hojaDias->getStyle("B{$f}:D{$f}")->getNumberFormat()->setFormatCode(self::FORMATO_MONEDA);
            $hojaDias->getStyle('E'.$f)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
            $f++;
        }

        // -------------------------------------------------------------
        // HOJA 3: Membresías
        // -------------------------------------------------------------
        $hojaPlanes = $spreadsheet->createSheet();
        $hojaPlanes->setTitle('Membresías');

        $hojaPlanes->setCellValue('A1', 'Plan');
        $hojaPlanes->setCellValue('B1', 'Pagos');
        $hojaPlanes->setCellValue('C1', 'Ingresos ($)');
        $hojaPlanes->setCellValue('D1', '% del total membresías');
        $hojaPlanes->setCellValue('E1', 'Promedio por operación ($)');
        $hojaPlanes->getStyle('A1:E1')->applyFromArray($estiloCabecera);

        $f = 2;
        foreach ($datos['planes'] as $plan) {
            $hojaPlanes->setCellValueExplicit('A'.$f, $plan['plan'], DataType::TYPE_STRING);
            $hojaPlanes->setCellValue('B'.$f, $plan['pagos']);
            $hojaPlanes->setCellValue('C'.$f, $plan['ingresos']);
            $hojaPlanes->setCellValue('D'.$f, $plan['porcentaje'] / 100);
            $hojaPlanes->setCellValue('E'.$f, $plan['promedio_operacion']);

            $hojaPlanes->getStyle('B'.$f)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
            $hojaPlanes->getStyle('C'.$f)->getNumberFormat()->setFormatCode(self::FORMATO_MONEDA);
            $hojaPlanes->getStyle('D'.$f)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
            $hojaPlanes->getStyle('E'.$f)->getNumberFormat()->setFormatCode(self::FORMATO_MONEDA);
            $f++;
        }

        // -------------------------------------------------------------
        // HOJA 4: Productos
        // -------------------------------------------------------------
        $hojaProductos = $spreadsheet->createSheet();
        $hojaProductos->setTitle('Productos');

        $hojaProductos->setCellValue('A1', 'Producto');
        $hojaProductos->setCellValue('B1', 'Unidades vendidas');
        $hojaProductos->setCellValue('C1', 'Operaciones');
        $hojaProductos->setCellValue('D1', 'Ingresos ($)');
        $hojaProductos->setCellValue('E1', '% del total ventas');
        $hojaProductos->getStyle('A1:E1')->applyFromArray($estiloCabecera);

        $f = 2;
        foreach ($datos['productos'] as $prod) {
            $hojaProductos->setCellValueExplicit('A'.$f, $prod['producto'], DataType::TYPE_STRING);
            $hojaProductos->setCellValue('B'.$f, $prod['unidades']);
            $hojaProductos->setCellValue('C'.$f, $prod['operaciones']);
            $hojaProductos->setCellValue('D'.$f, $prod['ingresos']);
            $hojaProductos->setCellValue('E'.$f, $prod['porcentaje'] / 100);

            $hojaProductos->getStyle("B{$f}:C{$f}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
            $hojaProductos->getStyle('D'.$f)->getNumberFormat()->setFormatCode(self::FORMATO_MONEDA);
            $hojaProductos->getStyle('E'.$f)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
            $f++;
        }

        // -------------------------------------------------------------
        // HOJA 5: Métodos de pago
        // -------------------------------------------------------------
        $hojaMetodos = $spreadsheet->createSheet();
        $hojaMetodos->setTitle('Métodos de pago');

        $hojaMetodos->setCellValue('A1', 'Método / Origen');
        $hojaMetodos->setCellValue('B1', 'Operaciones');
        $hojaMetodos->setCellValue('C1', 'Ingresos ($)');
        $hojaMetodos->setCellValue('D1', '% del total');
        $hojaMetodos->getStyle('A1:D1')->applyFromArray($estiloCabecera);

        $f = 2;
        foreach ($datos['metodos'] as $m) {
            $hojaMetodos->setCellValueExplicit('A'.$f, $m['metodo'], DataType::TYPE_STRING);
            $hojaMetodos->setCellValue('B'.$f, $m['operaciones']);
            $hojaMetodos->setCellValue('C'.$f, $m['ingresos']);
            $hojaMetodos->setCellValue('D'.$f, $m['porcentaje'] / 100);

            $hojaMetodos->getStyle('B'.$f)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
            $hojaMetodos->getStyle('C'.$f)->getNumberFormat()->setFormatCode(self::FORMATO_MONEDA);
            $hojaMetodos->getStyle('D'.$f)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
            $f++;
        }

        // Ajustar ancho de columnas en todas las hojas
        foreach ($spreadsheet->getAllSheets() as $hoja) {
            foreach (range('A', 'E') as $col) {
                $hoja->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$nombreArchivo.'"',
        ]);
    }
}

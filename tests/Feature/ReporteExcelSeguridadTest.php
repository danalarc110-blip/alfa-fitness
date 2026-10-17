<?php

namespace Tests\Feature;

use App\Services\ReporteExcelService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReporteExcelSeguridadTest extends TestCase
{
    public function test_real_xlsx_keeps_untrusted_text_literal_and_financial_values_numeric(): void
    {
        $datos = $this->datos();
        $response = app(ReporteExcelService::class)->descargar($datos, 'seguridad.xlsx');
        ob_start();
        $response->sendContent();
        $bytes = ob_get_clean();
        $this->assertStringStartsWith('PK', $bytes);
        $path = tempnam(sys_get_temp_dir(), 'af_excel_');
        try {
            file_put_contents($path, $bytes);
            $book = IOFactory::load($path);
            $this->assertSame(['Resumen', 'Ingresos por día', 'Membresías', 'Productos', 'Métodos de pago', 'Comparativo'], $book->getSheetNames());
            foreach ([['Resumen', 'C19', '=1+1'], ['Resumen', 'C20', '+1+1'], ['Resumen', 'C21', '@SUM(1)'], ['Membresías', 'A2', '=1+1'], ['Productos', 'A2', '+1+1'], ['Métodos de pago', 'A2', '-1+1']] as [$sheet, $coord, $value]) {
                $cell = $book->getSheetByName($sheet)->getCell($coord);
                $this->assertSame($value, $cell->getValue());
                $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
            }
            foreach ([['Resumen', 'B7', 125.5], ['Ingresos por día', 'D2', 125.5], ['Membresías', 'C2', 100.0], ['Productos', 'D2', 25.5], ['Métodos de pago', 'C2', 125.5]] as [$sheet, $coord, $value]) {
                $cell = $book->getSheetByName($sheet)->getCell($coord);
                $this->assertSame(DataType::TYPE_NUMERIC, $cell->getDataType());
                $this->assertEquals($value, $cell->getValue());
            }
            foreach ($book->getAllSheets() as $sheet) {
                $this->assertFalse($sheet->getShowGridlines());
                $this->assertNotEmpty($sheet->getFreezePane());
                $this->assertSame(1, $sheet->getPageSetup()->getFitToWidth());
                foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
                    $this->assertNotSame(DataType::TYPE_FORMULA, $sheet->getCell($coordinate)->getDataType());
                }
            }
            $this->assertEquals(125.5, $book->getSheetByName('Ingresos por día')->getCell('D3')->getValue());
            $this->assertSame('A1:E2', $book->getSheetByName('Ingresos por día')->getAutoFilter()->getRange());
            $this->assertSame('0.0%', $book->getSheetByName('Membresías')->getStyle('D2')->getNumberFormat()->getFormatCode());
            $this->assertSame('No calculable', $book->getSheetByName('Comparativo')->getCell('E2')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $book->getSheetByName('Comparativo')->getCell('B2')->getDataType());
            $content = json_encode(array_map(fn ($sheet) => $sheet->toArray(), $book->getAllSheets()));
            foreach (['privado@example.test', 'DUI-PRIVADO', 'PASSWORD-PRIVADO', 'TOKEN-PRIVADO', 'GOOGLE-PRIVADO'] as $sensitive) {
                $this->assertStringNotContainsString($sensitive, $content);
            }
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    private function datos(): array
    {
        return [
            'periodo' => ['desde' => '2026-10-01', 'hasta' => '2026-10-01', 'dias' => 1, 'prev_desde' => '2026-09-30', 'prev_hasta' => '2026-09-30', 'abierto' => false, 'solicitado_hasta' => '2026-10-01', 'corte' => '01/10/2026 23:59:59', 'comparacion_hasta' => '30/09/2026 23:59:59'],
            'reporte' => ['codigo' => 'AF-GER-20261001-20261001', 'version' => '1.0', 'generado_en' => '02/10/2026 12:00:00', 'zona_horaria' => 'America/El_Salvador', 'moneda' => 'USD'],
            'resumen_ejecutivo' => ['=RESUMEN_NO_EJECUTAR'],
            'metodologia' => ['+METODOLOGIA_NO_EJECUTAR'],
            'comparativo' => [['indicador' => '@INDICADOR_NO_EJECUTAR', 'actual' => 125.5, 'anterior' => 0.0, 'diferencia' => 125.5, 'variacion' => null, 'formato' => 'moneda']],
            'kpis' => [
                'ingresos_totales' => 125.5, 'ingresos_membresias' => 100.0, 'ingresos_ventas' => 25.5,
                'porcentaje_membresias' => 79.7, 'porcentaje_ventas' => 20.3,
                'transacciones_totales' => 2, 'transacciones_membresias' => 1, 'transacciones_ventas' => 1,
                'ticket_promedio' => 62.75, 'prev_ingresos_totales' => 120.0, 'diferencia_monetaria' => 5.5,
                'variacion_texto' => '+4.58%', 'dias_activos' => 1,
                'dia_mayor_ingreso' => ['monto' => 125.5, 'fecha' => '2026-10-01'],
                'dia_menor_ingreso' => ['monto' => 125.5, 'fecha' => '2026-10-01'],
                'promedio_ingreso_dia_activo' => 125.5, 'plan_mayor_recaudacion_monto' => 100.0,
                'plan_mayor_recaudacion' => '=1+1', 'producto_mayor_facturacion_monto' => 25.5,
                'producto_mayor_facturacion' => '+1+1', 'producto_mas_vendido_unidades' => 1,
                'producto_mas_vendido' => '@SUM(1)',
            ],
            'serie_diaria' => [['fecha' => '2026-10-01', 'membresias' => 100.0, 'ventas' => 25.5, 'total' => 125.5, 'transacciones' => 2]],
            'planes' => [['plan' => '=1+1', 'pagos' => 1, 'ingresos' => 100.0, 'porcentaje' => 100, 'promedio_operacion' => 100.0]],
            'productos' => [['producto' => '+1+1', 'unidades' => 1, 'operaciones' => 1, 'ingresos' => 25.5, 'porcentaje' => 100]],
            'metodos' => [['metodo' => '-1+1', 'operaciones' => 2, 'ingresos' => 125.5, 'porcentaje' => 100]],
            'clientes' => [['correo' => 'privado@example.test', 'dui' => 'DUI-PRIVADO', 'password' => 'PASSWORD-PRIVADO', 'remember_token' => 'TOKEN-PRIVADO', 'google_id' => 'GOOGLE-PRIVADO']],
        ];
    }
}

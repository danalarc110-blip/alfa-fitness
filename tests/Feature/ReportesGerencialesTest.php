<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use App\Services\ReporteExcelService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportesGerencialesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/El_Salvador']);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'America/El_Salvador'));
        $this->admin = User::factory()->create(['rol' => 'Administrador']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function comparaciones(): array
    {
        return [
            'sin base anterior' => ['0.00', '125.00', null, 125.0],
            'sin ingresos en ambos' => ['0.00', '0.00', 0.0, 0.0],
            'caída completa' => ['100.00', '0.00', -100.0, -100.0],
            'crecimiento con base' => ['100.00', '125.00', 25.0, 25.0],
        ];
    }

    #[DataProvider('comparaciones')]
    public function test_variacion_requires_a_positive_base(string $anterior, string $actual, ?float $variacion, float $diferencia): void
    {
        if ($anterior !== '0.00') {
            $this->venta($anterior, '2026-09-30 10:00:00');
        }
        if ($actual !== '0.00') {
            $this->venta($actual, '2026-10-01 10:00:00');
        }
        $datos = $this->analitica('2026-10-01', '2026-10-01');
        $this->assertSame($variacion, $datos['kpis']['variacion_porcentual']);
        $this->assertSame($variacion, $datos['comparativo'][2]['variacion']);
        $this->assertSame($diferencia, $datos['kpis']['diferencia_monetaria']);
        if ($variacion === null) {
            $this->assertStringContainsString('No calculable', $datos['kpis']['variacion_texto']);
        }
    }

    public function test_open_period_uses_elapsed_days_and_matching_previous_cutoff(): void
    {
        $this->venta('125.00', '2026-10-15 12:00:00');
        $this->venta('50.00', '2026-10-15 12:00:01');
        $this->venta('200.00', '2026-10-16 10:00:00');
        $this->venta('100.00', '2026-09-30 12:00:00');
        $this->venta('300.00', '2026-09-30 12:00:01');
        $this->venta('400.00', '2026-09-15 23:59:59');

        $datos = $this->analitica('2026-10-01', '2026-10-31');
        $this->assertTrue($datos['periodo']['abierto']);
        $this->assertSame('31/10/2026', $datos['periodo']['solicitado_hasta']);
        $this->assertSame('15/10/2026 12:00:00', $datos['periodo']['corte']);
        $this->assertSame('16/09/2026', $datos['periodo']['prev_desde']);
        $this->assertSame('30/09/2026 12:00:00', $datos['periodo']['comparacion_hasta']);
        $this->assertSame(15, $datos['periodo']['dias']);
        $this->assertCount(15, $datos['serie_diaria']);
        $this->assertSame(125.0, $datos['kpis']['ingresos_totales']);
        $this->assertSame(100.0, $datos['kpis']['prev_ingresos_totales']);
        $this->assertSame(25.0, $datos['kpis']['variacion_porcentual']);
        $this->assertSame(1, $datos['kpis']['transacciones_totales']);
        $this->assertSame(0.1, $datos['kpis']['promedio_transacciones_dia']);
        $this->assertEquals($datos['kpis']['ingresos_totales'], array_sum(array_column($datos['serie_diaria'], 'total')));
        $this->assertSame('America/El_Salvador', $datos['reporte']['zona_horaria']);
        $this->actingAs($this->admin)->get(route('analitica.index', ['preset' => 'este_mes']))
            ->assertOk()->assertSee('Resumen ejecutivo')->assertSee('En curso')->assertSee('31/10/2026')->assertSee('AF-GER-20261001-20261015');
    }

    public function test_closed_period_keeps_full_calendar_days_and_zero_days(): void
    {
        $this->venta('20.00', '2026-10-03 23:59:59');
        $this->venta('10.00', '2026-09-30 23:59:59');
        $datos = $this->analitica('2026-10-01', '2026-10-03');
        $this->assertFalse($datos['periodo']['abierto']);
        $this->assertSame(3, $datos['periodo']['dias']);
        $this->assertSame('28/09/2026', $datos['periodo']['prev_desde']);
        $this->assertSame('30/09/2026 23:59:59', $datos['periodo']['comparacion_hasta']);
        $this->assertSame(20.0, $datos['kpis']['ingresos_totales']);
        $this->assertSame(10.0, $datos['kpis']['prev_ingresos_totales']);
        $this->assertSame(0.0, $datos['serie_diaria'][0]['total']);
        $this->assertSame(1, $datos['kpis']['dias_activos']);
    }

    public function test_today_and_first_day_of_week_share_the_same_midnight_cutoff(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-12 00:00:00', 'America/El_Salvador'));
        $this->venta('15.00', '2026-10-12 00:00:00');
        $this->venta('10.00', '2026-10-11 00:00:00');
        $this->venta('20.00', '2026-10-11 00:00:01');
        $service = app(AnaliticaFinancieraService::class);
        foreach (['hoy', 'esta_semana'] as $preset) {
            $periodo = $service->resolverPeriodo($preset, null, null);
            $datos = $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);
            $this->assertSame(1, $datos['periodo']['dias']);
            $this->assertSame('11/10/2026 00:00:00', $datos['periodo']['comparacion_hasta']);
            $this->assertSame(15.0, $datos['kpis']['ingresos_totales']);
            $this->assertSame(10.0, $datos['kpis']['prev_ingresos_totales']);
            $this->assertSame(1.0, $datos['kpis']['promedio_transacciones_dia']);
        }
    }

    public function test_future_period_is_rejected_in_all_formats_without_export_logs(): void
    {
        $this->actingAs($this->admin);
        foreach (['analitica.index', 'analitica.pdf', 'analitica.excel'] as $route) {
            $this->getJson(route($route, ['preset' => 'personalizado', 'desde' => '2026-10-16', 'hasta' => '2026-10-20']))
                ->assertUnprocessable()->assertJsonValidationErrors('periodo');
        }
        $this->assertDatabaseCount('reportes_financieros_logs', 0);
    }

    public function test_free_sale_is_an_operation_but_not_a_day_with_income(): void
    {
        $this->venta('0.00', '2026-10-01 10:00:00');
        $datos = $this->analitica('2026-10-01', '2026-10-01');
        $this->assertSame(1, $datos['kpis']['transacciones_totales']);
        $this->assertSame(0, $datos['kpis']['dias_activos']);
        $this->assertSame(0.0, $datos['kpis']['ticket_promedio']);
        $this->actingAs($this->admin)->get(route('analitica.index', ['preset' => 'personalizado', 'desde' => '2026-10-01', 'hasta' => '2026-10-01']))
            ->assertOk()->assertSee('Días con ingresos')->assertDontSee('días con operaciones');
    }

    public function test_exports_share_traceability_and_reconcile_real_excel_values(): void
    {
        $this->venta('125.00', '2026-10-01 10:00:00');
        $datos = $this->analitica('2026-10-01', '2026-10-01');
        $html = view('analitica.pdf', compact('datos'))->render();
        foreach (['AF-GER-20261001-20261001', 'USD', 'America/El_Salvador', 'Resumen ejecutivo', 'Metodología', 'Detalle diario', 'No calculable'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        ob_start();
        app(ReporteExcelService::class)->descargar($datos, 'gerencial.xlsx')->sendContent();
        $bytes = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'af_ger_');
        try {
            file_put_contents($path, $bytes);
            $book = IOFactory::load($path);
            $this->assertSame($datos['reporte']['codigo'], $book->getSheetByName('Resumen')->getCell('B25')->getValue());
            $this->assertEquals($datos['kpis']['ingresos_totales'], $book->getSheetByName('Ingresos por día')->getCell('D3')->getValue());
            $this->assertEquals($datos['comparativo'][2]['actual'], $book->getSheetByName('Comparativo')->getCell('B4')->getValue());
            $this->assertSame('No calculable', $book->getSheetByName('Comparativo')->getCell('E4')->getValue());
            $this->assertStringContainsString('no representan utilidad', implode(' ', array_filter(array_column($book->getSheetByName('Resumen')->toArray(), 0))));
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $this->actingAs($this->admin)->get(route('analitica.pdf', ['preset' => 'personalizado', 'desde' => '2026-10-01', 'hasta' => '2026-10-01']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    private function venta(string $importe, string $fecha): void
    {
        $venta = new Venta(['user_id' => $this->admin->id, 'total' => $importe, 'metodo_pago' => 'Efectivo']);
        $venta->created_at = Carbon::parse($fecha, config('app.timezone'));
        $venta->save();
    }

    private function analitica(string $desde, string $hasta): array
    {
        $service = app(AnaliticaFinancieraService::class);
        $periodo = $service->resolverPeriodo('personalizado', $desde, $hasta);

        return $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);
    }
}

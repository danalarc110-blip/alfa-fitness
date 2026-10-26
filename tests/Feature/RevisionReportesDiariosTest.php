<?php

namespace Tests\Feature;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use App\Services\ReporteExcelService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RevisionReportesDiariosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/El_Salvador']);
        Carbon::setTestNow(Carbon::parse('2026-10-22 12:00:00', 'America/El_Salvador'));
        $this->admin = User::factory()->create(['rol' => 'Administrador']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_report_reconciles_real_sales_and_keeps_zero_income_days(): void
    {
        $this->venta('0.58', '2026-10-19 09:00:00', '0.58');
        $this->venta('0.10', '2026-10-21 23:59:59', '0.10');
        $this->venta('999.00', '2026-10-20 10:00:00', null, true);
        $this->venta('0.20', '2026-10-18 12:00:00', '0.20');

        $datos = $this->analitica('2026-10-19', '2026-10-21');

        $this->assertSame(0.68, $datos['kpis']['ingresos_totales']);
        $this->assertSame(0.23, $datos['kpis']['promedio_ingreso_dia']);
        $this->assertSame(0.34, $datos['kpis']['promedio_ingreso_dia_activo']);
        $this->assertSame(1, $datos['kpis']['dias_sin_ingresos']);
        $this->assertSame(0.0, $datos['serie_diaria'][1]['total']);
        $this->assertSame(2, $datos['kpis']['transacciones_totales']);
        $this->assertTrue($datos['conciliacion']['correcta']);
        $this->assertSame(0, $datos['conciliacion']['ventas_sin_detalle']);
        $this->assertSame(0, $datos['conciliacion']['ventas_con_diferencias']);
        $this->assertSame(0.68, $datos['conciliacion']['ingresos_productos']);
        $this->assertSame(0.0, $datos['conciliacion']['diferencia']);
    }

    public function test_opposite_sale_discrepancies_are_detected_even_when_aggregate_totals_match(): void
    {
        $this->venta('10.00', '2026-10-21 09:00:00', '11.00');
        $this->venta('20.00', '2026-10-21 10:00:00', '19.00');
        $datos = $this->analitica('2026-10-21', '2026-10-21');

        $this->assertFalse($datos['conciliacion']['correcta']);
        $this->assertSame(2, $datos['conciliacion']['ventas_con_diferencias']);
        $this->assertSame(0.0, $datos['conciliacion']['diferencia']);
        $this->assertSame(30.0, $datos['kpis']['ingresos_totales']);
        $this->assertNotEmpty($datos['conciliacion']['alertas']);
        $this->actingAs($this->admin)->get(route('analitica.index', ['preset' => 'ayer']))
            ->assertOk()->assertSee('Revisión necesaria')->assertSee('Ventas con diferencias: 2.');
    }

    public function test_missing_sale_details_are_visible_in_screen_pdf_and_real_excel(): void
    {
        $this->venta('25.00', '2026-10-21 09:00:00');
        $datos = $this->analitica('2026-10-21', '2026-10-21');
        $this->assertFalse($datos['conciliacion']['correcta']);
        $this->assertSame(1, $datos['conciliacion']['ventas_sin_detalle']);
        $this->assertSame(25.0, $datos['conciliacion']['diferencia']);
        $this->assertSame(25.0, $datos['kpis']['ingresos_totales']);
        $this->assertSame(0.0, $datos['conciliacion']['ingresos_productos']);
        $html = view('analitica.pdf', compact('datos'))->render();
        $this->assertStringContainsString('Revisión necesaria', $html);
        $this->assertStringContainsString('Ventas sin detalle: 1.', $html);
        $this->actingAs($this->admin)->get(route('analitica.index', ['preset' => 'ayer']))
            ->assertOk()->assertSee('Ventas sin detalle: 1.');

        ob_start();
        app(ReporteExcelService::class)->descargar($datos, 'revision.xlsx')->sendContent();
        $bytes = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'af_revision_reportes_');
        try {
            file_put_contents($path, $bytes);
            $book = IOFactory::load($path);
            $resumen = $book->getSheetByName('Resumen')->toArray(null, false, false);
            $filas = collect($resumen)->keyBy(fn ($fila) => $fila[0] ?? '');
            $this->assertSame('Revisión necesaria', $filas['Conciliación de ventas'][1]);
            $this->assertEquals(25.0, $filas['Diferencia ventas - productos'][1]);
            $this->assertEquals(0, $filas['Ingresos detallados por producto'][1]);
            $this->assertStringContainsString('Ventas sin detalle: 1.', implode(' ', array_filter(array_column($resumen, 0))));
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_preset_form_dates_follow_the_report_even_with_unrelated_query_dates(): void
    {
        $this->actingAs($this->admin)->get(route('analitica.index', [
            'preset' => 'hoy', 'desde' => '2026-01-01', 'hasta' => '2026-01-03',
        ]))->assertOk()->assertViewHas('desdeInput', '2026-10-22')->assertViewHas('hastaInput', '2026-10-22');
    }

    public static function rangosInvalidos(): array
    {
        return [
            'invertido' => ['2026-10-21', '2026-10-20'],
            '91 días' => ['2026-01-01', '2026-04-01'],
        ];
    }

    #[DataProvider('rangosInvalidos')]
    public function test_direct_service_call_rejects_invalid_ranges(string $desde, string $hasta): void
    {
        $this->expectException(ValidationException::class);
        app(AnaliticaFinancieraService::class)->obtenerAnalitica(Carbon::parse($desde)->startOfDay(), Carbon::parse($hasta)->endOfDay(), 1);
    }

    public function test_ninety_calendar_days_are_accepted_and_inputs_are_not_mutated(): void
    {
        $desde = Carbon::parse('2026-01-01')->startOfDay();
        $hasta = Carbon::parse('2026-03-31')->endOfDay();
        $datos = app(AnaliticaFinancieraService::class)->obtenerAnalitica($desde, $hasta, 1);
        $this->assertSame(90, $datos['periodo']['dias']);
        $this->assertCount(90, $datos['serie_diaria']);
        $this->assertSame('2026-01-01 00:00:00', $desde->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31 23:59:59', $hasta->format('Y-m-d H:i:s'));
    }

    private function venta(string $total, string $fecha, ?string $detalle = null, bool $simulada = false): void
    {
        $venta = new Venta(['user_id' => $this->admin->id, 'total' => $total, 'metodo_pago' => 'Efectivo', 'pago_simulado' => $simulada]);
        $venta->created_at = Carbon::parse($fecha, config('app.timezone'));
        $venta->save();
        if ($detalle !== null) {
            $producto = Producto::create(['nombre' => 'Agua', 'precio' => $detalle, 'stock' => 10, 'activo' => true]);
            DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => $detalle, 'subtotal' => $detalle]);
        }
    }

    private function analitica(string $desde, string $hasta): array
    {
        $service = app(AnaliticaFinancieraService::class);
        $periodo = $service->resolverPeriodo('personalizado', $desde, $hasta);

        return $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);
    }
}

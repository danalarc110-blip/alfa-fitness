<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoEntrada;
use App\Models\PagoMembresia;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use App\Services\ReporteExcelService;
use App\Services\ReportePdfService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class IngresosEntradasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/El_Salvador']);
        Carbon::setTestNow(Carbon::parse('2026-11-05 12:00:00', 'America/El_Salvador'));
        $this->admin = User::factory()->create(['rol' => 'Administrador']);
        $this->cliente = Cliente::create(['nombre' => 'Cliente Privado Entradas', 'correo' => 'entrada.privada@example.test', 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_memberships_sales_and_entry_payments_reconcile_with_daily_totals_and_matching_cutoffs(): void
    {
        $solicitud = SolicitudMembresia::create([
            'cliente_id' => $this->cliente->id, 'plan_nombre' => 'Mensual', 'precio_acordado' => '25.00',
            'duracion_dias' => 30, 'estado' => 'activada',
        ]);
        $membresia = Membresia::create([
            'cliente_id' => $this->cliente->id, 'solicitud_id' => $solicitud->id, 'plan' => 'Mensual',
            'importe' => '25.00', 'inicio' => '2026-11-01', 'fin' => '2026-11-30',
        ]);
        PagoMembresia::create([
            'membresia_id' => $membresia->id, 'solicitud_id' => $solicitud->id, 'registrado_por' => $this->admin->id,
            'importe' => '25.00', 'pagado_en' => '2026-11-01 08:00:00',
        ]);
        $this->venta('0.30', '2026-11-02 10:00:00');
        foreach (['2026-11-01 10:00:00', '2026-11-02 10:00:00', '2026-11-05 12:00:00'] as $fecha) {
            $this->entrada($fecha);
        }
        $this->entrada('2026-11-05 12:00:01');
        $this->entrada('2026-11-06 10:00:00');
        $this->entrada('2026-10-31 12:00:00');
        $this->entrada('2026-10-31 12:00:01');
        $this->venta('1.00', '2026-10-30 10:00:00');

        $datos = $this->analitica('2026-11-01', '2026-11-30');
        $this->assertSame(31.3, $datos['kpis']['ingresos_totales']);
        $this->assertSame(25.0, $datos['kpis']['ingresos_membresias']);
        $this->assertSame(0.3, $datos['kpis']['ingresos_ventas']);
        $this->assertSame(6.0, $datos['kpis']['ingresos_entradas']);
        $this->assertSame(3, $datos['kpis']['transacciones_entradas']);
        $this->assertSame(5, $datos['kpis']['transacciones_totales']);
        $this->assertSame(6.26, $datos['kpis']['ticket_promedio']);
        $this->assertSame(19.2, $datos['kpis']['porcentaje_entradas']);
        $this->assertSame(3.0, $datos['kpis']['prev_ingresos_totales']);
        $this->assertSame(28.3, $datos['kpis']['diferencia_monetaria']);
        $this->assertCount(5, $datos['serie_diaria']);
        $this->assertEquals(6.0, array_sum(array_column($datos['serie_diaria'], 'entradas')));
        $this->assertEquals(31.3, array_sum(array_column($datos['serie_diaria'], 'total')));
        $this->assertSame(5, array_sum(array_column($datos['serie_diaria'], 'transacciones')));
        $this->assertSame(0.0, $datos['serie_diaria'][2]['entradas']);
        $comparativoEntradas = collect($datos['comparativo'])->firstWhere('indicador', 'Ingresos por entradas');
        $this->assertSame(6.0, $comparativoEntradas['actual']);
        $this->assertSame(2.0, $comparativoEntradas['anterior']);
        $this->assertSame(200.0, $comparativoEntradas['variacion']);
        $this->assertEquals(31.3, $datos['metodos']->sum('ingresos'));
        $this->assertEquals(6.0, $datos['metodos']->firstWhere('tipo', 'Entradas')['ingresos']);
        $this->assertEquals(25.0, $datos['planes']->sum('ingresos'));
    }

    public function test_unpaid_historical_visits_never_become_income_and_entry_only_periods_have_their_own_source(): void
    {
        Asistencia::create(['cliente_id' => $this->cliente->id, 'fecha_hora' => '2026-11-01 09:00:00', 'tipo_acceso' => 'entrada']);
        $this->entrada('2026-11-01 10:00:00');
        $datos = $this->analitica('2026-11-01', '2026-11-01');

        $this->assertSame(2.0, $datos['kpis']['ingresos_totales']);
        $this->assertSame(2.0, $datos['kpis']['ingresos_entradas']);
        $this->assertSame(0.0, $datos['kpis']['ingresos_membresias']);
        $this->assertSame(0.0, $datos['kpis']['ingresos_ventas']);
        $this->assertSame(1, $datos['kpis']['transacciones_totales']);
        $this->assertSame(100.0, $datos['kpis']['porcentaje_entradas']);
        $this->assertTrue($datos['productos']->isEmpty());
        $this->assertTrue($datos['planes']->isEmpty());
        $this->assertCount(1, $datos['metodos']);
        $this->assertSame('Entradas', $datos['metodos'][0]['tipo']);
        $this->assertStringContainsString('Las entradas', implode(' ', $datos['resumen_ejecutivo']));
        $this->assertStringContainsString('pagos de entrada', implode(' ', $datos['metodologia']));
        $this->actingAs($this->admin)->get(route('dashboard', ['preset' => 'personalizado', 'desde' => '2026-11-01', 'hasta' => '2026-11-01']))
            ->assertOk()->assertViewHas('finanzas', fn ($finanzas) => $finanzas['datos']['kpis']['ingresos_entradas'] === 2.0);
    }

    public function test_real_excel_exports_numeric_entry_columns_and_reconciled_totals_without_personal_data(): void
    {
        $this->entrada('2026-11-01 10:00:00');
        $datos = $this->analitica('2026-11-01', '2026-11-01');
        ob_start();
        app(ReporteExcelService::class)->descargar($datos, 'entradas.xlsx')->sendContent();
        $bytes = ob_get_clean();
        $path = tempnam(sys_get_temp_dir(), 'af_entradas_');
        try {
            file_put_contents($path, $bytes);
            $book = IOFactory::load($path);
            $dias = $book->getSheetByName('Ingresos por día');
            $this->assertSame('Entradas ($)', $dias->getCell('D1')->getValue());
            foreach (['D2' => 2.0, 'E2' => 2.0, 'F2' => 1, 'D3' => 2.0, 'E3' => 2.0, 'F3' => 1] as $celda => $valor) {
                $this->assertSame(DataType::TYPE_NUMERIC, $dias->getCell($celda)->getDataType());
                $this->assertEquals($valor, $dias->getCell($celda)->getValue());
            }
            $resumen = $book->getSheetByName('Resumen')->toArray(null, false, false);
            $filaEntradas = collect($resumen)->first(fn ($fila) => $fila[0] === 'Ingresos por entradas');
            $this->assertEquals(2.0, $filaEntradas[1]);
            $this->assertStringContainsString('Entrada', json_encode($resumen));
            $contenido = json_encode(array_map(fn ($hoja) => $hoja->toArray(), $book->getAllSheets()));
            $this->assertStringNotContainsString($this->cliente->correo, $contenido);
            $this->assertStringNotContainsString($this->cliente->nombre, $contenido);
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_pdf_endpoint_receives_the_same_three_source_financial_data(): void
    {
        $this->entrada('2026-11-01 10:00:00');
        $this->mock(ReportePdfService::class, function ($mock) {
            $mock->shouldReceive('descargar')->once()->withArgs(function (array $datos, string $nombre): bool {
                return $datos['kpis']['ingresos_totales'] === 2.0
                    && $datos['kpis']['ingresos_entradas'] === 2.0
                    && $datos['serie_diaria'][0]['entradas'] === 2.0
                    && str_ends_with($nombre, '.pdf');
            })->andReturn(response('PDF de prueba', 200, ['Content-Type' => 'application/pdf']));
        });
        $this->actingAs($this->admin)->get(route('analitica.pdf', ['preset' => 'personalizado', 'desde' => '2026-11-01', 'hasta' => '2026-11-01']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    private function entrada(string $fecha): void
    {
        $asistencia = Asistencia::create(['cliente_id' => $this->cliente->id, 'fecha_hora' => $fecha, 'tipo_acceso' => 'entrada']);
        PagoEntrada::create(['asistencia_id' => $asistencia->id, 'registrado_por' => $this->admin->id, 'importe' => '2.00', 'pagado_en' => $fecha]);
    }

    private function venta(string $importe, string $fecha): void
    {
        $venta = new Venta(['user_id' => $this->admin->id, 'total' => $importe, 'metodo_pago' => 'Efectivo']);
        $venta->created_at = $fecha;
        $venta->save();
    }

    private function analitica(string $desde, string $hasta): array
    {
        $service = app(AnaliticaFinancieraService::class);
        $periodo = $service->resolverPeriodo('personalizado', $desde, $hasta);

        return $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);
    }
}

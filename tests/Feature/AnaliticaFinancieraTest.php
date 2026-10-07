<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnaliticaFinancieraTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $secretaria;

    protected User $entrenador;

    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'rol' => 'Administrador',
            'activo' => true,
            'password_establecida' => true,
        ]);

        $this->secretaria = User::factory()->create([
            'rol' => 'Secretaria',
            'activo' => true,
            'password_establecida' => true,
        ]);

        $this->entrenador = User::factory()->create([
            'rol' => 'Entrenador',
            'activo' => true,
            'password_establecida' => true,
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Juan Perez Sensible',
            'correo' => 'juan.privado@email.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('analitica.index'))->assertRedirect(route('login'));
        $this->get(route('analitica.pdf'))->assertRedirect(route('login'));
        $this->get(route('analitica.excel'))->assertRedirect(route('login'));
    }

    public function test_cliente_receives_403_on_analitica_routes(): void
    {
        $this->actingAs($this->cliente, 'cliente')
            ->get(route('analitica.index'))
            ->assertForbidden();

        $this->actingAs($this->cliente, 'cliente')
            ->get(route('analitica.pdf'))
            ->assertForbidden();

        $this->actingAs($this->cliente, 'cliente')
            ->get(route('analitica.excel'))
            ->assertForbidden();
    }

    public function test_entrenador_receives_403_on_analitica_routes(): void
    {
        $this->actingAs($this->entrenador)
            ->get(route('analitica.index'))
            ->assertForbidden();

        $this->actingAs($this->entrenador)
            ->get(route('analitica.pdf'))
            ->assertForbidden();

        $this->actingAs($this->entrenador)
            ->get(route('analitica.excel'))
            ->assertForbidden();
    }

    public function test_secretaria_receives_403_on_analitica_routes(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('analitica.index'))
            ->assertForbidden();

        $this->actingAs($this->secretaria)
            ->get(route('analitica.pdf'))
            ->assertForbidden();

        $this->actingAs($this->secretaria)
            ->get(route('analitica.excel'))
            ->assertForbidden();
    }

    public function test_administrador_receives_200_and_can_access_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('analitica.index'))
            ->assertOk()
            ->assertSee('Analítica')
            ->assertSee('Financiera');
    }

    public function test_calculates_exact_financial_totals_and_excludes_out_of_period_data(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');

        $planMensual = PlanMembresia::where('nombre', 'Mensual')->first()
            ?? PlanMembresia::create(['nombre' => 'Mensual', 'precio' => 25.00, 'duracion_dias' => 30, 'activo' => true]);

        $planTrimestral = PlanMembresia::where('nombre', 'Trimestral')->first()
            ?? PlanMembresia::create(['nombre' => 'Trimestral', 'precio' => 50.00, 'duracion_dias' => 90, 'activo' => true]);

        // 1. Membresía A: $25 (Dentro del período: 2026-10-05)
        $solicitudA = SolicitudMembresia::create([
            'cliente_id' => $this->cliente->id,
            'plan_nombre' => $planMensual->nombre,
            'precio_acordado' => 25.00,
            'duracion_dias' => 30,
            'estado' => 'activada',
        ]);
        $memA = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'solicitud_id' => $solicitudA->id,
            'plan' => $planMensual->nombre,
            'importe' => 25.00,
            'inicio' => '2026-10-05',
            'fin' => '2026-11-04',
            'cancelada' => false,
        ]);
        PagoMembresia::create([
            'membresia_id' => $memA->id,
            'solicitud_id' => $solicitudA->id,
            'registrado_por' => $this->admin->id,
            'importe' => 25.00,
            'pagado_en' => '2026-10-05 10:00:00',
        ]);

        // 2. Membresía B: $50 (Dentro del período: 2026-10-10)
        $solicitudB = SolicitudMembresia::create([
            'cliente_id' => $this->cliente->id,
            'plan_nombre' => $planTrimestral->nombre,
            'precio_acordado' => 50.00,
            'duracion_dias' => 90,
            'estado' => 'activada',
        ]);
        $memB = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'solicitud_id' => $solicitudB->id,
            'plan' => $planTrimestral->nombre,
            'importe' => 50.00,
            'inicio' => '2026-10-10',
            'fin' => '2027-01-08',
            'cancelada' => false,
        ]);
        PagoMembresia::create([
            'membresia_id' => $memB->id,
            'solicitud_id' => $solicitudB->id,
            'registrado_por' => $this->admin->id,
            'importe' => 50.00,
            'pagado_en' => '2026-10-10 11:00:00',
        ]);

        // 3. Membresía Fuera de período (Septiembre: $100)
        $solicitudC = SolicitudMembresia::create([
            'cliente_id' => $this->cliente->id,
            'plan_nombre' => $planMensual->nombre,
            'precio_acordado' => 100.00,
            'duracion_dias' => 30,
            'estado' => 'activada',
        ]);
        $memC = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'solicitud_id' => $solicitudC->id,
            'plan' => $planMensual->nombre,
            'importe' => 100.00,
            'inicio' => '2026-09-01',
            'fin' => '2026-09-30',
            'cancelada' => false,
        ]);
        PagoMembresia::create([
            'membresia_id' => $memC->id,
            'solicitud_id' => $solicitudC->id,
            'registrado_por' => $this->admin->id,
            'importe' => 100.00,
            'pagado_en' => '2026-09-01 10:00:00',
        ]);

        // Productos
        $prodProteina = Producto::create(['nombre' => 'Proteina Whey', 'precio' => 30.00, 'stock' => 50, 'activo' => true]);
        $prodCreatina = Producto::create(['nombre' => 'Creatina 300g', 'precio' => 20.00, 'stock' => 50, 'activo' => true]);
        $prodBebida = Producto::create(['nombre' => 'Bebida Isotonica', 'precio' => 10.00, 'stock' => 50, 'activo' => true]);

        // 4. Ventas dentro del período: $10, $20, $30
        $v1 = new Venta([
            'user_id' => $this->admin->id,
            'total' => 10.00,
            'metodo_pago' => 'Efectivo',
        ]);
        $v1->created_at = Carbon::parse('2026-10-06 14:00:00');
        $v1->save();
        DetalleVenta::create(['venta_id' => $v1->id, 'producto_id' => $prodBebida->id, 'cantidad' => 1, 'precio_unitario' => 10.00, 'subtotal' => 10.00]);

        $v2 = new Venta([
            'user_id' => $this->admin->id,
            'total' => 20.00,
            'metodo_pago' => 'Tarjeta',
        ]);
        $v2->created_at = Carbon::parse('2026-10-07 15:00:00');
        $v2->save();
        DetalleVenta::create(['venta_id' => $v2->id, 'producto_id' => $prodCreatina->id, 'cantidad' => 1, 'precio_unitario' => 20.00, 'subtotal' => 20.00]);

        $v3 = new Venta([
            'user_id' => $this->admin->id,
            'total' => 30.00,
            'metodo_pago' => 'Transferencia',
        ]);
        $v3->created_at = Carbon::parse('2026-10-08 16:00:00');
        $v3->save();
        DetalleVenta::create(['venta_id' => $v3->id, 'producto_id' => $prodProteina->id, 'cantidad' => 1, 'precio_unitario' => 30.00, 'subtotal' => 30.00]);

        // 5. Venta fuera de período (Septiembre: $500)
        $vOut = new Venta([
            'user_id' => $this->admin->id,
            'total' => 500.00,
            'metodo_pago' => 'Efectivo',
        ]);
        $vOut->created_at = Carbon::parse('2026-09-15 12:00:00');
        $vOut->save();
        DetalleVenta::create(['venta_id' => $vOut->id, 'producto_id' => $prodProteina->id, 'cantidad' => 5, 'precio_unitario' => 100.00, 'subtotal' => 500.00]);

        // Consultar período de Octubre (2026-10-01 al 2026-10-31)
        $service = app(AnaliticaFinancieraService::class);
        $periodo = $service->resolverPeriodo('personalizado', '2026-10-01', '2026-10-31');
        $analitica = $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);

        // Aserciones exactas requeridas:
        // Membresías: 25 + 50 = $75.00
        $this->assertEquals(75.00, $analitica['kpis']['ingresos_membresias']);
        // Ventas: 10 + 20 + 30 = $60.00
        $this->assertEquals(60.00, $analitica['kpis']['ingresos_ventas']);
        // Total: 75 + 60 = $135.00
        $this->assertEquals(135.00, $analitica['kpis']['ingresos_totales']);
        // Transacciones: 2 membresías + 3 ventas = 5 operaciones
        $this->assertEquals(5, $analitica['kpis']['transacciones_totales']);
        // Ticket promedio: 135 / 5 = $27.00
        $this->assertEquals(27.00, $analitica['kpis']['ticket_promedio']);

        // Comprobar que en la vista se muestren exactamente estos totales
        $response = $this->actingAs($this->admin)
            ->get(route('analitica.index', [
                'preset' => 'personalizado',
                'desde' => '2026-10-01',
                'hasta' => '2026-10-31',
            ]));

        $response->assertOk();
        $response->assertSee('$135.00');
        $response->assertSee('$75.00');
        $response->assertSee('$60.00');
        $response->assertSee('$27.00');

        Carbon::setTestNow();
    }

    public function test_rejects_query_exceeding_90_days(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('analitica.index', [
                'preset' => 'personalizado',
                'desde' => '2026-01-01',
                'hasta' => '2026-04-15', // 105 días > 90
            ]));

        $response->assertSessionHasErrors('periodo');
    }

    public function test_empty_period_returns_zeros_without_division_by_zero(): void
    {
        $service = app(AnaliticaFinancieraService::class);
        $periodo = $service->resolverPeriodo('personalizado', '2025-01-01', '2025-01-10');
        $analitica = $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);

        $this->assertEquals(0.00, $analitica['kpis']['ingresos_totales']);
        $this->assertEquals(0.00, $analitica['kpis']['ingresos_membresias']);
        $this->assertEquals(0.00, $analitica['kpis']['ingresos_ventas']);
        $this->assertEquals(0, $analitica['kpis']['transacciones_totales']);
        $this->assertEquals(0.00, $analitica['kpis']['ticket_promedio']);
        $this->assertEquals(0.0, $analitica['kpis']['variacion_porcentual']);

        $response = $this->actingAs($this->admin)
            ->get(route('analitica.index', [
                'preset' => 'personalizado',
                'desde' => '2025-01-01',
                'hasta' => '2025-01-10',
            ]));

        $response->assertOk();
        $response->assertSee('$0.00');
    }

    public function test_export_pdf_and_excel_require_admin_and_contain_zero_personal_data(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');

        // Registrar una venta asociada al cliente sensible
        $prod = Producto::create(['nombre' => 'Barra Energetica', 'precio' => 5.00, 'stock' => 10, 'activo' => true]);
        $venta = Venta::create([
            'user_id' => $this->admin->id,
            'cliente_id' => $this->cliente->id, // cliente: Juan Perez Sensible, juan.privado@email.com
            'total' => 5.00,
            'metodo_pago' => 'Efectivo',
            'created_at' => '2026-10-10 10:00:00',
        ]);
        DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $prod->id, 'cantidad' => 1, 'precio_unitario' => 5.00, 'subtotal' => 5.00]);

        // 1. Exportar PDF
        $responsePdf = $this->actingAs($this->admin)
            ->get(route('analitica.pdf', ['preset' => 'este_mes']));

        $responsePdf->assertOk();
        $responsePdf->assertHeader('content-disposition');
        $this->assertStringContainsString('.pdf', $responsePdf->headers->get('content-disposition'));

        // Obtener contenido del PDF y verificar que NO contenga PII
        $pdfContent = $responsePdf->getContent();
        $this->assertStringNotContainsString('Juan Perez Sensible', $pdfContent);
        $this->assertStringNotContainsString('juan.privado@email.com', $pdfContent);

        // 2. Exportar Excel
        $responseExcel = $this->actingAs($this->admin)
            ->get(route('analitica.excel', ['preset' => 'este_mes']));

        $responseExcel->assertOk();
        $responseExcel->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', $responseExcel->headers->get('content-disposition'));

        // 3. Comprobar que se registraron los logs de auditoría en reportes_financieros_logs
        $this->assertDatabaseHas('reportes_financieros_logs', [
            'user_id' => $this->admin->id,
            'tipo' => 'pdf',
        ]);
        $this->assertDatabaseHas('reportes_financieros_logs', [
            'user_id' => $this->admin->id,
            'tipo' => 'excel',
        ]);

        Carbon::setTestNow();
    }
}

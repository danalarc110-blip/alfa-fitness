<?php

namespace Tests\Feature;

use App\Mail\ComprobanteVentaMail;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagosSimuladosTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(array $pago = []): array
    {
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));
        $producto = Producto::create(['nombre' => 'Agua de prueba', 'precio' => '0.29', 'stock' => 10, 'activo' => true]);

        return [$producto, array_merge([
            'venta_uuid' => (string) Str::uuid(), 'pago_simulado' => 1, 'metodo_pago' => 'Efectivo',
            'efectivo_recibido' => '1.00', 'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ], $pago)];
    }

    public function test_simulated_cash_computes_exact_change_and_receipt_and_retry(): void
    {
        [$producto, $datos] = $this->preparar(['total' => '0.01', 'cambio' => '100.00']);
        $this->post(route('ventas.store'), $datos)->assertSessionHasNoErrors();
        $venta = Venta::firstOrFail();
        $this->assertTrue($venta->pago_simulado);
        $this->assertSame('0.58', $venta->total);
        $this->assertSame('0.42', $venta->cambio);
        $this->assertSame('1.00', $venta->efectivo_recibido);
        $this->assertStringStartsWith('SIM-', $venta->referencia_pago);
        $this->post(route('ventas.store'), ['venta_uuid' => $datos['venta_uuid']])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ventas', 1);
        $this->assertSame(8, $producto->fresh()->stock);
        $this->get(route('ventas.comprobante', $venta))->assertOk()->assertSee('SIN COBRO REAL')->assertSee('$0.42');
        $correo = view('emails.comprobante-venta', ['venta' => $venta->load('detalles.producto', 'user')])->render();
        $this->assertStringContainsString('SIN COBRO REAL', $correo);
        $this->assertStringContainsString('Cambio: $0.42', $correo);
        $this->assertStringNotContainsString('electrónico oficial', $correo);
        Mail::fake();
        $this->post(route('ventas.enviar-correo', $venta), ['correo' => 'destino@example.test'])
            ->assertSessionHas('status', fn ($mensaje) => str_contains($mensaje, 'No se entregó por correo'));
        Mail::assertSent(ComprobanteVentaMail::class);
    }

    public function test_insufficient_or_missing_cash_never_changes_stock_or_creates_sale(): void
    {
        [$producto, $datos] = $this->preparar(['efectivo_recibido' => '0.57']);
        $this->post(route('ventas.store'), $datos)->assertSessionHasErrors('efectivo_recibido');
        unset($datos['efectivo_recibido']);
        $this->post(route('ventas.store'), $datos)->assertSessionHasErrors('efectivo_recibido');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $producto->fresh()->stock);
    }

    public function test_declined_card_can_retry_with_same_uuid_without_partial_sale(): void
    {
        [$producto, $datos] = $this->preparar(['metodo_pago' => 'Tarjeta', 'resultado_tarjeta' => 'rechazada']);
        $this->from(route('ventas.index'))->post(route('ventas.store'), $datos)
            ->assertSessionHasErrors('resultado_tarjeta')->assertSessionHasInput('venta_uuid', $datos['venta_uuid']);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $producto->fresh()->stock);
        $datos['resultado_tarjeta'] = 'aprobada';
        $this->post(route('ventas.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ventas', 1);
        $this->assertTrue(Venta::first()->pago_simulado);
        $this->assertNull(Venta::first()->efectivo_recibido);
        $this->assertNull(Venta::first()->cambio);
        $this->assertSame(8, $producto->fresh()->stock);
    }

    public function test_transfer_simulation_gets_a_reference_and_never_uses_cash_fields(): void
    {
        [$producto, $datos] = $this->preparar(['metodo_pago' => 'Transferencia']);
        $this->post(route('ventas.store'), $datos)->assertSessionHasNoErrors();
        $this->assertNotEmpty(Venta::first()->referencia_pago);
        $this->assertNull(Venta::first()->efectivo_recibido);
        $this->assertSame(8, $producto->fresh()->stock);
    }

    public function test_card_numbers_and_security_codes_are_rejected_without_flashing_sensitive_input(): void
    {
        [$producto, $datos] = $this->preparar(['metodo_pago' => 'Tarjeta', 'resultado_tarjeta' => 'aprobada', 'numero_tarjeta' => '4242424242424242', 'cvv' => '123']);
        $this->post(route('ventas.store'), $datos)->assertSessionHasErrors(['numero_tarjeta', 'cvv'])
            ->assertSessionMissing('_old_input.numero_tarjeta')->assertSessionMissing('_old_input.cvv');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $producto->fresh()->stock);
    }

    public function test_simulated_sales_do_not_inflate_any_financial_aggregate(): void
    {
        [$producto, $datos] = $this->preparar();
        $this->post(route('ventas.store'), $datos)->assertSessionHasNoErrors();
        $real = $datos;
        $real['venta_uuid'] = (string) Str::uuid();
        $real['pago_simulado'] = 0;
        $this->post(route('ventas.store'), $real)->assertSessionHasNoErrors();
        $reporte = app(AnaliticaFinancieraService::class)->obtenerAnalitica(today(), now()->endOfDay(), 1);
        $this->assertSame(0.58, $reporte['kpis']['ingresos_ventas']);
        $this->assertSame(1, $reporte['kpis']['transacciones_ventas']);
        $this->assertEquals(0.58, array_sum(array_column($reporte['serie_diaria'], 'ventas')));
        $this->assertEquals(0.58, $reporte['productos']->sum('ingresos'));
        $this->assertEquals(0.58, $reporte['metodos']->sum('ingresos'));
        $response = $this->get(route('ventas.index'))->assertOk();
        $this->assertEquals(0.58, $response->viewData('hoyTotal'));
        $this->assertSame(1, $response->viewData('simulacionesHoy'));
    }

    public function test_admin_income_uses_actual_membership_payments_and_excludes_simulated_sales(): void
    {
        [$producto, $datos] = $this->preparar();
        $this->post(route('ventas.store'), $datos)->assertSessionHasNoErrors();
        $cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'correo' => 'sin-pago@example.test', 'activo' => true]);
        Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Registro histórico sin cobro', 'importe' => '50.00', 'inicio' => today(), 'fin' => today()->addDays(30)]);
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $response->assertSee('Configuración pendiente')->assertViewHas('pendientesConfiguracion', fn ($pendientes) => count($pendientes) > 0);
        $metricas = collect($response->viewData('metricas'))->mapWithKeys(fn ($item) => [$item[0] => $item[1]]);
        $this->assertSame('$0.00', $metricas['Ingresos este mes']);
        $this->assertSame('$0.00', $metricas['Membresías cobradas']);
        $this->assertSame('$0.00', $metricas['Ventas mostrador']);
        $this->assertSame(1, $metricas['Ventas simuladas este mes']);
    }
}

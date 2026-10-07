<?php

namespace Tests\Feature;

use App\Mail\ComprobanteVentaMail;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaIntegridadTest extends TestCase
{
    use RefreshDatabase;

    private function producto(int $stock = 1000): Producto
    {
        return Producto::create(['nombre' => 'Agua', 'precio' => '0.29', 'stock' => $stock, 'activo' => true]);
    }

    private function payload(Producto $producto): array
    {
        return ['venta_uuid' => (string) Str::uuid(), 'metodo_pago' => 'Efectivo', 'items' => [['producto_id' => $producto->id, 'cantidad' => 2]]];
    }

    public function test_sale_retry_returns_same_sale_without_consuming_stock_twice(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto();
        $payload = $this->payload($producto);

        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $id = session('venta_creada_id');
        $payload['venta_uuid'] = strtoupper($payload['venta_uuid']);
        $this->post(route('ventas.store'), $payload)->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('venta_creada_id', $id);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('detalle_ventas', 1);
        $this->assertSame(998, $producto->fresh()->stock);
        $this->assertSame('0.58', Venta::firstOrFail()->total);
    }

    public function test_sale_retry_uuid_cannot_reveal_another_sellers_sale(): void
    {
        $owner = User::factory()->create(['rol' => 'Secretaria']);
        $otro = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto();
        $payload = $this->payload($producto);
        $this->actingAs($owner)->post(route('ventas.store'), $payload)->assertRedirect();
        $this->actingAs($otro)->post(route('ventas.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('ventas', 1);
        $this->assertSame(998, $producto->fresh()->stock);
    }

    public function test_duplicate_lines_cannot_bypass_per_product_quantity_limit(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto(2000);
        $payload = $this->payload($producto);
        $payload['items'] = array_fill(0, 2, ['producto_id' => $producto->id, 'cantidad' => 600]);
        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(2000, $producto->fresh()->stock);
    }

    public function test_failed_cart_preserves_all_products_and_records(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $primero = $this->producto(10);
        $insuficiente = $this->producto(1);
        $payload = $this->payload($primero);
        $payload['items'][] = ['producto_id' => $insuficiente->id, 'cantidad' => 2];
        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('detalle_ventas', 0);
        $this->assertSame(10, $primero->fresh()->stock);
        $this->assertSame(1, $insuficiente->fresh()->stock);
    }

    public function test_inactive_client_cannot_purchase(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Inactivo', 'correo' => 'inactivo@example.test', 'activo' => false]);
        $producto = $this->producto();
        $payload = $this->payload($producto);
        $payload['cliente_id'] = $cliente->id;
        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(1000, $producto->fresh()->stock);
    }

    public function test_cart_line_limit_is_enforced(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto();
        $payload = $this->payload($producto);
        $payload['items'] = array_fill(0, 101, ['producto_id' => $producto->id, 'cantidad' => 1]);
        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(1000, $producto->fresh()->stock);
    }

    public function test_search_by_client_email_and_until_date_without_start_date(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'correo-unico@example.test', 'activo' => true]);
        Venta::create(['user_id' => $staff->id, 'cliente_id' => $cliente->id, 'total' => '1.00', 'notas' => 'Factura localizada']);
        Venta::create(['user_id' => $staff->id, 'total' => '2.00', 'notas' => 'Factura excluida']);
        $this->actingAs($staff)->get(route('ventas.index', ['q' => $cliente->correo, 'hasta' => today()->toDateString()]))
            ->assertOk()->assertSee('Factura localizada')->assertDontSee('Factura excluida');
    }

    public function test_email_failure_logs_no_recipient_or_transport_secrets(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $venta = Venta::create(['user_id' => $staff->id, 'total' => '1.00']);
        Mail::shouldReceive('to')->with('privado@example.test')->once()->andReturnSelf();
        Mail::shouldReceive('send')->withArgs(fn ($mail) => $mail instanceof ComprobanteVentaMail)->once()->andThrow(new \RuntimeException('smtp-secret recipient privado@example.test'));
        Log::shouldReceive('warning')->once()->with('Fallo al enviar comprobante de venta por correo.', ['venta_id' => $venta->id, 'exception' => \RuntimeException::class]);
        $this->actingAs($staff)->post(route('ventas.enviar-correo', $venta), ['correo' => 'privado@example.test'])->assertRedirect()->assertSessionHas('error');
    }
}

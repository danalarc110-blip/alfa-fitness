<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PuntoVentaTest extends TestCase
{
    use RefreshDatabase;

    private function producto(array $datos = []): Producto
    {
        return Producto::create(array_merge([
            'nombre' => 'Agua purificada',
            'categoria' => 'Bebidas',
            'precio' => '0.29',
            'stock' => 10,
            'activo' => true,
        ], $datos));
    }

    private function payload(Producto $producto, array $datos = []): array
    {
        return array_merge([
            'venta_uuid' => (string) Str::uuid(),
            'cliente_id' => null,
            'metodo_pago' => 'Efectivo',
            'notas' => 'Venta de recepción',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
        ], $datos);
    }

    public function test_staff_catalog_shows_category_prices_stock_and_sold_out_products(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $disponible = $this->producto();
        $agotado = $this->producto(['nombre' => 'Bebida agotada', 'stock' => 0]);
        $inactivo = $this->producto(['nombre' => 'Producto retirado', 'activo' => false]);

        $response = $this->actingAs($staff)->get(route('ventas.index'))
            ->assertOk()->assertSee('Buscar por nombre o categoría')
            ->assertSee('Bebidas')->assertSee('$0.29')->assertSee('Stock: 10')
            ->assertSee('Agotado')->assertSee('Revisar venta')
            ->assertDontSee($inactivo->nombre);
        $catalogo = $response->viewData('catalogoVenta')->keyBy('id');
        $this->assertSame(29, $catalogo[$disponible->id]['precio_centavos']);
        $this->assertSame(0, $catalogo[$agotado->id]['stock']);
        $this->assertCount(2, $catalogo);
        $this->assertMatchesRegularExpression('/data-anadir-producto="'.$agotado->id.'"[^>]*disabled/s', $response->getContent());
        $this->assertMatchesRegularExpression('/name="venta_uuid" value="[a-f0-9-]{36}"/', $response->getContent());
    }

    public function test_failed_sale_restores_cart_client_payment_notes_and_uuid(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Cliente POS', 'correo' => 'pos@example.test', 'activo' => true]);
        $primero = $this->producto();
        $segundo = $this->producto(['nombre' => 'Proteína', 'stock' => 1]);
        $payload = $this->payload($primero, [
            'cliente_id' => $cliente->id,
            'metodo_pago' => 'Transferencia',
            'notas' => 'Voucher POS-123',
            'items' => [
                ['producto_id' => $primero->id, 'cantidad' => 3],
                ['producto_id' => $segundo->id, 'cantidad' => 2],
            ],
        ]);

        $this->actingAs($staff)->from(route('ventas.index'))->post(route('ventas.store'), $payload)
            ->assertRedirect(route('ventas.index'))->assertSessionHasErrors('items')
            ->assertSessionHasInput('items', $payload['items'])
            ->assertSessionHasInput('cliente_id', $cliente->id)
            ->assertSessionHasInput('metodo_pago', 'Transferencia')
            ->assertSessionHasInput('notas', 'Voucher POS-123')
            ->assertSessionHasInput('venta_uuid', $payload['venta_uuid']);

        $response = $this->get(route('ventas.index'))->assertOk()
            ->assertSee('Conservamos tus datos para corregirla.')
            ->assertSee('Stock insuficiente')->assertSee('value="Voucher POS-123"', false)
            ->assertSee('value="'.$payload['venta_uuid'].'"', false);
        $this->assertSame($payload['items'], $response->viewData('itemsAnteriores'));
        $this->assertMatchesRegularExpression('/value="'.$cliente->id.'" selected/', $response->getContent());
        $this->assertMatchesRegularExpression('/value="Transferencia" selected/', $response->getContent());
        preg_match('/<script id="venta-config" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $this->assertTrue(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['recuperarVenta']);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $primero->fresh()->stock);
        $this->assertSame(1, $segundo->fresh()->stock);
    }

    public function test_inactive_cart_line_is_preserved_for_removal_after_error(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto(['activo' => false]);
        $payload = $this->payload($producto);
        $this->actingAs($staff)->from(route('ventas.index'))->post(route('ventas.store'), $payload)
            ->assertSessionHasErrors('items');
        $response = $this->get(route('ventas.index'))->assertOk();
        $this->assertSame($payload['items'], $response->viewData('itemsAnteriores'));
        $this->assertFalse($response->viewData('catalogoVenta')->first()['activo']);
        $response->assertDontSee('data-anadir-producto="'.$producto->id.'"', false);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_sale_requires_valid_retry_identifier(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto();
        $payload = $this->payload($producto);
        unset($payload['venta_uuid']);
        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertSessionHasErrors('venta_uuid');
        $payload['venta_uuid'] = 'identificador-no-valido';
        $this->from(route('ventas.index'))->post(route('ventas.store'), $payload)->assertSessionHasErrors('venta_uuid');
        $response = $this->get(route('ventas.index'))->assertOk();
        preg_match('/name="venta_uuid" value="([a-f0-9-]{36})"/', $response->getContent(), $uuid);
        $this->assertTrue(Str::isUuid($uuid[1]));
        $this->assertSame($payload['items'], $response->viewData('itemsAnteriores'));
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $producto->fresh()->stock);
    }

    public function test_existing_sale_retry_survives_mutable_client_and_product_changes(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Cliente POS', 'correo' => 'retry@example.test', 'activo' => true]);
        $producto = $this->producto(['stock' => 2]);
        $payload = $this->payload($producto, ['cliente_id' => $cliente->id]);
        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertSessionHasNoErrors();
        $ventaId = session('venta_creada_id');
        $cliente->update(['activo' => false]);
        $producto->update(['activo' => false]);
        $this->post(route('ventas.store'), $payload)->assertSessionHasNoErrors()->assertSessionHas('venta_creada_id', $ventaId);
        $this->post(route('ventas.store'), ['venta_uuid' => strtoupper($payload['venta_uuid'])])
            ->assertSessionHasNoErrors()->assertSessionHas('venta_creada_id', $ventaId);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('detalle_ventas', 1);
        $this->assertSame(0, $producto->fresh()->stock);
        $this->assertSame('0.58', Venta::firstOrFail()->total);
    }

    public function test_duplicate_products_are_grouped_and_server_ignores_posted_totals(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto();
        $payload = $this->payload($producto, [
            'total' => '0.01',
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => '0.01'],
                ['producto_id' => $producto->id, 'cantidad' => 3, 'subtotal' => '0.01'],
            ],
        ]);
        $this->actingAs($staff)->post(route('ventas.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('detalle_ventas', 1);
        $this->assertDatabaseHas('detalle_ventas', ['producto_id' => $producto->id, 'cantidad' => 5, 'subtotal' => '1.45']);
        $this->assertSame('1.45', Venta::firstOrFail()->total);
        $this->assertSame(5, $producto->fresh()->stock);
    }

    public function test_each_presential_payment_method_can_register_public_sale(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $producto = $this->producto();
        foreach (['Efectivo', 'Tarjeta', 'Transferencia'] as $metodo) {
            $this->actingAs($staff)->post(route('ventas.store'), $this->payload($producto, ['metodo_pago' => $metodo]))
                ->assertSessionHasNoErrors()->assertSessionHas('venta_creada_id');
            $this->assertDatabaseHas('ventas', ['cliente_id' => null, 'metodo_pago' => $metodo, 'total' => '0.58']);
        }
        $this->assertDatabaseCount('ventas', 3);
        $this->assertSame(4, $producto->fresh()->stock);
    }

    public function test_clients_and_trainers_cannot_register_or_consult_sales(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'no-pos@example.test', 'activo' => true]);
        $producto = $this->producto();
        $this->actingAs($cliente, 'cliente')->get(route('ventas.index'))->assertForbidden();
        $this->post(route('ventas.store'), $this->payload($producto))->assertForbidden();
        $this->post(route('cliente.logout'));
        $trainer = User::factory()->create(['rol' => 'Entrenador']);
        $this->actingAs($trainer)->get(route('ventas.index'))->assertForbidden();
        $this->post(route('ventas.store'), $this->payload($producto))->assertForbidden();
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $producto->fresh()->stock);
    }
}

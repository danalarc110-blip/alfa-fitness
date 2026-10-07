<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_and_register_sales(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Juan Pérez', 'correo' => 'juan@example.test', 'activo' => true]);
        $producto = Producto::create([
            'nombre' => 'Proteína Whey 2lb',
            'precio' => 35.00,
            'stock' => 10,
            'activo' => true,
        ]);

        // Unauthorized user (e.g. member without inventory permission) cannot access sales
        $this->actingAs($cliente, 'cliente')->get(route('ventas.index'))->assertForbidden();
        $this->post(route('cliente.logout'));

        // Staff can view sales index
        $this->actingAs($secretaria)->get(route('ventas.index'))->assertOk();

        // Staff registers a sale of 2 units
        $response = $this->actingAs($secretaria)->post(route('ventas.store'), [
            'cliente_id' => $cliente->id,
            'metodo_pago' => 'Efectivo',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 2,
                ],
            ],
        ]);

        $response->assertRedirect()->assertSessionHas('status');

        // Stock decreased by 2
        $this->assertSame(8, $producto->fresh()->stock);

        // Venta and Detalle recorded with total 70.00
        $this->assertDatabaseHas('ventas', [
            'user_id' => $secretaria->id,
            'cliente_id' => $cliente->id,
            'total' => 70.00,
            'metodo_pago' => 'Efectivo',
        ]);

        $this->assertDatabaseHas('detalle_ventas', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 35.00,
            'subtotal' => 70.00,
        ]);
    }

    public function test_cannot_sell_more_than_available_stock(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $producto = Producto::create([
            'nombre' => 'Barra Energética',
            'precio' => 2.50,
            'stock' => 3,
            'activo' => true,
        ]);

        $response = $this->actingAs($secretaria)->post(route('ventas.store'), [
            'metodo_pago' => 'Tarjeta',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 5, // Exceeds available stock
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(3, $producto->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_sales_filtering_by_search_query_and_payment_method(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $clienteA = Cliente::create(['nombre' => 'Carlos López', 'correo' => 'carlos@test.com', 'activo' => true]);
        $clienteB = Cliente::create(['nombre' => 'María Gómez', 'correo' => 'maria@test.com', 'activo' => true]);

        $productoA = Producto::create(['nombre' => 'Proteína Vainilla', 'precio' => 30.00, 'stock' => 10, 'activo' => true]);
        $productoB = Producto::create(['nombre' => 'Creatina Monohidrato', 'precio' => 20.00, 'stock' => 10, 'activo' => true]);

        // Venta A: Carlos López, Efectivo, Proteína Vainilla
        $ventaA = Venta::create([
            'user_id' => $secretaria->id,
            'cliente_id' => $clienteA->id,
            'total' => 30.00,
            'metodo_pago' => 'Efectivo',
            'notas' => 'Venta rápida cliente Carlos',
        ]);
        $ventaA->detalles()->create([
            'producto_id' => $productoA->id,
            'cantidad' => 1,
            'precio_unitario' => 30.00,
            'subtotal' => 30.00,
        ]);

        // Venta B: María Gómez, Tarjeta, Creatina Monohidrato
        $ventaB = Venta::create([
            'user_id' => $secretaria->id,
            'cliente_id' => $clienteB->id,
            'total' => 20.00,
            'metodo_pago' => 'Tarjeta',
            'notas' => 'Pago por terminal María',
        ]);
        $ventaB->detalles()->create([
            'producto_id' => $productoB->id,
            'cantidad' => 1,
            'precio_unitario' => 20.00,
            'subtotal' => 20.00,
        ]);

        // Buscar por texto q="Carlos": aparece la nota de la venta A y no la de la venta B
        $this->actingAs($secretaria)
            ->get(route('ventas.index', ['q' => 'Carlos']))
            ->assertOk()
            ->assertSee('Venta rápida cliente Carlos')
            ->assertDontSee('Pago por terminal María');

        // Filtrar por método de pago "Tarjeta": aparece la venta B y no la venta A
        $this->actingAs($secretaria)
            ->get(route('ventas.index', ['metodo' => 'Tarjeta']))
            ->assertOk()
            ->assertSee('Pago por terminal María')
            ->assertDontSee('Venta rápida cliente Carlos');
    }
}

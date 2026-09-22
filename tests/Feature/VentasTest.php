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
}

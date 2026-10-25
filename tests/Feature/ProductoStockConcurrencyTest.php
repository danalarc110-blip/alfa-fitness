<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductoStockConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function producto(): Producto
    {
        return Producto::create(['nombre' => 'Agua', 'precio' => '1.25', 'stock' => 10, 'activo' => true]);
    }

    private function datos(array $cambios = []): array
    {
        return array_merge(['nombre' => 'Agua fría', 'precio' => '1.25', 'stock' => 10, 'stock_original' => 10, 'activo' => 1], $cambios);
    }

    private function venderDos(Producto $producto): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))
            ->post(route('ventas.store'), [
                'venta_uuid' => (string) Str::uuid(), 'pago_simulado' => 1,
                'metodo_pago' => 'Efectivo', 'efectivo_recibido' => '2.50',
                'items' => [['producto_id' => $producto->id, 'cantidad' => 2]],
            ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(8, $producto->fresh()->stock);
    }

    public function test_stale_metadata_form_preserves_units_sold_after_it_was_opened(): void
    {
        $producto = $this->producto();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->get(route('productos.index'))->assertOk();
        $this->venderDos($producto);

        $this->actingAs($admin)->put(route('productos.update', $producto), $this->datos())
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(8, $producto->fresh()->stock);
        $this->assertSame('Agua fría', $producto->fresh()->nombre);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseHas('detalle_ventas', ['producto_id' => $producto->id, 'cantidad' => 2]);
    }

    public function test_stale_manual_stock_adjustment_is_rejected_without_partial_metadata_changes(): void
    {
        $producto = $this->producto();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->venderDos($producto);

        $this->actingAs($admin)->from(route('productos.index'))
            ->put(route('productos.update', $producto), $this->datos(['stock' => 15, 'precio' => '2.00']))
            ->assertRedirect(route('productos.index'))->assertSessionHasErrors('stock');

        $this->assertSame(8, $producto->fresh()->stock);
        $this->assertSame('Agua', $producto->fresh()->nombre);
        $this->assertSame('1.25', $producto->fresh()->precio);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_manual_stock_adjustment_with_current_original_is_allowed(): void
    {
        $producto = $this->producto();
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']))
            ->put(route('productos.update', $producto), $this->datos(['stock' => 15]))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(15, $producto->fresh()->stock);
    }

    public function test_stock_update_cannot_omit_the_original_value(): void
    {
        $producto = $this->producto();
        $datos = $this->datos(['stock' => 15]);
        unset($datos['stock_original']);
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']))
            ->put(route('productos.update', $producto), $datos)
            ->assertRedirect()->assertSessionHasErrors('stock_original');

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame('Agua', $producto->fresh()->nombre);
    }

    public function test_secretary_cannot_supply_an_original_stock_value(): void
    {
        $producto = $this->producto();
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))
            ->put(route('productos.update', $producto), [
                'nombre' => 'Alterado', 'precio' => '1.25', 'activo' => 1, 'stock_original' => 10,
            ])->assertRedirect()->assertSessionHasErrors('stock_original');

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame('Agua', $producto->fresh()->nombre);
    }

    public function test_admin_edit_form_includes_the_rendered_stock_as_original(): void
    {
        $this->producto();
        $response = $this->actingAs(User::factory()->create(['rol' => 'Administrador']))
            ->get(route('productos.index'))->assertOk();
        $this->assertTrue(str_contains($response->getContent(), 'name="stock_original" value="10"'), 'El formulario debe conservar las existencias originales.');
    }
}

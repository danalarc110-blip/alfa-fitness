<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentasIdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_form_does_not_charge_or_reduce_stock_twice(): void
    {
        $p = Producto::create(['nombre' => 'Agua', 'precio' => 1, 'stock' => 10, 'activo' => true]);
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));
        $data = ['request_uid' => (string) Str::uuid(), 'metodo_pago' => 'Efectivo', 'items' => [['producto_id' => $p->id, 'cantidad' => 2]]];
        $this->post(route('ventas.store'), $data)->assertSessionHasNoErrors();
        $id = Venta::firstOrFail()->id;
        $this->post(route('ventas.store'), $data)->assertSessionHasNoErrors()->assertSessionHas('venta_creada_id', $id);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('detalle_ventas', 1);
        $this->assertSame(8, $p->fresh()->stock);
    }

    public function test_reusing_voided_or_other_cashiers_request_is_rejected(): void
    {
        $p = Producto::create(['nombre' => 'Agua', 'precio' => 1, 'stock' => 10, 'activo' => true]);
        $first = User::factory()->create(['rol' => 'Secretaria']);
        $second = User::factory()->create(['rol' => 'Secretaria']);
        $data = ['request_uid' => (string) Str::uuid(), 'metodo_pago' => 'Efectivo', 'items' => [['producto_id' => $p->id, 'cantidad' => 2]]];
        $this->actingAs($first, 'web')->post(route('ventas.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($second, 'web')->post(route('ventas.store'), $data)->assertSessionHasErrors('items');
        Venta::firstOrFail()->forceFill(['anulada_en' => now()])->save();
        $this->actingAs($first, 'web')->post(route('ventas.store'), $data)->assertSessionHasErrors('items');
        $this->assertSame(8, $p->fresh()->stock);
    }

    public function test_amount_overflow_is_validation_error_and_rolls_back_inventory(): void
    {
        $p = Producto::create(['nombre' => 'Máximo', 'precio' => 999999, 'stock' => 999, 'activo' => true]);
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->post(route('ventas.store'), ['metodo_pago' => 'Efectivo', 'items' => [['producto_id' => $p->id, 'cantidad' => 999]]])->assertSessionHasErrors('items');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(999, $p->fresh()->stock);
    }
}

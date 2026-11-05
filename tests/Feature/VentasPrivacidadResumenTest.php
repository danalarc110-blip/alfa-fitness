<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentasPrivacidadResumenTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_can_operate_sales_and_receipts_without_receiving_business_totals(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $venta = Venta::create(['user_id' => $secretaria->id, 'total' => '57.89', 'metodo_pago' => 'Efectivo']);

        $this->actingAs($secretaria)->get(route('ventas.index'))->assertOk()
            ->assertViewHas('hoyTotal', fn ($total) => $total === null)
            ->assertViewHas('mesTotal', fn ($total) => $total === null)
            ->assertViewHas('hoyConteo', 1)
            ->assertViewHas('ventas', fn ($ventas) => $ventas->first()->id === $venta->id)
            ->assertDontSee('data-sales-financial-summary', false)
            ->assertSee('Revisar venta');

        $this->get(route('ventas.comprobante', $venta))->assertOk()->assertSee('$57.89');
    }

    public function test_administrator_receives_daily_and_monthly_sales_totals(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        Venta::create(['user_id' => $admin->id, 'total' => '57.89', 'metodo_pago' => 'Efectivo']);

        $this->actingAs($admin)->get(route('ventas.index'))->assertOk()
            ->assertViewHas('hoyTotal', fn ($total) => (float) $total === 57.89)
            ->assertViewHas('mesTotal', fn ($total) => (float) $total === 57.89)
            ->assertSee('data-sales-financial-summary', false);
    }
}

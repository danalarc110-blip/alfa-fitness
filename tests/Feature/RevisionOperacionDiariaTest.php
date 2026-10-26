<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RevisionOperacionDiariaTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): Cliente
    {
        return Cliente::create(['nombre' => 'Socio de recepción', 'correo' => 'operacion@example.test', 'activo' => true]);
    }

    public function test_banning_a_member_during_a_visit_closes_the_visit_and_prevents_reentry(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $this->actingAs($secretaria)->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin, 'web')->patch(route('cuentas.banear', $cliente))->assertSessionHasNoErrors();
        $this->assertNotNull(Asistencia::firstOrFail()->fecha_salida);
        $this->assertSame($admin->id, Asistencia::firstOrFail()->salida_registrada_por);
        $this->actingAs($secretaria, 'web')->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('asistencias', 1);
        $this->get(route('asistencia.index', ['q' => $cliente->correo]))->assertOk()
            ->assertViewHas('clientes', fn ($clientes) => ! $clientes->contains('id', $cliente->id));
    }

    public function test_reception_displays_the_current_paused_membership_even_with_a_paid_future_renewal(): void
    {
        $cliente = $this->cliente();
        $vigente = Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Actual', 'importe' => '25.00', 'inicio' => today()->subDays(10), 'fin' => today()->addDays(20)]);
        PausaMembresia::create(['cliente_id' => $cliente->id, 'membresia_id' => $vigente->id, 'dias' => 3, 'motivo' => 'Viaje', 'estado' => 'aprobada', 'inicio_pausa' => today(), 'fin_pausa_estimada' => today()->addDays(2)]);
        Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Renovación cobrada', 'importe' => '25.00', 'inicio' => today()->addDays(21), 'fin' => today()->addDays(50)]);
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))
            ->get(route('asistencia.index', ['q' => $cliente->correo]))->assertOk()
            ->assertSee('Membresia: En Pausa')->assertDontSee('Membresia: Programada');
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_reception_keeps_future_expired_and_cancelled_membership_states_distinct(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));
        foreach (['Programada', 'Vencida', 'Sin membresia'] as $indice => $estado) {
            $cliente = Cliente::create(['nombre' => $estado, 'correo' => 'estado'.$indice.'@example.test', 'activo' => true]);
            Membresia::create([
                'cliente_id' => $cliente->id, 'plan' => 'Período', 'importe' => '25.00',
                'inicio' => $indice === 0 ? today()->addDay() : today()->subDays(30),
                'fin' => $indice === 0 ? today()->addDays(30) : today()->subDay(),
                'cancelada' => $indice === 2,
            ]);
            $this->get(route('asistencia.index', ['q' => $cliente->correo]))->assertOk()->assertSee('Membresia: '.$estado);
        }
    }

    public function test_daily_cycle_preserves_payments_stock_and_attendance_across_retries_and_next_day_pause(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(8));
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $plan = PlanMembresia::firstOrFail();
        $this->actingAs($cliente, 'cliente')->post(route('membresias.solicitar'), ['plan_id' => $plan->id])->assertSessionHasNoErrors();
        $solicitud = SolicitudMembresia::firstOrFail();
        auth('cliente')->logout();
        $this->actingAs($secretaria, 'web')->patch(route('membresias.activar', $solicitud), ['importe' => $plan->precio])->assertSessionHasNoErrors();
        $membresia = Membresia::firstOrFail();
        $finOriginal = $membresia->fin->copy();
        $this->patch(route('membresias.activar', $solicitud), ['importe' => $plan->precio])->assertSessionHasErrors('importe');
        $this->assertDatabaseCount('pagos_membresia', 1);
        $this->post(route('membresias.pausa.solicitar', $membresia), ['dias' => 3, 'motivo' => 'Viaje desde mañana', 'inicio_pausa' => today()->addDay()->toDateString()])->assertSessionHasNoErrors();
        $this->assertTrue($membresia->fresh()->fin->equalTo($finOriginal->copy()->addDays(3)));
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasErrors('cliente_id');

        $agua = Producto::create(['nombre' => 'Agua', 'precio' => '0.29', 'stock' => 5, 'activo' => true]);
        $barra = Producto::create(['nombre' => 'Barra', 'precio' => '1.25', 'stock' => 1, 'activo' => true]);
        $venta = ['venta_uuid' => (string) Str::uuid(), 'cliente_id' => $cliente->id, 'metodo_pago' => 'Efectivo', 'items' => [['producto_id' => $agua->id, 'cantidad' => 2], ['producto_id' => $barra->id, 'cantidad' => 2]]];
        $this->post(route('ventas.store'), $venta)->assertSessionHasErrors('items');
        $this->assertSame(5, $agua->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
        $venta['items'][1]['cantidad'] = 1;
        $this->post(route('ventas.store'), $venta)->assertSessionHasNoErrors();
        $this->post(route('ventas.store'), $venta)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('detalle_ventas', 2);
        $this->assertSame('1.83', Venta::firstOrFail()->total);
        $this->assertSame(3, $agua->fresh()->stock);
        $this->assertSame(0, $barra->fresh()->stock);
        $this->travel(2)->hours();
        $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->assertSame('2h 0m', Asistencia::firstOrFail()->duracion);

        $this->travel(1)->days();
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasErrors('cliente_id');
        $this->patch(route('membresias.reanudar', $membresia))->assertSessionHasNoErrors();
        $this->assertTrue($membresia->fresh()->fin->equalTo($finOriginal->copy()->addDay()));
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('asistencias', 2);
        $this->assertSame(1, Asistencia::whereNull('fecha_salida')->count());
        $this->assertDatabaseCount('pagos_membresia', 1);
        $this->assertDatabaseCount('ventas', 1);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PlanMembresia;
use App\Models\SolicitudMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembresiasTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo = 'miembro@example.com'): Cliente { return Cliente::create(['nombre' => $correo, 'correo' => $correo, 'password' => 'Password!123', 'activo' => true]); }

    public function test_request_does_not_activate_and_only_secretary_can_charge_once(): void
    {
        $cliente = $this->cliente(); $plan = PlanMembresia::firstOrFail();
        $this->actingAs($cliente, 'cliente')->post(route('membresias.solicitar'), ['plan_id' => $plan->id])->assertSessionHasNoErrors();
        $solicitud = SolicitudMembresia::firstOrFail();
        $plan->update(['precio' => 999]);
        $this->assertSame('25.00', $solicitud->precio_acordado);
        $this->assertSame('pendiente', $solicitud->estado); $this->assertDatabaseCount('membresias', 0); $this->assertDatabaseCount('pagos_membresia', 0);
        $this->post(route('cliente.logout'));
        foreach (['Administrador', 'Entrenador'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]))->patch(route('membresias.activar', $solicitud), ['importe' => 25])->assertForbidden();
            $this->post(route('logout'));
        }
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($secretaria)->allows('operaciones'));
        $this->actingAs($secretaria)->patch(route('membresias.activar', $solicitud), ['importe' => 25, 'referencia' => 'EFECTIVO'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($secretaria)->patch(route('membresias.activar', $solicitud), ['importe' => 25])->assertRedirect();
        $this->assertDatabaseCount('membresias', 1); $this->assertDatabaseCount('pagos_membresia', 1);
        $this->assertDatabaseHas('pagos_membresia', ['registrado_por' => $secretaria->id, 'importe' => 25]);
    }

    public function test_renewal_starts_after_paid_days_and_client_sees_only_own_history(): void
    {
        $cliente = $this->cliente(); $otro = $this->cliente('otro@example.com'); $plan = PlanMembresia::firstOrFail(); $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Anterior', 'importe' => 10, 'inicio' => today(), 'fin' => today()->addDays(10), 'cancelada' => false]);
        $solicitud = SolicitudMembresia::create(['cliente_id' => $cliente->id, 'plan_id' => $plan->id, 'plan_nombre' => $plan->nombre, 'precio_acordado' => $plan->precio, 'duracion_dias' => $plan->duracion_dias, 'condiciones' => 'snapshot']);
        SolicitudMembresia::create(['cliente_id' => $otro->id, 'plan_id' => $plan->id, 'plan_nombre' => 'Privado', 'precio_acordado' => 999, 'duracion_dias' => 30]);
        $this->actingAs($secretaria)->patch(route('membresias.activar', $solicitud), ['importe' => 20])->assertSessionHasNoErrors();
        $nueva = Membresia::where('solicitud_id', $solicitud->id)->firstOrFail();
        $this->assertTrue($nueva->inicio->equalTo(today()->addDays(11)));
        $this->actingAs($cliente, 'cliente')->get(route('membresias.index'))->assertOk()->assertDontSee('Privado')->assertDontSee('999');
    }
}

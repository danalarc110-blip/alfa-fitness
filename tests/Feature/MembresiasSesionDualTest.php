<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\SolicitudMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembresiasSesionDualTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo): Cliente
    {
        return Cliente::create(['nombre' => 'Socio', 'correo' => $correo, 'activo' => true]);
    }

    private function membresia(Cliente $cliente): Membresia
    {
        return Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Mensual',
            'importe' => '25.00',
            'inicio' => today(),
            'fin' => today()->addDays(29),
            'cancelada' => false,
        ]);
    }

    private function sesionDual(Cliente $cliente): User
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria, 'web')->actingAs($cliente, 'cliente');

        return $secretaria;
    }

    public function test_client_cannot_borrow_staff_session_to_pause_or_resume_another_membership(): void
    {
        $cliente = $this->cliente('actual@example.test');
        $otro = $this->cliente('otro@example.test');
        $membresia = $this->membresia($otro);
        $atributos = $membresia->fresh()->getAttributes();
        $this->sesionDual($cliente);

        $this->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 3, 'motivo' => 'Viaje', 'inicio_pausa' => today()->toDateString(),
        ])->assertForbidden();
        $this->assertDatabaseCount('pausas_membresia', 0);

        $pausa = PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $otro->id,
            'dias' => 3,
            'motivo' => 'Viaje',
            'estado' => 'aprobada',
            'inicio_pausa' => today(),
            'fin_pausa_estimada' => today()->addDays(2),
        ]);
        $this->patch(route('membresias.reanudar', $membresia))->assertForbidden();

        $this->assertSame('aprobada', $pausa->fresh()->estado);
        $this->assertSame($atributos, $membresia->fresh()->getAttributes());
    }

    public function test_client_pause_still_requires_approval_when_staff_session_also_exists(): void
    {
        $cliente = $this->cliente('actual@example.test');
        $membresia = $this->membresia($cliente);
        $fin = $membresia->fin->toDateString();
        $this->sesionDual($cliente);

        $this->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 3, 'motivo' => 'Viaje', 'inicio_pausa' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pausas_membresia', [
            'membresia_id' => $membresia->id, 'estado' => 'pendiente', 'aprobada_por' => null,
        ]);
        $this->assertSame($fin, $membresia->fresh()->fin->toDateString());
    }

    public function test_client_cancellation_is_not_attributed_to_an_unselected_staff_session(): void
    {
        $cliente = $this->cliente('actual@example.test');
        $solicitud = SolicitudMembresia::create([
            'cliente_id' => $cliente->id,
            'plan_nombre' => 'Mensual',
            'precio_acordado' => '25.00',
            'duracion_dias' => 30,
            'estado' => 'pendiente',
        ]);
        $this->sesionDual($cliente);

        $this->patch(route('membresias.solicitudes.cancelar', $solicitud))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('solicitudes_membresia', [
            'id' => $solicitud->id, 'estado' => 'cancelada', 'resuelta_por' => null,
        ]);
    }
}

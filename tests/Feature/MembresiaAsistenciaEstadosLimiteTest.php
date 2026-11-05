<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembresiaAsistenciaEstadosLimiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_pause_approval_cannot_credit_days_when_access_was_still_available_and_can_be_retried(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(10));
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Socio de pausa', 'correo' => 'pausa-tardia@example.test', 'activo' => true]);
        $fin = today()->addDays(30)->toDateString();
        $membresia = Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
            'inicio' => today()->subDays(5), 'fin' => $fin, 'cancelada' => false,
        ]);

        $this->actingAs($cliente, 'cliente')->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 5, 'motivo' => 'Viaje', 'inicio_pausa' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $pausa = PausaMembresia::firstOrFail();
        $this->assertSame('pendiente', $pausa->estado);
        $this->assertSame('Vigente', $membresia->fresh()->estado);

        auth('cliente')->logout();
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->travel(3)->days();

        $this->patch(route('membresias.pausa.aprobar', $pausa))->assertRedirect()->assertSessionHasErrors('pausa');
        $this->assertSame($fin, $membresia->fresh()->fin->toDateString());
        $this->assertSame('pendiente', $pausa->fresh()->estado);
        $this->assertSame(0, $membresia->fresh()->diasPausadosAcumulados());

        // Reception can resolve the stale request so the member can choose current dates.
        $this->patch(route('membresias.pausa.rechazar', $pausa))->assertRedirect()->assertSessionHasNoErrors();
        auth('web')->logout();
        $this->actingAs($cliente, 'cliente')->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 3, 'motivo' => 'Nuevas fechas de viaje', 'inicio_pausa' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $nueva = PausaMembresia::latest('id')->firstOrFail();
        auth('cliente')->logout();
        $this->actingAs($staff, 'web')->patch(route('membresias.pausa.aprobar', $nueva))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('aprobada', $nueva->fresh()->estado);
        $this->assertSame($fin, $membresia->fresh()->fin->copy()->subDays(3)->toDateString());
        $this->assertTrue($nueva->fresh()->estaVigenteHoy());
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertRedirect()->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('asistencias', 1);
    }

    public function test_disabling_an_inside_member_allows_the_exit_but_blocks_reentry(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Socio dentro', 'correo' => 'salida-inactivo@example.test', 'activo' => true]);
        Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
            'inicio' => today(), 'fin' => today()->addDays(29), 'cancelada' => false,
        ]);
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertRedirect()->assertSessionHasNoErrors();
        $visita = Asistencia::firstOrFail();
        $cliente->update(['activo' => false]);

        $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($visita->fresh()->fecha_salida);
        $this->assertSame($staff->id, $visita->fresh()->salida_registrada_por);
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertRedirect()->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('asistencias', 1);
    }
}

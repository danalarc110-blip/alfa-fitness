<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_secretary_can_register_entry_and_exit_and_both_operators_are_audited(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $otra = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Cliente Demo', 'correo' => 'cliente@example.com', 'password' => 'Password!123', 'activo' => true]);

        $this->actingAs($secretaria)->get(route('asistencia.index'))->assertOk()->assertSee('Cliente Demo');
        $this->actingAs($secretaria)->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->actingAs($secretaria)->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasErrors('cliente_id');
        $this->actingAs($otra)->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $visita = Asistencia::firstOrFail();
        $this->assertSame($secretaria->id, $visita->registrado_por);
        $this->assertSame($otra->id, $visita->salida_registrada_por);
        $this->actingAs($otra)->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertSessionHasErrors('cliente_id');
        $this->actingAs($otra)->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('asistencias', 2);
    }

    public function test_client_admin_and_trainer_cannot_view_or_mutate_attendance(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'c@example.com', 'password' => 'Password!123', 'activo' => true]);
        foreach ([[$cliente, 'cliente'], [User::factory()->create(['rol' => 'Administrador']), 'web'], [User::factory()->create(['rol' => 'Entrenador']), 'web']] as [$actor, $guard]) {
            $this->actingAs($actor, $guard);
            $this->get(route('asistencia.index'))->assertForbidden();
            $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertForbidden();
            $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertForbidden();
        }
        $this->assertDatabaseCount('asistencias', 0);
    }
}

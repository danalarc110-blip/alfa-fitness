<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PausasMembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_pause_and_secretary_approves_extending_validity(): void
    {
        $secretary = User::create([
            'name' => 'Secretaria Alfa',
            'email' => 'secre@test.com',
            'password' => 'Password!123',
            'rol' => 'Secretaria',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Luis Membresia',
            'correo' => 'luis@test.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $inicio = today();
        $finOriginal = today()->addDays(30);

        $membresia = Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 25.00,
            'inicio' => $inicio,
            'fin' => $finOriginal,
            'cancelada' => false,
        ]);

        // Client requests 10 days pause
        $response = $this->actingAs($cliente, 'cliente')->post(route('membresias.pausa.solicitar', $membresia), [
            'inicio_pausa' => today()->format('Y-m-d'),
            'dias' => 10,
            'motivo' => 'Reposo médico por lesión en tobillo',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pausas_membresia', [
            'membresia_id' => $membresia->id,
            'cliente_id' => $cliente->id,
            'dias' => 10,
            'estado' => 'pendiente',
        ]);

        $pausa = PausaMembresia::where('membresia_id', $membresia->id)->first();

        // Switch to secretary guard
        auth('cliente')->logout();
        $approveResponse = $this->actingAs($secretary, 'web')->patch(route('membresias.pausa.aprobar', $pausa));
        $approveResponse->assertRedirect();

        $pausa->refresh();
        $membresia->refresh();

        $this->assertSame('aprobada', $pausa->estado);
        $this->assertSame($secretary->id, $pausa->aprobada_por);
        // fin date shifted forward by 10 days
        $this->assertEquals($finOriginal->copy()->addDays(10)->toDateString(), $membresia->fin->toDateString());
        $this->assertSame('En Pausa', $membresia->estado);
    }

    public function test_attendance_is_blocked_when_membership_is_in_active_pause(): void
    {
        $secretary = User::create([
            'name' => 'Secretaria Alfa',
            'email' => 'secre@test.com',
            'password' => 'Password!123',
            'rol' => 'Secretaria',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Roberto Pausado',
            'correo' => 'roberto@test.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $membresia = Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Plan Trimestral',
            'importe' => 70.00,
            'inicio' => today()->subDays(5),
            'fin' => today()->addDays(85),
            'cancelada' => false,
        ]);

        // Active approved pause covering today
        PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $cliente->id,
            'inicio_pausa' => today()->subDays(2),
            'fin_pausa_estimada' => today()->addDays(5),
            'dias' => 8,
            'motivo' => 'Viaje de trabajo',
            'estado' => 'aprobada',
            'aprobada_por' => $secretary->id,
        ]);

        // Secretary attempts to register entry
        $response = $this->actingAs($secretary, 'web')->post(route('asistencia.store'), [
            'cliente_id' => $cliente->id,
        ]);

        $response->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseMissing('asistencias', [
            'cliente_id' => $cliente->id,
            'tipo_acceso' => 'entrada',
        ]);
    }

    public function test_max_30_cumulative_days_per_membership_is_strictly_enforced(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Ana Limite',
            'correo' => 'ana@test.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $membresia = Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Plan Anual',
            'importe' => 200.00,
            'inicio' => today(),
            'fin' => today()->addDays(365),
            'cancelada' => false,
        ]);

        // Existing approved pause of 25 days
        PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $cliente->id,
            'inicio_pausa' => today()->subDays(30),
            'fin_pausa_estimada' => today()->subDays(6),
            'dias' => 25,
            'motivo' => 'Reposo previo',
            'estado' => 'aprobada',
        ]);

        // Requesting 10 days should fail because 25 + 10 > 30 max limit
        $response = $this->actingAs($cliente, 'cliente')->post(route('membresias.pausa.solicitar', $membresia), [
            'inicio_pausa' => today()->format('Y-m-d'),
            'dias' => 10,
            'motivo' => 'Nueva pausa',
        ]);

        $response->assertSessionHasErrors('dias');
    }

    public function test_early_resume_adjusts_days_used_and_membership_fin_date(): void
    {
        $secretary = User::create([
            'name' => 'Secretaria Alfa',
            'email' => 'secre@test.com',
            'password' => 'Password!123',
            'rol' => 'Secretaria',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Marcos Reanuda',
            'correo' => 'marcos@test.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $inicio = today()->subDays(10);
        $finBase = today()->addDays(20);

        $membresia = Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30.00,
            'inicio' => $inicio,
            'fin' => $finBase->copy()->addDays(14), // already shifted by 14 days when paused
            'cancelada' => false,
        ]);

        // Pause was approved 4 days ago for 14 days total
        $pausa = PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $cliente->id,
            'inicio_pausa' => today()->subDays(4),
            'fin_pausa_estimada' => today()->addDays(9),
            'dias' => 14,
            'motivo' => 'Cirugía menor',
            'estado' => 'aprobada',
            'aprobada_por' => $secretary->id,
        ]);

        // Client or secretary decides to resume today (after 4 days used, 10 days unused)
        $response = $this->actingAs($secretary, 'web')->patch(route('membresias.reanudar', $membresia));
        $response->assertRedirect();

        $pausa->refresh();
        $membresia->refresh();

        // Pausa adjusted
        $this->assertEquals(today()->toDateString(), $pausa->fin_pausa_estimada->toDateString());
        $this->assertEquals(today()->toDateString(), $pausa->fecha_reanudacion->toDateString());
        // 4 days diff + 1 = 5 effective days
        $this->assertSame(5, $pausa->dias);

        // Membership fin date shifted back: 14 - 5 = 9 days returned
        $this->assertEquals($finBase->copy()->addDays(5)->toDateString(), $membresia->fin->toDateString());
    }
}

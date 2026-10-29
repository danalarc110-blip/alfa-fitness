<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PausasPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function membresia(): Membresia
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente de pausas',
            'correo' => 'pausas@example.test',
            'activo' => true,
        ]);

        return Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Mensual',
            'importe' => 25,
            'inicio' => today(),
            'fin' => today()->addDays(29),
            'cancelada' => false,
        ]);
    }

    public function test_staff_without_operations_permission_cannot_create_an_approved_pause(): void
    {
        $membresia = $this->membresia();
        $finOriginal = $membresia->fin->toDateString();

        foreach (['Administrador', 'Entrenador'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]), 'web')
                ->post(route('membresias.pausa.solicitar', $membresia), [
                    'inicio_pausa' => today()->toDateString(),
                    'dias' => 5,
                    'motivo' => 'Viaje',
                ])->assertForbidden();
        }

        $this->assertDatabaseCount('pausas_membresia', 0);
        $this->assertSame($finOriginal, $membresia->fresh()->fin->toDateString());
    }

    public function test_staff_without_operations_permission_cannot_resume_a_pause(): void
    {
        $membresia = $this->membresia();
        $finOriginal = $membresia->fin->toDateString();
        $pausa = PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $membresia->cliente_id,
            'inicio_pausa' => today(),
            'fin_pausa_estimada' => today()->addDays(4),
            'dias' => 5,
            'motivo' => 'Viaje',
            'estado' => 'aprobada',
        ]);

        foreach (['Administrador', 'Entrenador'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]), 'web')
                ->patch(route('membresias.reanudar', $membresia))->assertForbidden();
        }

        $this->assertSame('aprobada', $pausa->fresh()->estado);
        $this->assertSame($finOriginal, $membresia->fresh()->fin->toDateString());
    }
}

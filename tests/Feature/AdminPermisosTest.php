<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_membresias_and_asistencia(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $this->actingAs($admin)
            ->get(route('membresias.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('asistencia.index'))
            ->assertOk();
    }

    public function test_administrator_can_register_attendance_entry_and_exit(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = Cliente::create([
            'nombre' => 'Carlos Miembro',
            'correo' => 'carlos@prueba.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        // Admin registers entry
        $this->actingAs($admin)
            ->post(route('asistencia.store'), ['cliente_id' => $cliente->id])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('asistencias', [
            'cliente_id' => $cliente->id,
            'registrado_por' => $admin->id,
            'fecha_salida' => null,
        ]);

        // Admin registers exit
        $this->actingAs($admin)
            ->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])
            ->assertRedirect()
            ->assertSessionHas('status');

        $asistencia = Asistencia::where('cliente_id', $cliente->id)->first();
        $this->assertNotNull($asistencia->fecha_salida);
        $this->assertSame($admin->id, $asistencia->salida_registrada_por);
    }

    public function test_trainer_cannot_access_asistencia_or_membresias(): void
    {
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);

        $this->actingAs($entrenador)
            ->get(route('asistencia.index'))
            ->assertForbidden();

        $this->actingAs($entrenador)
            ->get(route('membresias.index'))
            ->assertForbidden();
    }
}

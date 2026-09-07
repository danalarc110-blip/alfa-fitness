<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_register_client_entry_and_exit(): void
    {
        $empleado = User::create([
            'name' => 'Secretaria Demo',
            'email' => 'secretaria@example.com',
            'password' => 'password',
            'rol' => 'Recepcionista',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Cliente Demo',
            'correo' => 'cliente@example.com',
            'password' => 'password',
            'activo' => true,
        ]);

        $this->actingAs($empleado)
            ->get(route('asistencia.index'))
            ->assertOk()
            ->assertSee('Asistencia')
            ->assertSee('Cliente Demo');

        $this->actingAs($empleado)
            ->post(route('asistencia.store'), [
                'cliente_id' => $cliente->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('asistencias', [
            'cliente_id' => $cliente->id,
            'registrado_por' => $empleado->id,
            'tipo_acceso' => 'entrada',
            'fecha_salida' => null,
        ]);

        $this->actingAs($empleado)
            ->post(route('asistencia.salida'), [
                'cliente_id' => $cliente->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('asistencias', [
            'cliente_id' => $cliente->id,
            'fecha_salida' => null,
        ]);
    }

    public function test_client_can_view_attendance_module_during_development(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Demo',
            'correo' => 'cliente@example.com',
            'password' => 'password',
            'activo' => true,
        ]);

        $this->actingAs($cliente, 'cliente')
            ->get(route('asistencia.index'))
            ->assertOk()
            ->assertSee('Asistencia');
    }

    public function test_non_secretary_employee_can_view_attendance_module_during_development(): void
    {
        $empleado = User::create([
            'name' => 'Entrenador Demo',
            'email' => 'entrenador@example.com',
            'password' => 'password',
            'rol' => 'Entrenador',
            'activo' => true,
        ]);

        $this->actingAs($empleado)
            ->get(route('asistencia.index'))
            ->assertOk()
            ->assertSee('Asistencia');
    }
}

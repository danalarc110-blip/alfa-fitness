<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_view_own_attendance_history(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Mateo Cliente',
            'correo' => 'mateo@cliente.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $admin = User::factory()->create(['rol' => 'Administrador']);

        Asistencia::create([
            'cliente_id' => $cliente->id,
            'registrado_por' => $admin->id,
            'fecha_hora' => now()->subHours(2),
            'fecha_salida' => now()->subHour(),
        ]);

        $response = $this->actingAs($cliente, 'cliente')
            ->get(route('cliente.asistencia'));

        $response->assertOk()
            ->assertSee('Mi Historial de Asistencia')
            ->assertSee('Mateo Cliente')
            ->assertSee('1h 0m');
    }

    public function test_client_cannot_see_other_clients_attendance(): void
    {
        $cliente1 = Cliente::create([
            'nombre' => 'Cliente Uno',
            'correo' => 'uno@cliente.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $cliente2 = Cliente::create([
            'nombre' => 'Cliente Dos',
            'correo' => 'dos@cliente.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $admin = User::factory()->create(['rol' => 'Administrador']);

        Asistencia::create([
            'cliente_id' => $cliente2->id,
            'registrado_por' => $admin->id,
            'fecha_hora' => now()->subHours(3),
            'fecha_salida' => now()->subHours(2),
        ]);

        $response = $this->actingAs($cliente1, 'cliente')
            ->get(route('cliente.asistencia', ['cliente_id' => $cliente2->id]));

        $response->assertOk()
            ->assertDontSee('Cliente Dos')
            ->assertSee('Aún no tienes registros de asistencia');
    }

    public function test_client_cannot_access_administrative_attendance(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Tres',
            'correo' => 'tres@cliente.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $this->actingAs($cliente, 'cliente')
            ->get(route('asistencia.index'))
            ->assertForbidden();
    }
}

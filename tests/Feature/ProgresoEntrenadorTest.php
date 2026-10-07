<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgresoEntrenadorTest extends TestCase
{
    use RefreshDatabase;

    private function crearCliente(array $attrs = []): Cliente
    {
        static $counter = 1;
        $counter++;

        return Cliente::create(array_merge([
            'nombre' => "Cliente {$counter}",
            'correo' => "cliente{$counter}@fitness.test",
            'password' => 'Password!123',
            'activo' => true,
        ], $attrs));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertRedirect(route('login'));
    }

    public function test_cliente_cannot_access_coach_progress_route(): void
    {
        $cliente = $this->crearCliente();
        $otroCliente = $this->crearCliente();

        $response = $this->actingAs($cliente, 'cliente')
            ->get(route('entrenador.cliente.progreso', $otroCliente));

        $response->assertForbidden();
    }

    public function test_administrador_can_view_any_client_progress(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->crearCliente(['nombre' => 'Pedro Atleta']);
        $ejercicio = Ejercicio::create([
            'nombre' => 'Sentadilla Profunda',
            'grupo_muscular' => 'Piernas',
            'activo' => true,
        ]);
        PersonalRecord::create([
            'cliente_id' => $cliente->id,
            'ejercicio_id' => $ejercicio->id,
            'peso_kg' => 120,
            'repeticiones' => 5,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertOk();
        $response->assertSee('Progreso: Pedro Atleta');
        $response->assertSee('Sentadilla Profunda');
        $response->assertSee('120');
    }

    public function test_coach_can_view_assigned_client_progress(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Coach Carlos']);
        $cliente = $this->crearCliente(['nombre' => 'Laura Alumna']);

        // Assign a routine
        Rutina::create([
            'user_id' => $cliente->id,
            'user_type' => 'cliente',
            'nombre' => 'Fuerza 5x5',
            'objetivo' => 'Ganar fuerza',
            'nivel' => 'Intermedio',
            'asignado_por' => 'Coach Carlos',
            'asignado_por_id' => $coach->id,
            'dias_por_semana' => 3,
        ]);

        $response = $this->actingAs($coach, 'web')
            ->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertOk();
        $response->assertSee('Progreso: Laura Alumna');
    }

    public function test_coach_is_forbidden_if_not_assigned_to_client(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Coach Carlos']);
        $cliente = $this->crearCliente();

        $response = $this->actingAs($coach, 'web')
            ->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rutina;
use App\Models\SesionEntrenamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesionEntrenamientoTest extends TestCase
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

    public function test_guest_is_redirected_to_login_on_historial(): void
    {
        $response = $this->get(route('entrenamientos.historial'));

        $response->assertRedirect(route('login'));
    }

    public function test_client_can_finalize_workout_session_and_persist_it(): void
    {
        $cliente = $this->crearCliente();
        $rutina = Rutina::create([
            'user_id' => $cliente->id,
            'user_type' => 'cliente',
            'nombre' => 'Fuerza Total',
            'objetivo' => 'Ganar fuerza',
            'nivel' => 'Intermedio',
            'dias_por_semana' => 3,
        ]);
        $dia = $rutina->dias()->create([
            'orden' => 1,
            'titulo' => 'Piernas y Espalda',
            'duracion_estimada_min' => 45,
            'duracion_estimada_max' => 60,
        ]);

        $response = $this->actingAs($cliente, 'cliente')
            ->postJson(route('entrenamientos.finalizar', $rutina), [
                'dia_id' => $dia->id,
                'duracion_segundos' => 2400,
                'series_completadas' => 12,
                'total_series' => 12,
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('sesion.rutina_nombre', 'Fuerza Total')
            ->assertJsonPath('sesion.dia_titulo', 'Piernas y Espalda');

        $this->assertDatabaseHas('sesiones_entrenamiento', [
            'user_id' => $cliente->id,
            'user_type' => 'cliente',
            'rutina_id' => $rutina->id,
            'rutina_nombre' => 'Fuerza Total',
            'duracion_segundos' => 2400,
            'series_completadas' => 12,
            'total_series' => 12,
            'estado' => 'completado',
        ]);
    }

    public function test_client_can_view_own_workout_history(): void
    {
        $cliente = $this->crearCliente(['nombre' => 'Carlos Crossfit']);
        $rutina = Rutina::create([
            'user_id' => $cliente->id,
            'user_type' => 'cliente',
            'nombre' => 'WOD Diario',
            'objetivo' => 'Resistencia',
            'nivel' => 'Avanzado',
            'dias_por_semana' => 4,
        ]);

        SesionEntrenamiento::create([
            'user_id' => $cliente->id,
            'user_type' => 'cliente',
            'rutina_id' => $rutina->id,
            'rutina_nombre' => 'WOD Diario',
            'dia_titulo' => 'Día 1: Metabólico',
            'iniciado_en' => now()->subMinutes(45),
            'finalizado_en' => now(),
            'duracion_segundos' => 2700,
            'series_completadas' => 15,
            'total_series' => 15,
            'estado' => 'completado',
        ]);

        $response = $this->actingAs($cliente, 'cliente')
            ->get(route('entrenamientos.historial'));

        $response->assertOk()
            ->assertSee('Historial de Entrenamientos')
            ->assertSee('WOD Diario')
            ->assertSee('Día 1: Metabólico')
            ->assertSee('45 min');
    }

    public function test_user_cannot_finalize_another_users_routine(): void
    {
        $cliente1 = $this->crearCliente();
        $cliente2 = $this->crearCliente();

        $rutina = Rutina::create([
            'user_id' => $cliente1->id,
            'user_type' => 'cliente',
            'nombre' => 'Privada',
            'objetivo' => 'Fuerza',
            'nivel' => 'Intermedio',
            'dias_por_semana' => 2,
        ]);

        $response = $this->actingAs($cliente2, 'cliente')
            ->postJson(route('entrenamientos.finalizar', $rutina), [
                'duracion_segundos' => 300,
                'series_completadas' => 2,
                'total_series' => 4,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseEmpty('sesiones_entrenamiento');
    }
}

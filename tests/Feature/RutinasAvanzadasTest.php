<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinasAvanzadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_assign_routine_to_active_client(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $client = Cliente::create(['nombre' => 'María Atleta', 'correo' => 'maria@example.test', 'activo' => true]);

        // Coach creates routine
        $rutina = Rutina::create([
            'user_id' => $coach->id,
            'user_type' => 'web',
            'nombre' => 'Rutina Hipertrofia 3 Días',
            'objetivo' => 'Ganar masa',
            'nivel' => 'Avanzado',
            'dias_por_semana' => 3,
        ]);
        $dia = $rutina->dias()->create(['orden' => 1, 'titulo' => 'Día 1: Pecho']);
        $ej = Ejercicio::create(['nombre' => 'Press Banca', 'grupo_muscular' => 'Pecho', 'activo' => true]);
        $dia->ejercicios()->create([
            'ejercicio_id' => $ej->id,
            'orden' => 1,
            'series' => 4,
            'repeticiones' => '10',
            'peso' => 60,
            'descanso_segundos' => 90,
        ]);

        // Coach assigns routine to client
        $response = $this->actingAs($coach)->post(route('entrenamientos.asignar', $rutina), [
            'cliente_id' => $client->id,
        ]);
        $response->assertRedirect()->assertSessionHas('status');

        // Routine cloned for client
        $rutinaCliente = Rutina::where('user_type', 'cliente')->where('user_id', $client->id)->first();
        $this->assertNotNull($rutinaCliente);
        $this->assertSame('Rutina Hipertrofia 3 Días', $rutinaCliente->nombre);
        $this->assertSame($coach->name, $rutinaCliente->asignado_por);
        $this->assertSame(1, $rutinaCliente->dias()->count());
        $this->assertSame(1, $rutinaCliente->dias()->first()->ejercicios()->count());

        // Client can view and train their routine
        $this->actingAs($client, 'cliente')->get(route('entrenamientos.entrenar', $rutinaCliente))->assertOk();

        // Client can access printable view
        $this->actingAs($client, 'cliente')->get(route('entrenamientos.imprimir', $rutinaCliente))->assertOk();
    }
}

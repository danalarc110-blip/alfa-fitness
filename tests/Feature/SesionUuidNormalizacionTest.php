<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rutina;
use App\Models\SesionEntrenamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesionUuidNormalizacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_routine_client_picker_excludes_unauthorized_staff_and_email(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'privado@example.test', 'activo' => true]);
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $this->actingAs($secretaria)->get(route('entrenamientos.index'))->assertOk()->assertViewHas('clientes', fn ($clientes) => $clientes->isEmpty())->assertDontSee($cliente->correo);
        $this->actingAs($coach)->get(route('entrenamientos.index'))->assertOk()->assertViewHas('clientes', fn ($clientes) => $clientes->contains('id', $cliente->id) && ! array_key_exists('correo', $clientes->first()->getAttributes()))->assertDontSee($cliente->correo);
    }

    public function test_uuid_case_does_not_duplicate_same_workout(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'socio@example.test', 'activo' => true]);
        $rutina = Rutina::create(['user_id' => $cliente->id, 'user_type' => 'cliente', 'nombre' => 'Fuerza', 'objetivo' => 'Salud', 'nivel' => 'Intermedio', 'dias_por_semana' => 1]);
        $uuid = 'a1b2c3d4-1111-4111-a111-a1b2c3d4e5f6';
        $this->actingAs($cliente, 'cliente')->postJson(route('entrenamientos.finalizar', $rutina), ['sesion_uuid' => strtoupper($uuid)])->assertOk();
        $id = SesionEntrenamiento::firstOrFail()->id;
        $this->postJson(route('entrenamientos.finalizar', $rutina), ['sesion_uuid' => $uuid])->assertOk()->assertJsonPath('sesion.id', $id);
        $this->assertDatabaseCount('sesiones_entrenamiento', 1);
        $this->assertDatabaseHas('sesiones_entrenamiento', ['id' => $id, 'sesion_uuid' => $uuid]);
    }
}

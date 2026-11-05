<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RutinasCasosLimiteTest extends TestCase
{
    use RefreshDatabase;

    private function rutina(User|Cliente $user): Rutina
    {
        $rutina = Rutina::create([
            'user_id' => $user->id, 'user_type' => $user instanceof Cliente ? 'cliente' : 'web',
            'nombre' => 'Rutina de prueba', 'objetivo' => 'Fuerza', 'nivel' => 'Intermedio', 'dias_por_semana' => 1,
        ]);
        $dia = $rutina->dias()->create(['orden' => 1, 'titulo' => 'Día propio']);
        $ejercicio = Ejercicio::create(['nombre' => 'Ejercicio de prueba', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $dia->ejercicios()->create(['ejercicio_id' => $ejercicio->id, 'orden' => 1, 'series' => 3,
            'repeticiones' => '10', 'descanso_segundos' => 0]);

        return $rutina;
    }

    public function test_training_rejects_a_day_from_another_routine_instead_of_silently_switching_days(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'nested-rutina@example.test', 'activo' => true]);
        $rutina = $this->rutina($cliente);
        $otra = $this->rutina($cliente);
        $ajeno = Cliente::create(['nombre' => 'Ajeno', 'correo' => 'nested-ajeno@example.test', 'activo' => true]);
        $rutinaAjena = $this->rutina($ajeno);
        $this->actingAs($cliente, 'cliente');

        foreach ([$otra, $rutinaAjena] as $incompatible) {
            $this->get(route('entrenamientos.entrenar', ['rutina' => $rutina, 'dia' => $incompatible->dias()->firstOrFail()]))->assertNotFound();
        }
        $diaPropio = $rutina->dias()->firstOrFail();
        $this->get(route('entrenamientos.entrenar', $rutina))->assertOk()
            ->assertViewHas('diaSeleccionado', fn ($dia) => $dia->id === $diaPropio->id);
        $this->get(route('entrenamientos.entrenar', ['rutina' => $rutina, 'dia' => $diaPropio]))->assertOk()
            ->assertViewHas('diaSeleccionado', fn ($dia) => $dia->id === $diaPropio->id);
    }

    public static function personal(): array
    {
        return [['Administrador'], ['Secretaria'], ['Entrenador']];
    }

    #[DataProvider('personal')]
    public function test_staff_training_summary_does_not_offer_a_client_only_personal_record_action(string $rol): void
    {
        $user = User::factory()->create(['rol' => $rol]);
        $rutina = $this->rutina($user);
        $this->actingAs($user, 'web')->get(route('entrenamientos.entrenar', $rutina))->assertOk()
            ->assertDontSee('Registrar PRs')->assertDontSee('href="'.route('progreso.index').'"', false);
    }

    public function test_client_keeps_personal_record_action_and_zero_rest_is_rendered_as_zero(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'cero-descanso@example.test', 'activo' => true]);
        $rutina = $this->rutina($cliente);
        $this->actingAs($cliente, 'cliente')->get(route('entrenamientos.entrenar', $rutina))->assertOk()
            ->assertSee('Registrar PRs')->assertSee('onclick="toggleSerie(this, 0)"', false)
            ->assertSee('Descanso:</strong> 0s', false);
    }

    public function test_saved_session_retry_survives_removal_of_its_original_day_without_duplicate_history(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'retry-dia@example.test', 'activo' => true]);
        $rutina = $this->rutina($cliente);
        $dia = $rutina->dias()->firstOrFail();
        $payload = ['sesion_uuid' => (string) Str::uuid(), 'dia_id' => $dia->id,
            'duracion_segundos' => 300, 'series_completadas' => 1, 'total_series' => 3];
        $response = $this->actingAs($cliente, 'cliente')->postJson(route('entrenamientos.finalizar', $rutina), $payload)->assertOk();
        $id = $response->json('sesion.id');
        $this->deleteJson(route('entrenamientos.dias.eliminar', $dia))->assertOk();
        $payload['series_completadas'] = 2;
        $this->postJson(route('entrenamientos.finalizar', $rutina), $payload)->assertOk()
            ->assertJsonPath('sesion.id', $id)->assertJsonPath('sesion.series_completadas', 1)
            ->assertJsonPath('sesion.dia_titulo', 'Día propio');
        $this->assertDatabaseCount('sesiones_entrenamiento', 1);
        $this->get(route('entrenamientos.historial'))->assertOk()->assertSee('Día propio');
    }

    public function test_dual_session_cannot_use_staff_ownership_to_read_write_print_or_finish_routines(): void
    {
        $staff = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'dual-rutina@example.test', 'activo' => true]);
        $delStaff = $this->rutina($staff);
        $delCliente = $this->rutina($cliente);
        $diaDelStaff = $delStaff->dias()->firstOrFail();
        $this->actingAs($staff, 'web')->actingAs($cliente, 'cliente');

        foreach (['entrenamientos.editar', 'entrenamientos.entrenar', 'entrenamientos.imprimir'] as $ruta) {
            $this->get(route($ruta, $delStaff))->assertForbidden();
            $this->get(route($ruta, $delCliente))->assertOk();
        }
        $this->putJson(route('entrenamientos.dias.renombrar', $diaDelStaff), ['titulo' => 'Inyectado'])->assertForbidden();
        $this->postJson(route('entrenamientos.finalizar', $delStaff), ['sesion_uuid' => (string) Str::uuid()])->assertForbidden();
        $this->assertSame('Día propio', $diaDelStaff->fresh()->titulo);
        $this->assertDatabaseCount('sesiones_entrenamiento', 0);
    }

    public function test_exercise_reordering_rejects_named_or_sparse_keys_without_changing_the_order(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'orden-lista@example.test', 'activo' => true]);
        $rutina = $this->rutina($cliente);
        $dia = $rutina->dias()->firstOrFail();
        $ejercicio = $dia->ejercicios()->firstOrFail();
        $this->actingAs($cliente, 'cliente');
        foreach ([['primero' => $ejercicio->id], [7 => $ejercicio->id]] as $ordenInvalido) {
            $this->putJson(route('entrenamientos.ejercicios.reordenar', $dia), ['orden' => $ordenInvalido])
                ->assertUnprocessable()->assertJsonValidationErrors('orden');
            $this->assertSame(1, $ejercicio->fresh()->orden);
        }
        $this->putJson(route('entrenamientos.ejercicios.reordenar', $dia), ['orden' => [$ejercicio->id]])->assertOk();
    }
}

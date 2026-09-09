<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinasTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_creates_updates_and_deletes_only_owned_resources(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->post(route('entrenamientos.crear'))->assertRedirect();
        $rutina = Rutina::firstOrFail();
        $dia = $rutina->dias()->firstOrFail();
        $this->get(route('entrenamientos.editar', $rutina))->assertOk();
        $ejercicio = Ejercicio::create(['nombre' => 'Sentadilla', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $this->postJson(route('entrenamientos.ejercicios.crear', $dia), ['ejercicio_id' => $ejercicio->id])->assertOk();
        $re = $dia->ejercicios()->firstOrFail();
        $this->putJson(route('entrenamientos.ejercicios.actualizar', $re), ['repeticiones' => '8-12'])->assertOk();
        $this->actingAs($other)->get(route('entrenamientos.editar', $rutina))->assertForbidden();
        $this->putJson(route('entrenamientos.ejercicios.actualizar', $re), ['series' => 1])->assertForbidden();
        $this->deleteJson(route('entrenamientos.dias.eliminar', $dia))->assertForbidden();
        $this->actingAs($owner)->putJson(route('entrenamientos.ejercicios.reordenar', $dia), ['orden' => [$re->id, $re->id]])->assertUnprocessable();
        $this->putJson(route('entrenamientos.ejercicios.reordenar', $dia), ['orden' => [$re->id]])->assertOk();
        $this->delete(route('entrenamientos.eliminar', $rutina))->assertRedirect();
        $this->assertDatabaseMissing('rutina_ejercicios', ['id' => $re->id]);
    }

    public function test_unrated_exercise_has_no_fabricated_score(): void
    {
        $ejercicio = Ejercicio::create(['nombre' => 'Ejercicio sin imagen', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $this->assertSame(0.0, $ejercicio->calificacion_promedio);
        $this->actingAs(User::factory()->create())->get('/ejercicios')->assertOk()->assertSee('Sin votos')->assertSee('Imagen no disponible');
    }
}

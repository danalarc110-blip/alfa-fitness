<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgresoHistorialInactivoTest extends TestCase
{
    use RefreshDatabase;

    private function historial(): array
    {
        $cliente = Cliente::create(['nombre' => 'Socio con historial', 'correo' => 'historial.retirado@example.test', 'activo' => true]);
        $otro = Cliente::create(['nombre' => 'Socio ajeno', 'correo' => 'historial.ajeno@example.test', 'activo' => true]);
        $retirado = Ejercicio::create(['nombre' => 'Press retirado con historial', 'grupo_muscular' => 'Pecho', 'activo' => false]);
        $ajeno = Ejercicio::create(['nombre' => 'Ejercicio retirado ajeno', 'grupo_muscular' => 'Piernas', 'activo' => false]);
        $activo = Ejercicio::create(['nombre' => 'Remo activo', 'grupo_muscular' => 'Espalda', 'activo' => true]);
        PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $retirado->id, 'peso_kg' => 60, 'repeticiones' => 5]);
        PersonalRecord::create(['cliente_id' => $otro->id, 'ejercicio_id' => $ajeno->id, 'peso_kg' => 90, 'repeticiones' => 3]);

        return [$cliente, $retirado, $activo, $ajeno, $otro];
    }

    public function test_client_can_filter_retired_exercises_from_own_history_without_registering_new_records_in_them(): void
    {
        [$cliente, $retirado, $activo, $ajeno] = $this->historial();
        $this->actingAs($cliente, 'cliente')->get(route('progreso.index', ['ejercicio_id' => $retirado->id]))->assertOk()
            ->assertViewHas('ejercicioSeleccionadoObj', fn ($ejercicio) => $ejercicio?->id === $retirado->id);
        $this->get(route('progreso.index'))->assertOk()
            ->assertViewHas('ejerciciosHistorial', fn ($ejercicios) => $ejercicios->contains('id', $retirado->id)
                && $ejercicios->contains('id', $activo->id) && ! $ejercicios->contains('id', $ajeno->id))
            ->assertViewHas('ejercicios', fn ($ejercicios) => $ejercicios->pluck('id')->all() === [$activo->id]);
        $this->get(route('progreso.index', ['ejercicio_id' => $retirado->id]))->assertOk()
            ->assertViewHas('ejercicioSeleccionadoObj', fn ($ejercicio) => $ejercicio?->id === $retirado->id)
            ->assertViewHas('records', fn ($records) => $records->total() === 1 && $records->first()->ejercicio_id === $retirado->id)
            ->assertSee('Press retirado con historial (inactivo)');
        $this->postJson(route('progreso.store'), ['ejercicio_id' => $retirado->id, 'peso_kg' => 70, 'repeticiones' => 5])
            ->assertUnprocessable()->assertJsonValidationErrors('ejercicio_id');
        $this->assertDatabaseCount('personal_records', 2);
    }

    public function test_assigned_trainer_can_consult_retired_history_and_cannot_borrow_another_clients_history(): void
    {
        [$cliente, $retirado, $activo, $ajeno, $otro] = $this->historial();
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        Rutina::create(['user_type' => 'cliente', 'user_id' => $cliente->id, 'asignado_por_id' => $coach->id,
            'nombre' => 'Rutina de seguimiento', 'objetivo' => 'Fuerza', 'nivel' => 'Principiante', 'dias_por_semana' => 2]);

        $this->actingAs($coach)->get(route('entrenador.cliente.progreso', [$cliente, 'ejercicio_id' => $retirado->id]))->assertOk()
            ->assertViewHas('ejercicioSeleccionadoObj', fn ($ejercicio) => $ejercicio?->id === $retirado->id);
        $this->get(route('entrenador.cliente.progreso', $cliente))->assertOk()
            ->assertViewHas('ejerciciosHistorial', fn ($ejercicios) => $ejercicios->contains('id', $retirado->id)
                && $ejercicios->contains('id', $activo->id) && ! $ejercicios->contains('id', $ajeno->id));
        $this->get(route('entrenador.cliente.progreso', [$cliente, 'ejercicio_id' => $retirado->id]))->assertOk()
            ->assertViewHas('ejercicioSeleccionadoObj', fn ($ejercicio) => $ejercicio?->id === $retirado->id)
            ->assertSee('Press retirado con historial (inactivo)');
        $this->getJson(route('entrenador.cliente.progreso', [$otro, 'ejercicio_id' => ['unexpected']]))->assertForbidden();
        $this->getJson(route('entrenador.cliente.progreso', [$cliente, 'ejercicio_id' => ['unexpected']]))
            ->assertUnprocessable()->assertJsonValidationErrors('ejercicio_id');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\Rutina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisionEntrenamientoDiarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_distant_renewal_does_not_hide_expiring_membership_with_a_gap(): void
    {
        $this->travelTo(today()->setTime(10, 0));
        $cliente = $this->cliente();
        $actual = Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Actual', 'importe' => '25.00',
            'inicio' => today()->subDays(20), 'fin' => today()->addDays(2), 'cancelada' => false,
        ]);
        $renovacion = Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Futura', 'importe' => '25.00',
            'inicio' => today()->addDays(10), 'fin' => today()->addDays(39), 'cancelada' => false,
        ]);

        $this->actingAs($cliente, 'cliente')->get(route('cliente.dashboard'))
            ->assertOk()->assertViewHas('porVencer', fn ($items) => $items->contains('id', $actual->id));

        $renovacion->update(['inicio' => $actual->fin->copy()->addDay()]);
        $this->get(route('cliente.dashboard'))->assertOk()
            ->assertViewHas('porVencer', fn ($items) => $items->isEmpty());
    }

    public function test_reordering_rejects_object_keys_without_changing_exercise_order(): void
    {
        $cliente = $this->cliente();
        $rutina = Rutina::create([
            'user_id' => $cliente->id, 'user_type' => 'cliente', 'nombre' => 'Diaria',
            'objetivo' => 'Fuerza', 'nivel' => 'Intermedio', 'dias_por_semana' => 2,
        ]);
        $dia = $rutina->dias()->create(['orden' => 1, 'titulo' => 'Día 1']);
        $ejercicio = Ejercicio::create(['nombre' => 'Remo diario', 'grupo_muscular' => 'Espalda', 'activo' => true]);
        $primero = $dia->ejercicios()->create(['ejercicio_id' => $ejercicio->id, 'orden' => 1, 'series' => 3, 'repeticiones' => '8']);
        $segundo = $dia->ejercicios()->create(['ejercicio_id' => $ejercicio->id, 'orden' => 2, 'series' => 3, 'repeticiones' => '8']);
        $this->actingAs($cliente, 'cliente');

        foreach ([['primero' => $segundo->id, 'segundo' => $primero->id], [5 => $segundo->id, 9 => $primero->id]] as $orden) {
            $this->putJson(route('entrenamientos.ejercicios.reordenar', $dia), ['orden' => $orden])
                ->assertUnprocessable()->assertJsonValidationErrors('orden');
            $this->assertSame(1, (int) $primero->fresh()->orden);
            $this->assertSame(2, (int) $segundo->fresh()->orden);
        }

        $this->putJson(route('entrenamientos.ejercicios.reordenar', $dia), ['orden' => [$segundo->id, $primero->id]])
            ->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(2, (int) $primero->fresh()->orden);
        $this->assertSame(1, (int) $segundo->fresh()->orden);
    }

    private function cliente(): Cliente
    {
        return Cliente::create([
            'nombre' => 'Atleta de prueba', 'correo' => 'jornada@example.test',
            'password' => 'Password!123', 'activo' => true,
        ]);
    }
}

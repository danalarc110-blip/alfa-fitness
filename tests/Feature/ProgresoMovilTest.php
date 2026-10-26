<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\User;
use App\Support\RangoAtleta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProgresoMovilTest extends TestCase
{
    use RefreshDatabase;

    public function test_rank_boundaries_are_explicit_and_have_distinct_local_images(): void
    {
        foreach ([0 => 'Iniciado', 2 => 'Iniciado', 3 => 'Intermedio', 7 => 'Intermedio', 8 => 'Avanzado', 14 => 'Avanzado', 15 => 'Élite', 30 => 'Élite'] as $total => $nombre) {
            $rango = RangoAtleta::paraRegistros($total);
            $this->assertSame($nombre, $rango['nombre']);
            $this->assertGreaterThanOrEqual(0, $rango['porcentaje']);
            $this->assertLessThanOrEqual(100, $rango['porcentaje']);
            $this->assertFileExists(public_path('images/rangos/'.$rango['imagen']));
        }
        $this->assertCount(4, array_unique(array_column(RangoAtleta::RANGOS, 'imagen')));
    }

    public function test_filter_keeps_global_rank_and_chart_never_mixes_exercises(): void
    {
        $cliente = Cliente::create(['nombre' => 'Atleta', 'correo' => 'atleta@example.test', 'activo' => true]);
        $press = Ejercicio::create(['nombre' => 'Press de prueba', 'grupo_muscular' => 'Pecho', 'activo' => true]);
        $piernas = Ejercicio::create(['nombre' => 'Sentadilla de prueba', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        for ($i = 0; $i < 8; $i++) {
            PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $i % 2 ? $press->id : $piernas->id, 'peso_kg' => 10 + $i, 'repeticiones' => 5]);
        }
        $this->actingAs($cliente, 'cliente');
        $todos = $this->get(route('progreso.index'))->assertOk();
        $filtrado = $this->get(route('progreso.index', ['ejercicio_id' => $piernas->id]))->assertOk();
        $this->assertSame('Avanzado', $todos->viewData('rango')['nombre']);
        $this->assertSame($todos->viewData('rango'), $filtrado->viewData('rango'));
        $this->assertSame([$press->id], $todos->viewData('ultimosLevantamientos')->pluck('ejercicio_id')->unique()->all());
        $this->assertSame([$piernas->id], $filtrado->viewData('ultimosLevantamientos')->pluck('ejercicio_id')->unique()->all());
        $this->assertSame([10.0, 12.0, 14.0, 16.0], $filtrado->viewData('ultimosLevantamientos')->map(fn ($r) => (float) $r->peso_kg)->all());
    }

    public function test_archived_exercise_history_remains_filterable_but_cannot_receive_new_records(): void
    {
        $cliente = Cliente::create(['nombre' => 'Histórico', 'correo' => 'historico@example.test', 'activo' => true]);
        $ejercicio = Ejercicio::create(['nombre' => 'Ejercicio archivado', 'grupo_muscular' => 'Pecho', 'activo' => false]);
        PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 20, 'repeticiones' => 5]);
        $response = $this->actingAs($cliente, 'cliente')->get(route('progreso.index', ['ejercicio_id' => $ejercicio->id]))
            ->assertOk()->assertSee('Ejercicio archivado (Archivado)')->assertSee('20 kg');
        $this->assertTrue($response->viewData('ejerciciosRegistro')->isEmpty());
        $this->post(route('progreso.store'), ['ejercicio_id' => $ejercicio->id, 'peso_kg' => 30, 'repeticiones' => 5])->assertSessionHasErrors('ejercicio_id');
        $this->assertDatabaseCount('personal_records', 1);
    }

    public function test_mobile_shortcuts_follow_permissions_and_admin_cannot_take_reception_payments(): void
    {
        foreach (['Administrador', 'Secretaria', 'Entrenador'] as $rol) {
            $user = User::factory()->create(['rol' => $rol]);
            $response = $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Accesos rápidos');
            if ($rol === 'Secretaria') {
                $response->assertDontSee(route('analitica.index'));
                $this->get(route('cuentas.index'))->assertForbidden();
            } elseif ($rol === 'Entrenador') {
                $response->assertDontSee(route('ventas.index'));
                $this->get(route('progreso.index'))->assertForbidden();
            } else {
                $this->assertFalse(Gate::forUser($user)->allows('operaciones'));
            }
        }
    }
}

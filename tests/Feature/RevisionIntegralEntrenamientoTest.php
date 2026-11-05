<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RevisionIntegralEntrenamientoTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): Cliente
    {
        return Cliente::create(['nombre' => 'Atleta revisión', 'correo' => 'revision-ui@example.test', 'activo' => true]);
    }

    private function ejercicio(string $nombre = 'Sentadilla', string $grupo = 'Piernas'): Ejercicio
    {
        return Ejercicio::create(['nombre' => $nombre, 'grupo_muscular' => $grupo, 'subgrupo' => 'Cuádriceps', 'activo' => true]);
    }

    private function rutaProgreso(Cliente $cliente, string $rol, array $filtros = []): string
    {
        if ($rol === 'cliente') {
            $this->actingAs($cliente, 'cliente');

            return route('progreso.index', $filtros);
        }
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']), 'web');

        return route('entrenador.cliente.progreso', ['cliente' => $cliente, ...$filtros]);
    }

    public static function vistas(): array
    {
        return [['cliente'], ['administrador']];
    }

    public function test_catalog_search_honors_its_muscle_search_label_and_explicit_group_filter(): void
    {
        $cliente = $this->cliente();
        $piernas = $this->ejercicio();
        $this->ejercicio('Press', 'Pecho');
        $this->actingAs($cliente, 'cliente');

        foreach (['Piernas', 'Cuádriceps', 'Sentadilla'] as $texto) {
            $this->getJson(route('entrenamientos.catalogo.buscar', ['q' => $texto, 'grupo' => 'Piernas']))
                ->assertOk()->assertJsonCount(1, 'ejercicios')->assertJsonPath('ejercicios.0.id', $piernas->id);
        }
        $this->getJson(route('entrenamientos.catalogo.buscar', ['q' => 'Piernas', 'grupo' => 'Pecho']))
            ->assertOk()->assertJsonCount(0, 'ejercicios');
    }

    #[DataProvider('vistas')]
    public function test_progress_does_not_join_unrelated_exercises_into_a_strength_trend(string $rol): void
    {
        $cliente = $this->cliente();
        $sentadilla = $this->ejercicio();
        $curl = $this->ejercicio('Curl de bíceps', 'Biceps');
        foreach ([[$sentadilla, 80], [$curl, 20], [$sentadilla, 90]] as [$ejercicio, $peso]) {
            PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => $peso, 'repeticiones' => 5]);
        }
        $ruta = $this->rutaProgreso($cliente, $rol);
        $this->get($ruta)->assertOk()
            ->assertDontSee('<polyline fill="none" stroke="url(#gradiente-linea', false)
            ->assertSee('Selecciona un ejercicio en el filtro del historial para comparar tus levantamientos.');

        $this->get($ruta.'?ejercicio_id='.$sentadilla->id)->assertOk()
            ->assertSee('<polyline fill="none" stroke="url(#gradiente-linea', false)
            ->assertSee('role="img"', false)->assertSee('Evolución de Fuerza en Sentadilla');
    }

    #[DataProvider('vistas')]
    public function test_recent_progress_uses_id_to_break_equal_timestamp_ties(string $rol): void
    {
        $cliente = $this->cliente();
        $ejercicio = $this->ejercicio();
        $this->freezeTime();
        $ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $ids[] = PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id,
                'peso_kg' => $i * 10, 'repeticiones' => 5])->id;
        }
        $this->get($this->rutaProgreso($cliente, $rol))->assertOk()
            ->assertViewHas('records', fn ($records) => $records->first()->id === $ids[9])
            ->assertViewHas('ultimosLevantamientos', fn ($records) => $records->pluck('id')->all() === array_slice($ids, 2));
    }
}

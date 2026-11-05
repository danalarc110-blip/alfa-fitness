<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EjerciciosConsultaRobustezTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_search_is_applied_without_including_unrelated_or_inactive_exercises(): void
    {
        $coincide = Ejercicio::create(['nombre' => 'Press 10', 'grupo_muscular' => 'Pecho', 'activo' => true]);
        Ejercicio::create(['nombre' => 'Remo ajeno', 'grupo_muscular' => 'Espalda', 'activo' => true]);
        Ejercicio::create(['nombre' => 'Press 0 retirado', 'grupo_muscular' => 'Pecho', 'activo' => false]);
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'consulta.ejercicios@example.test', 'activo' => true]);

        $this->actingAs($cliente, 'cliente')->get(route('ejercicios.index', ['q' => ' 0 ']))
            ->assertOk()->assertViewHas('busqueda', '0')
            ->assertViewHas('ejercicios', fn ($ejercicios) => $ejercicios->pluck('id')->all() === [$coincide->id]);
    }

    public function test_zero_group_is_filtered_and_kept_selected(): void
    {
        $coincide = Ejercicio::create(['nombre' => 'Ejercicio del grupo cero', 'grupo_muscular' => '0', 'activo' => true]);
        Ejercicio::create(['nombre' => 'Ejercicio de piernas', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'grupo.ejercicios@example.test', 'activo' => true]);

        $this->actingAs($cliente, 'cliente')->get(route('ejercicios.index', ['grupo' => ' 0 ']))
            ->assertOk()->assertViewHas('grupoSeleccionado', '0')
            ->assertViewHas('ejercicios', fn ($ejercicios) => $ejercicios->pluck('id')->all() === [$coincide->id]);
    }

    public static function malformedQueries(): array
    {
        return [
            'exercise search array' => ['ejercicios.index', 'q', 'web'],
            'exercise group array' => ['ejercicios.index', 'grupo', 'web'],
            'trainer search array' => ['entrenadores.index', 'q', 'web'],
            'trainer role array' => ['entrenadores.index', 'rol', 'web'],
            'trainer client search array' => ['entrenador.clientes', 'q', 'web'],
            'trainer filter array' => ['entrenador.clientes', 'entrenador_id', 'web'],
            'own progress exercise array' => ['progreso.index', 'ejercicio_id', 'cliente'],
        ];
    }

    #[DataProvider('malformedQueries')]
    public function test_malformed_query_fields_return_validation_errors(string $route, string $field, string $guard): void
    {
        $actor = $guard === 'web'
            ? User::factory()->create(['rol' => 'Administrador'])
            : Cliente::create(['nombre' => 'Socio', 'correo' => 'consulta.invalida@example.test', 'activo' => true]);
        $this->actingAs($actor, $guard)->getJson(route($route, [$field => ['unexpected']]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_can_view_pr_statistics(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Demo',
            'correo' => 'cliente@example.com',
            'password' => 'password',
            'activo' => true,
        ]);

        $ejercicio = Ejercicio::create([
            'nombre' => 'Sentadilla',
            'grupo_muscular' => 'Piernas',
            'activo' => true,
        ]);

        PersonalRecord::create([
            'cliente_id' => $cliente->id,
            'ejercicio_id' => $ejercicio->id,
            'peso_kg' => 100,
            'repeticiones' => 3,
        ]);

        $this->actingAs($cliente, 'cliente')
            ->get(route('estadisticas.index'))
            ->assertOk()
            ->assertSee('PR / Estadísticas')
            ->assertSee('Sentadilla');
    }
}

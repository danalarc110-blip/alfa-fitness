<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_can_view_rankings_from_personal_records(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Demo',
            'correo' => 'cliente@example.com',
            'password' => 'password',
            'activo' => true,
        ]);

        $ejercicio = Ejercicio::create([
            'nombre' => 'Peso muerto',
            'grupo_muscular' => 'Espalda',
            'activo' => true,
        ]);

        PersonalRecord::create([
            'cliente_id' => $cliente->id,
            'ejercicio_id' => $ejercicio->id,
            'peso_kg' => 120,
            'repeticiones' => 2,
        ]);

        $this->actingAs($cliente, 'cliente')
            ->get(route('rankings.index'))
            ->assertOk()
            ->assertSee('Rankings')
            ->assertSee('Peso muerto')
            ->assertSee('Provisional');
    }
}

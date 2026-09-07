<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgresoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_can_register_a_personal_record(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Demo',
            'correo' => 'cliente@example.com',
            'password' => 'password',
            'activo' => true,
        ]);

        $ejercicio = Ejercicio::create([
            'nombre' => 'Press de banca',
            'grupo_muscular' => 'Pecho',
            'activo' => true,
        ]);

        $this->actingAs($cliente, 'cliente')
            ->get(route('progreso.index'))
            ->assertOk()
            ->assertSee('Progreso');

        $this->actingAs($cliente, 'cliente')
            ->post(route('progreso.store'), [
                'ejercicio_id' => $ejercicio->id,
                'peso_kg' => 80,
                'repeticiones' => 5,
                'notas' => 'Serie limpia',
            ])
            ->assertRedirect(route('progreso.index'));

        $this->assertDatabaseHas('personal_records', [
            'cliente_id' => $cliente->id,
            'ejercicio_id' => $ejercicio->id,
            'peso_kg' => 80,
            'repeticiones' => 5,
            'notas' => 'Serie limpia',
        ]);
    }
}

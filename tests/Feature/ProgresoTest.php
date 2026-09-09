<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\User;
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

    public function test_cliente_cannot_read_or_delete_another_clients_record(): void
    {
        $uno = Cliente::create(['nombre' => 'Uno', 'correo' => 'uno@example.com', 'password' => 'Password!123', 'activo' => true]);
        $dos = Cliente::create(['nombre' => 'Dos Privado', 'correo' => 'dos@example.com', 'password' => 'Password!123', 'activo' => true]);
        $ejercicio = Ejercicio::create(['nombre' => 'Privado', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $record = PersonalRecord::create(['cliente_id' => $dos->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 99, 'repeticiones' => 1]);
        $this->actingAs($uno, 'cliente')->get(route('progreso.index', ['cliente_id' => $dos->id]))->assertOk()->assertDontSee('Dos Privado')->assertDontSee('99 kg');
        $this->delete(route('progreso.destroy', $record))->assertForbidden();
        $this->assertDatabaseHas('personal_records', ['id' => $record->id]);
    }
}

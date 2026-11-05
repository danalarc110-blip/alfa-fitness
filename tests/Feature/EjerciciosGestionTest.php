<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EjerciciosGestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_create_update_and_toggle_exercises(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);

        // Non-admin cannot store
        $this->actingAs($entrenador)->post(route('ejercicios.store'), [
            'nombre' => 'Press Militar con Mancuernas',
            'grupo_muscular' => 'Hombros',
        ])->assertForbidden();

        // Admin can store
        $this->actingAs($admin)->post(route('ejercicios.store'), [
            'nombre' => 'Press Militar con Mancuernas',
            'grupo_muscular' => 'Hombros',
            'subgrupo' => 'Deltoides anterior',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('ejercicios', [
            'nombre' => 'Press Militar con Mancuernas',
            'grupo_muscular' => 'Hombros',
            'activo' => true,
        ]);

        $ejercicio = Ejercicio::where('nombre', 'Press Militar con Mancuernas')->firstOrFail();

        // Admin can update
        $this->actingAs($admin)->put(route('ejercicios.update', $ejercicio), [
            'nombre' => 'Press Militar de Pie con Barra',
            'grupo_muscular' => 'Hombros',
            'subgrupo' => 'Deltoides completo',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('ejercicios', [
            'id' => $ejercicio->id,
            'nombre' => 'Press Militar de Pie con Barra',
        ]);

        // Admin can toggle status
        $this->actingAs($admin)->patch(route('ejercicios.toggle', $ejercicio))->assertRedirect();
        $this->assertFalse($ejercicio->fresh()->activo);

        $this->actingAs($admin)->patch(route('ejercicios.toggle', $ejercicio))->assertRedirect();
        $this->assertTrue($ejercicio->fresh()->activo);
    }

    public function test_zero_subgroup_text_is_preserved_when_creating_and_updating_an_exercise(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->post(route('ejercicios.store'), [
            'nombre' => 'Ejercicio con subgrupo cero', 'grupo_muscular' => 'Hombros', 'subgrupo' => '0',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $ejercicio = Ejercicio::where('nombre', 'Ejercicio con subgrupo cero')->firstOrFail();
        $this->assertSame('0', $ejercicio->subgrupo);

        $ejercicio->update(['subgrupo' => 'Otro subgrupo']);
        $this->put(route('ejercicios.update', $ejercicio), [
            'nombre' => $ejercicio->nombre, 'grupo_muscular' => 'Hombros', 'subgrupo' => '0',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('0', $ejercicio->fresh()->subgrupo);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_and_update_products(): void
    {
        $empleado = User::create([
            'name' => 'Recepcion Demo',
            'email' => 'recepcion@example.com',
            'password' => 'password',
            'rol' => 'Recepcionista',
            'activo' => true,
        ]);

        $this->actingAs($empleado)
            ->post(route('productos.store'), [
                'nombre' => 'Agua',
                'precio' => 1.25,
                'categoria' => 'Bebidas',
                'stock' => 10,
            ])
            ->assertRedirect();

        $producto = Producto::where('nombre', 'Agua')->firstOrFail();

        $this->actingAs($empleado)
            ->get(route('productos.index'))
            ->assertOk()
            ->assertSee('Agua')
            ->assertSee('Bebidas');

        $this->actingAs($empleado)
            ->put(route('productos.update', $producto), [
                'stock' => 8,
                'activo' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'stock' => 8,
            'activo' => true,
        ]);
    }
}

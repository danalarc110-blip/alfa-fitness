<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\ImagenSegura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProductosPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): Cliente
    {
        return Cliente::create([
            'nombre' => 'Cliente del catálogo',
            'correo' => 'catalogo@example.test',
            'password' => 'Cliente!2026Seguro',
            'activo' => true,
        ]);
    }

    private function producto(array $atributos = []): Producto
    {
        return Producto::create(array_merge([
            'nombre' => 'Agua del catálogo',
            'categoria' => 'Bebidas',
            'precio' => '1.25',
            'stock' => 37,
            'activo' => true,
        ], $atributos));
    }

    public function test_cliente_catalog_is_informational_and_hides_internal_stock(): void
    {
        $producto = $this->producto();
        $this->producto(['nombre' => 'Barra del catálogo', 'stock' => 2]);
        $this->producto(['nombre' => 'Producto agotado', 'stock' => 0]);
        $this->producto(['nombre' => 'Producto oculto', 'activo' => false]);

        $this->actingAs($this->cliente(), 'cliente')->get(route('productos.index'))
            ->assertOk()
            ->assertSee($producto->nombre)
            ->assertSee('Bebidas')
            ->assertSee('$1.25')
            ->assertSee('Disponible')
            ->assertSee('Agotado')
            ->assertSee('Para comprar este producto, acércate a recepción. La venta la realiza la secretaria')
            ->assertDontSee('Producto oculto')
            ->assertDontSee('Stock crítico')
            ->assertDontSee('37 disponibles')
            ->assertDontSee('Quedan 2')
            ->assertDontSee('name="stock"', false)
            ->assertDontSee('data-stock=', false)
            ->assertDontSee('Editar producto')
            ->assertDontSee('Punto de Venta')
            ->assertDontSee('Carrito');
    }

    public function test_cliente_cannot_register_sales_or_modify_products_through_direct_requests(): void
    {
        $producto = $this->producto();
        $vendedor = User::factory()->create(['rol' => 'Secretaria']);
        $venta = Venta::create(['user_id' => $vendedor->id, 'total' => '1.25']);
        $this->actingAs($this->cliente(), 'cliente');

        $this->get(route('ventas.index'))->assertForbidden();
        $this->post(route('ventas.store'), [])->assertForbidden();
        $this->get(route('ventas.comprobante', $venta))->assertForbidden();
        $this->post(route('ventas.enviar-correo', $venta), ['correo' => 'destino@example.test'])->assertForbidden();
        $this->post(route('productos.store'), ['nombre' => 'No autorizado', 'precio' => 1, 'stock' => 50])->assertForbidden();
        $this->put(route('productos.update', $producto), ['nombre' => 'Alterado', 'precio' => 1, 'stock' => 0])->assertForbidden();

        $this->assertDatabaseCount('productos', 1);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertSame(37, $producto->fresh()->stock);
    }

    public function test_secretaria_can_create_products_without_establishing_stock(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))
            ->post(route('productos.store'), ['nombre' => 'Barra nueva', 'precio' => '2.50', 'categoria' => 'Alimentos'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['nombre' => 'Barra nueva', 'stock' => 0, 'activo' => true]);
        $this->get(route('productos.index'))->assertOk()->assertDontSee('name="stock"', false);
    }

    public function test_secretaria_stock_tampering_is_rejected_without_changes(): void
    {
        $producto = $this->producto();
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));

        $this->post(route('productos.store'), ['nombre' => 'Creación alterada', 'precio' => '2.50', 'stock' => 1])
            ->assertSessionHasErrors('stock');
        $this->put(route('productos.update', $producto), ['nombre' => 'Edición alterada', 'precio' => '2.50', 'stock' => 0, 'activo' => 1])
            ->assertSessionHasErrors('stock');

        $this->assertDatabaseCount('productos', 1);
        $this->assertSame('Agua del catálogo', $producto->fresh()->nombre);
        $this->assertSame(37, $producto->fresh()->stock);
    }

    public function test_secretaria_empty_stock_input_is_rejected_without_writing(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));
        foreach ([null, ''] as $indice => $stock) {
            $this->post(route('productos.store'), [
                'nombre' => 'Producto sin existencias '.$indice,
                'precio' => '2.50',
                'stock' => $stock,
            ])->assertRedirect()->assertSessionHasErrors('stock');
            $this->assertDatabaseMissing('productos', ['nombre' => 'Producto sin existencias '.$indice]);
        }
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_secretaria_can_update_product_fields_and_image_without_changing_stock(): void
    {
        $producto = $this->producto(['imagen' => 'producto_1_anterior.webp']);
        $nombreImagen = 'producto_'.$producto->id.'_nueva.webp';
        $this->mock(ImagenSegura::class, function ($mock) use ($producto, $nombreImagen) {
            $mock->shouldReceive('guardar')->once()
                ->withArgs(fn ($archivo, $directorio, $prefijo) => $archivo instanceof UploadedFile
                    && $directorio === 'productos' && $prefijo === 'producto_'.$producto->id)
                ->andReturn($nombreImagen);
            $mock->shouldReceive('eliminar')->once()
                ->with('producto_1_anterior.webp', 'productos', 'producto_'.$producto->id);
        });

        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))
            ->put(route('productos.update', $producto), [
                'nombre' => 'Agua fría',
                'precio' => '1.50',
                'categoria' => 'Bebidas frías',
                'activo' => 1,
                'imagen' => UploadedFile::fake()->image('agua.png', 40, 40),
            ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Agua fría',
            'precio' => '1.50',
            'categoria' => 'Bebidas frías',
            'imagen' => $nombreImagen,
            'stock' => 37,
        ]);
    }

    public function test_only_admin_has_manual_stock_permission_while_inventory_roles_are_preserved(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->cliente();

        $this->assertTrue(Gate::forUser($admin)->allows('existencias'));
        $this->assertTrue(Gate::forUser($admin)->allows('inventario'));
        $this->assertTrue(Gate::forUser($secretaria)->allows('inventario'));
        foreach ([$secretaria, $entrenador, $cliente] as $usuario) {
            $this->assertFalse(Gate::forUser($usuario)->allows('existencias'));
        }
        $this->assertFalse(Gate::forUser($entrenador)->allows('inventario'));
        $this->assertFalse(Gate::forUser($cliente)->allows('inventario'));
    }

    public function test_admin_can_set_and_correct_stock_but_cannot_set_negative_quantities(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']));
        $this->post(route('productos.store'), ['nombre' => 'Agua administrada', 'precio' => '1.25', 'stock' => 8])
            ->assertRedirect()->assertSessionHasNoErrors();
        $producto = Producto::firstOrFail();

        $this->put(route('productos.update', $producto), ['nombre' => $producto->nombre, 'precio' => '1.25', 'stock' => 15, 'stock_original' => 8, 'activo' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(15, $producto->fresh()->stock);
        $this->put(route('productos.update', $producto), ['nombre' => $producto->nombre, 'precio' => '1.25', 'stock' => -1, 'stock_original' => 15, 'activo' => 1])
            ->assertSessionHasErrors('stock');
        $this->post(route('productos.store'), ['nombre' => 'Stock negativo', 'precio' => '1.25', 'stock' => -1])
            ->assertSessionHasErrors('stock');

        $this->assertSame(15, $producto->fresh()->stock);
        $this->assertDatabaseCount('productos', 1);
    }

    public function test_coexisting_cliente_and_admin_guards_cannot_disclose_or_modify_stock(): void
    {
        $producto = $this->producto();
        $this->producto(['nombre' => 'Producto oculto dual', 'activo' => false]);
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']), 'web')
            ->actingAs($this->cliente(), 'cliente');

        $this->get(route('productos.index'))->assertOk()
            ->assertDontSee('Producto oculto dual')
            ->assertDontSee('37 disponibles')
            ->assertDontSee('name="stock"', false);
        $this->put(route('productos.update', $producto), ['nombre' => 'Alterado dual', 'precio' => 1, 'stock' => 0, 'activo' => 1])
            ->assertForbidden();
        $this->post(route('ventas.store'), [])->assertForbidden();
        $this->assertSame(37, $producto->fresh()->stock);
    }
}

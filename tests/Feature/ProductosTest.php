<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Database\Seeders\ProductoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_seeder_adds_exactly_five_editable_starter_products(): void
    {
        $this->seed(ProductoSeeder::class);

        $this->assertDatabaseCount('productos', 5);
        $this->assertSame(5, Producto::where('activo', true)->count());
        $this->assertDatabaseHas('productos', ['nombre' => 'Agua mineral 600 ml']);
    }

    public function test_catalog_uses_five_item_pages_without_deleting_legacy_products(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);

        foreach (range(1, 7) as $numero) {
            Producto::create([
                'nombre' => sprintf('Producto %02d', $numero),
                'precio' => $numero,
                'stock' => 1,
                'activo' => true,
                'orden' => $numero,
            ]);
        }

        $primeraPagina = $this->actingAs($secretaria)->get(route('productos.index'));
        $primeraPagina->assertOk()->assertViewHas('productos', function ($productos): bool {
            return $productos->perPage() === 5
                && $productos->currentPage() === 1
                && $productos->count() === 5
                && $productos->total() === 7;
        });

        $segundaPagina = $this->get(route('productos.index', ['page' => 2]));
        $segundaPagina->assertOk()->assertViewHas('productos', function ($productos): bool {
            return $productos->perPage() === 5
                && $productos->currentPage() === 2
                && $productos->count() === 2
                && $productos->total() === 7;
        });

        $this->assertDatabaseCount('productos', 7);
    }

    public function test_catalog_rejects_a_sixth_product_counting_inactive_products(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);

        foreach (range(1, 5) as $numero) {
            Producto::create([
                'nombre' => 'Producto '.$numero,
                'precio' => $numero,
                'stock' => 1,
                'activo' => $numero !== 5,
            ]);
        }

        $this->actingAs($secretaria)
            ->post(route('productos.store'), [
                'nombre' => 'Producto sexto',
                'precio' => 6,
                'stock' => 1,
            ])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('productos', 5);
        $this->assertDatabaseMissing('productos', ['nombre' => 'Producto sexto']);
    }

    public function test_authorized_staff_can_create_and_fully_update_products(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->post(route('productos.store'), ['nombre' => 'Agua', 'precio' => 1.25, 'categoria' => 'Bebidas', 'stock' => 10])->assertSessionHasNoErrors();
        $producto = Producto::firstOrFail();
        $this->actingAs($secretaria)->put(route('productos.update', $producto), ['nombre' => 'Agua fria', 'precio' => 1.5, 'categoria' => 'Bebidas', 'stock' => 8, 'activo' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'nombre' => 'Agua fria', 'precio' => 1.5, 'stock' => 8]);
        $this->post(route('productos.store'), ['nombre' => 'Invalido', 'precio' => -1, 'stock' => 0])->assertSessionHasErrors('precio');
    }

    public function test_trainer_cannot_modify_catalog_and_invalid_image_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Entrenador']))->post(route('productos.store'), [])->assertForbidden();
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->post(route('productos.store'), ['nombre' => 'Archivo', 'precio' => 1, 'stock' => 1, 'imagen' => UploadedFile::fake()->create('malware.php', 10, 'application/x-php')])->assertSessionHasErrors('imagen');
    }

    public function test_product_image_can_be_uploaded_previewed_replaced_and_removed(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->post(route('productos.store'), ['nombre' => 'Batido', 'precio' => 3, 'stock' => 2, 'imagen' => UploadedFile::fake()->image('batido.jpg', 300, 300)->size(120)])->assertSessionHasNoErrors();
        $producto = Producto::firstOrFail();
        $this->assertNotNull($producto->imagen);
        $this->assertStringEndsWith('.webp', $producto->imagen);
        $this->assertFileExists(public_path('images/productos/'.$producto->imagen));
        $this->actingAs($secretaria)->put(route('productos.update', $producto), ['nombre' => 'Batido', 'precio' => 3, 'stock' => 2, 'activo' => 1, 'eliminar_imagen' => 1])->assertSessionHasNoErrors();
        $this->assertNull($producto->fresh()->imagen);
    }
}

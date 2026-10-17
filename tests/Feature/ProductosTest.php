<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use App\Support\ImagenesCatalogo;
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
        foreach (Producto::all() as $producto) {
            $this->assertSame(ImagenesCatalogo::PRODUCTOS[$producto->nombre], $producto->imagen);
            $this->assertNotNull($producto->imagen_url);
            $this->assertFileExists(public_path('images/productos/'.$producto->imagen));
        }
    }

    public function test_image_migration_associates_known_products_and_preserves_valid_images(): void
    {
        $faltante = Producto::create(['nombre' => 'Agua mineral 600 ml', 'precio' => 1, 'stock' => 30]);
        $rota = Producto::create(['nombre' => 'Bebida isotónica', 'precio' => 2, 'stock' => 20, 'imagen' => 'archivo-inexistente.webp']);
        $imagenValida = ImagenesCatalogo::PRODUCTOS['Creatina monohidratada 300 g'];
        $valida = Producto::create(['nombre' => 'Proteína whey 2 lb', 'precio' => 35, 'stock' => 10, 'imagen' => $imagenValida]);
        $otro = Producto::create(['nombre' => 'Producto especial', 'precio' => 5, 'stock' => 1]);
        $migration = require database_path('migrations/2026_10_14_100000_associate_catalog_product_images.php');
        $migration->up();
        $migration->up();

        $this->assertSame(ImagenesCatalogo::PRODUCTOS[$faltante->nombre], $faltante->fresh()->imagen);
        $this->assertSame(ImagenesCatalogo::PRODUCTOS[$rota->nombre], $rota->fresh()->imagen);
        $this->assertSame($imagenValida, $valida->fresh()->imagen);
        $this->assertNull($otro->fresh()->imagen);
        $this->assertSame(30, $faltante->fresh()->stock);
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

    public function test_zero_search_is_applied_to_names_and_categories_and_spaces_are_trimmed(): void
    {
        Producto::create(['nombre' => 'Agua 600 ml', 'categoria' => 'Bebidas', 'precio' => 1, 'stock' => 1]);
        Producto::create(['nombre' => 'Toalla de entrenamiento', 'categoria' => 'Accesorios 0', 'precio' => 5, 'stock' => 1]);
        Producto::create(['nombre' => 'Barra excluida por búsqueda', 'categoria' => 'Snacks', 'precio' => 2, 'stock' => 1]);
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))
            ->get(route('productos.index', ['q' => ' 0 ']))->assertOk()
            ->assertSee('Agua 600 ml')->assertSee('Toalla de entrenamiento')
            ->assertDontSee('Barra excluida por búsqueda')->assertViewHas('busqueda', '0');
    }

    public function test_catalog_allows_more_than_five_products_without_artificial_cap(): void
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
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('productos', 6);
        $this->assertDatabaseHas('productos', ['nombre' => 'Producto sexto']);
    }

    public function test_secretary_can_manage_product_fields_without_manual_stock_changes(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->post(route('productos.store'), ['nombre' => 'Agua', 'precio' => 1.25, 'categoria' => 'Bebidas'])->assertSessionHasNoErrors();
        $producto = Producto::firstOrFail();
        $this->actingAs($secretaria)->put(route('productos.update', $producto), ['nombre' => 'Agua fria', 'precio' => 1.5, 'categoria' => 'Bebidas', 'activo' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'nombre' => 'Agua fria', 'precio' => 1.5, 'stock' => 0]);
        $this->post(route('productos.store'), ['nombre' => 'Invalido', 'precio' => -1])->assertSessionHasErrors('precio');
    }

    public function test_trainer_cannot_modify_catalog_and_invalid_image_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Entrenador']))->post(route('productos.store'), [])->assertForbidden();
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->post(route('productos.store'), ['nombre' => 'Archivo', 'precio' => 1, 'imagen' => UploadedFile::fake()->create('malware.php', 10, 'application/x-php')])->assertSessionHasErrors('imagen');
    }

    public function test_product_image_can_be_uploaded_previewed_replaced_and_removed(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->post(route('productos.store'), ['nombre' => 'Batido', 'precio' => 3, 'imagen' => UploadedFile::fake()->image('batido.jpg', 300, 300)->size(120)])->assertSessionHasNoErrors();
        $producto = Producto::firstOrFail();
        $this->assertNotNull($producto->imagen);
        $this->assertStringEndsWith('.webp', $producto->imagen);
        $this->assertFileExists(public_path('images/productos/'.$producto->imagen));
        $this->actingAs($secretaria)->put(route('productos.update', $producto), ['nombre' => 'Batido', 'precio' => 3, 'activo' => 1, 'eliminar_imagen' => 1])->assertSessionHasNoErrors();
        $this->assertNull($producto->fresh()->imagen);
    }
}

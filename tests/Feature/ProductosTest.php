<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertFileExists(public_path('images/productos/'.$producto->imagen));
        $this->actingAs($secretaria)->put(route('productos.update', $producto), ['nombre' => 'Batido', 'precio' => 3, 'stock' => 2, 'activo' => 1, 'eliminar_imagen' => 1])->assertSessionHasNoErrors();
        $this->assertNull($producto->fresh()->imagen);
    }
}

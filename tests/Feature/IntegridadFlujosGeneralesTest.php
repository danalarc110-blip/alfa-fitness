<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\PersonalRecord;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegridadFlujosGeneralesTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_and_test_data_seeder_execute_cleanly_and_idempotently(): void
    {
        // 1. DatabaseSeeder should populate public catalogs
        $this->seed(DatabaseSeeder::class);
        $this->assertGreaterThan(0, Ejercicio::count());
        $this->assertGreaterThan(0, Producto::count());

        // 2. TestDataSeeder should populate all realistic test data
        $this->seed(TestDataSeeder::class);

        $admin = User::where('email', 'admin@alphafitness.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame('Administrador', $admin->rol);
        $this->assertTrue($admin->activo);

        $secretaria = User::where('email', 'secretaria@alphafitness.test')->first();
        $this->assertNotNull($secretaria);
        $this->assertSame('Secretaria', $secretaria->rol);

        $this->assertSame(2, User::where('rol', 'Entrenador')->count());
        $this->assertSame(8, Cliente::count());
        $this->assertSame(8, SolicitudMembresia::count());
        $this->assertSame(8, Membresia::count());
        $this->assertSame(20, Asistencia::count());
        $this->assertSame(4, Asistencia::whereNull('fecha_salida')->count());
        $this->assertSame(20, Venta::count());
        $this->assertGreaterThanOrEqual(20, PersonalRecord::count());
        $this->assertDatabaseHas('rutinas', ['nombre' => 'Torso y Pecho Hipertrofia']);

        // 3. Second run should be idempotent without duplicate key exceptions
        $this->seed(TestDataSeeder::class);
        $this->assertSame(1, User::where('email', 'admin@alphafitness.test')->count());
        $this->assertSame(8, Cliente::count());
    }

    public function test_public_and_redirect_routes_behave_as_expected(): void
    {
        // Root redirects to login
        $this->get('/')->assertRedirect(route('login'));

        // Public gym information page loads and shows active plans
        PlanMembresia::create(['nombre' => 'Plan Diario Especial', 'precio' => 5.00, 'duracion_dias' => 1, 'activo' => true]);
        $this->get(route('informacion'))
            ->assertOk()
            ->assertSee('Centro Integral de Acondicionamiento Físico')
            ->assertSee('Plan Diario Especial')
            ->assertSee('$5.00');

        // Rankings and Estadisticas legacy routes redirect to progreso.index
        $cliente = Cliente::create(['nombre' => 'Socio Atleta', 'correo' => 'atleta@example.test', 'password' => 'Password!123', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('rankings.index'))->assertRedirect(route('progreso.index'));
        $this->actingAs($cliente, 'cliente')->get(route('estadisticas.index'))->assertRedirect(route('progreso.index'));
    }

    public function test_exercise_rating_validation_and_inactive_exercise_protection(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio Calificador', 'correo' => 'calificador@example.test', 'password' => 'Password!123', 'activo' => true]);
        $ejercicio = Ejercicio::create(['nombre' => 'Sentadilla Hack', 'grupo_muscular' => 'Piernas', 'activo' => true]);

        $this->actingAs($cliente, 'cliente');

        // Rating must be between 1 and 5
        $this->postJson(route('ejercicios.calificar', $ejercicio), ['estrellas' => 0])->assertUnprocessable()->assertJsonValidationErrors('estrellas');
        $this->postJson(route('ejercicios.calificar', $ejercicio), ['estrellas' => 6])->assertUnprocessable()->assertJsonValidationErrors('estrellas');

        // Valid rating works
        $response = $this->postJson(route('ejercicios.calificar', $ejercicio), ['estrellas' => 5]);
        $response->assertOk()->assertJsonPath('ok', true)->assertJsonPath('promedio', 5);

        // Inactive exercise cannot be rated
        $ejercicio->update(['activo' => false]);
        $this->postJson(route('ejercicios.calificar', $ejercicio), ['estrellas' => 4])->assertNotFound();
    }

    public function test_configuration_profile_and_password_validation_defenses(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio Perfil', 'correo' => 'perfil@example.test', 'password' => bcrypt('Password!12345'), 'activo' => true]);
        $this->actingAs($cliente, 'cliente');

        // Name cannot be empty or exceed 100 characters
        $this->post(route('configuracion.perfil'), ['nombre' => ''])->assertSessionHasErrors('nombre');
        $this->post(route('configuracion.perfil'), ['nombre' => str_repeat('a', 101)])->assertSessionHasErrors('nombre');

        // Valid name update
        $this->post(route('configuracion.perfil'), ['nombre' => 'Nuevo Nombre'])->assertRedirect()->assertSessionHas('status');
        $this->assertSame('Nuevo Nombre', $cliente->fresh()->nombre);

        // Password change requires correct current password
        $this->post(route('configuracion.password'), [
            'password_actual' => 'ClaveIncorrecta!123',
            'password' => 'NuevaClave!2026Fuerte',
            'password_confirmation' => 'NuevaClave!2026Fuerte',
        ])->assertSessionHasErrors('password_actual');

        // Password change requires confirmation to match
        $this->post(route('configuracion.password'), [
            'password_actual' => 'Password!12345',
            'password' => 'NuevaClave!2026Fuerte',
            'password_confirmation' => 'Mismatched!2026Fuerte',
        ])->assertSessionHasErrors('password');

        // Avatar validation: non-image or oversized
        $this->post(route('configuracion.avatar'), ['avatar' => 'no-file'])->assertSessionHasErrors('avatar');
    }

    public function test_sales_reject_empty_cart_and_invalid_quantities(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria);

        // Empty items array
        $this->post(route('ventas.store'), [
            'venta_uuid' => (string) Str::uuid(),
            'metodo_pago' => 'Efectivo',
            'items' => [],
        ])->assertSessionHasErrors('items');

        // Non-existent product
        $this->post(route('ventas.store'), [
            'venta_uuid' => (string) Str::uuid(),
            'metodo_pago' => 'Efectivo',
            'items' => [['producto_id' => 99999, 'cantidad' => 1]],
        ])->assertSessionHasErrors('items.0.producto_id');

        // Invalid quantity (0 or negative)
        $producto = Producto::create(['nombre' => 'Proteina', 'precio' => 30.00, 'stock' => 10, 'activo' => true]);
        $this->post(route('ventas.store'), [
            'venta_uuid' => (string) Str::uuid(),
            'metodo_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 0]],
        ])->assertSessionHasErrors('items.0.cantidad');
    }
}

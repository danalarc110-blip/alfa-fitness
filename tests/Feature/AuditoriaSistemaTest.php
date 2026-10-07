<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\EjercicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaSistemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ultima_membresia_relation_eager_loads_correctly_for_multiple_clients(): void
    {
        // Create 3 clients with different memberships
        $c1 = Cliente::create(['nombre' => 'Cliente Uno', 'correo' => 'uno@test.com', 'password' => 'Password!123', 'activo' => true]);
        $c2 = Cliente::create(['nombre' => 'Cliente Dos', 'correo' => 'dos@test.com', 'password' => 'Password!123', 'activo' => true]);
        $c3 = Cliente::create(['nombre' => 'Cliente Tres', 'correo' => 'tres@test.com', 'password' => 'Password!123', 'activo' => true]);

        Membresia::create(['cliente_id' => $c1->id, 'plan' => 'Plan Basico', 'importe' => 20, 'inicio' => today(), 'fin' => today()->addDays(30), 'cancelada' => false]);
        Membresia::create(['cliente_id' => $c2->id, 'plan' => 'Plan Pro', 'importe' => 35, 'inicio' => today(), 'fin' => today()->addDays(30), 'cancelada' => false]);
        Membresia::create(['cliente_id' => $c3->id, 'plan' => 'Plan VIP', 'importe' => 50, 'inicio' => today(), 'fin' => today()->addDays(30), 'cancelada' => false]);

        // Eager load using ultimaMembresia
        $clientes = Cliente::whereIn('id', [$c1->id, $c2->id, $c3->id])
            ->with('ultimaMembresia')
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $clientes);
        $this->assertNotNull($clientes[0]->ultimaMembresia);
        $this->assertSame('Plan Basico', $clientes[0]->ultimaMembresia->plan);

        $this->assertNotNull($clientes[1]->ultimaMembresia);
        $this->assertSame('Plan Pro', $clientes[1]->ultimaMembresia->plan);

        $this->assertNotNull($clientes[2]->ultimaMembresia);
        $this->assertSame('Plan VIP', $clientes[2]->ultimaMembresia->plan);
    }

    public function test_client_dashboard_displays_recent_visits(): void
    {
        $cliente = Cliente::create(['nombre' => 'Atleta Activo', 'correo' => 'atleta@test.com', 'password' => 'Password!123', 'activo' => true]);

        Asistencia::create([
            'cliente_id' => $cliente->id,
            'fecha_hora' => now()->subHours(2),
            'fecha_salida' => now()->subHour(),
            'tipo_acceso' => 'entrada',
        ]);

        $response = $this->actingAs($cliente, 'cliente')->get(route('cliente.dashboard'));
        $response->assertOk();
        $response->assertSee('Mis visitas recientes');
        $response->assertSee('Visita completada');
    }

    public function test_producto_and_user_image_fallbacks_return_null_when_file_missing(): void
    {
        $producto = Producto::create([
            'nombre' => 'Shaker Inexistente',
            'precio' => 9.99,
            'stock' => 5,
            'activo' => true,
            'imagen' => 'archivo_inexistente_123456.jpg',
        ]);

        $this->assertNull($producto->imagen_url);

        $user = User::factory()->create([
            'avatar' => 'avatar_fantasma_9999.png',
        ]);

        $this->assertNull($user->avatar_url);

        $cliente = Cliente::create([
            'nombre' => 'Test Avatar',
            'correo' => 'avatar@test.com',
            'password' => 'Password!123',
            'activo' => true,
            'avatar' => 'avatar_fantasma_cliente.png',
        ]);

        $this->assertNull($cliente->avatar_url);
    }

    public function test_all_seeded_exercises_have_muscle_images_available(): void
    {
        $this->seed(EjercicioSeeder::class);

        $ejercicios = Ejercicio::where('activo', true)->get();
        $this->assertGreaterThan(0, $ejercicios->count());

        foreach ($ejercicios as $ejercicio) {
            $this->assertTrue(
                $ejercicio->tiene_imagen_musculos,
                "El ejercicio '{$ejercicio->nombre}' debe tener su diagrama muscular existente en disco."
            );
            $this->assertNotEmpty($ejercicio->imagen_musculos_url);
        }
    }

    public function test_administrador_and_entrenador_dashboards_render_specialized_panels(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);

        // Crear una membresía que vence en 3 días para verificar la alerta
        $cliente = Cliente::create(['nombre' => 'Socio Vencimiento', 'correo' => 'socio@test.com', 'password' => 'Password!123', 'activo' => true]);
        Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30,
            'inicio' => today()->subDays(25),
            'fin' => today()->addDays(3),
            'cancelada' => false,
        ]);

        // Dashboard de Administrador: ve sección de ventas y planes por vencer
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Últimas ventas (TPV)')
            ->assertSee('Planes que vencen en los próximos 7 días');

        // Dashboard de Entrenador: ve ejercicios populares y no ve ventas de TPV
        $this->actingAs($entrenador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ejercicios populares')
            ->assertDontSee('Últimas ventas (TPV)');
    }
}

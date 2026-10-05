<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EstadisticaEjercicioTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente de prueba', 'correo' => $correo, 'password' => 'PruebaSegura!123', 'activo' => true]);
    }

    public function test_estadisticas_calculan_solo_datos_propios_e_incluyen_historial(): void
    {
        $cliente = $this->cliente('propio@example.test');
        $otro = $this->cliente('ajeno@example.test');
        $ejercicio = Ejercicio::create(['nombre' => 'Press propio', 'grupo_muscular' => 'Pecho', 'activo' => true]);
        $historico = Ejercicio::create(['nombre' => 'Máquina histórica', 'grupo_muscular' => 'Espalda', 'activo' => false]);
        $ajeno = Ejercicio::create(['nombre' => 'Historial ajeno privado', 'grupo_muscular' => 'Piernas', 'activo' => false]);
        PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 60, 'repeticiones' => 5]);
        PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 30, 'repeticiones' => 8]);
        PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $historico->id, 'peso_kg' => 80, 'repeticiones' => 8]);
        PersonalRecord::create(['cliente_id' => $otro->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 999, 'repeticiones' => 100]);
        PersonalRecord::create(['cliente_id' => $otro->id, 'ejercicio_id' => $ajeno->id, 'peso_kg' => 999, 'repeticiones' => 100]);

        $respuesta = $this->actingAs($cliente, 'cliente')->get(route('progreso.estadisticas', ['cliente_id' => $otro->id]));
        $respuesta->assertOk()->assertSee('Máquina histórica')->assertSee('Histórico')->assertDontSee('Historial ajeno privado')->assertDontSee('999.00');
        $respuesta->assertViewHas('resumen', fn ($resumen) => (int) $resumen->registros === 3 && (float) $resumen->volumen === 1180.0);
        $respuesta->assertViewHas('estadisticas', function ($datos) use ($ejercicio) {
            $press = $datos->firstWhere('id', $ejercicio->id);

            return $datos->total() === 2 && (float) $press->volumen === 540.0 && (float) $press->peso_maximo === 60.0 && (float) $press->estimacion_1rm === 70.0;
        });
    }

    public function test_estadisticas_filtran_por_fecha_y_limitaron_la_estimacion_a_treinta_repeticiones(): void
    {
        $cliente = $this->cliente('filtros@example.test');
        $ejercicio = Ejercicio::create(['nombre' => 'Máquina demo', 'grupo_muscular' => 'Pecho', 'activo' => true]);
        $anterior = PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 100, 'repeticiones' => 5]);
        $anterior->forceFill(['created_at' => '2026-01-01 12:00:00'])->save();
        $actual = PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 10, 'repeticiones' => 100]);
        $actual->forceFill(['created_at' => '2026-02-01 12:00:00'])->save();

        $this->actingAs($cliente, 'cliente')->get(route('progreso.estadisticas', ['ejercicio_id' => $ejercicio->id, 'desde' => '2026-02-01', 'hasta' => '2026-02-01']))
            ->assertOk()->assertViewHas('estadisticas', fn ($datos) => (int) $datos->first()->registros === 1 && (float) $datos->first()->estimacion_1rm === 20.0);
        $this->get(route('progreso.estadisticas', ['hasta' => '2026-01-01']))
            ->assertOk()->assertViewHas('resumen', fn ($datos) => (int) $datos->registros === 1);
        $this->get(route('progreso.estadisticas', ['desde' => 'fecha inválida']))->assertSessionHasErrors('desde');
        $this->get(route('progreso.estadisticas', ['desde' => '2026-02-01', 'hasta' => '2026-01-01']))->assertSessionHasErrors('hasta');
        $this->get(route('progreso.estadisticas', ['ejercicio_id' => '1 OR 1=1']))->assertSessionHasErrors('ejercicio_id');
    }

    public function test_estadisticas_paginan_y_muestran_estado_vacio(): void
    {
        $cliente = $this->cliente('paginas@example.test');
        $this->actingAs($cliente, 'cliente')->get(route('progreso.estadisticas'))->assertOk()->assertSee('No hay registros para estos filtros.');
        for ($indice = 1; $indice <= 16; $indice++) {
            $ejercicio = Ejercicio::create(['nombre' => sprintf('Ejercicio %02d', $indice), 'grupo_muscular' => 'Pecho', 'activo' => true]);
            PersonalRecord::create(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'peso_kg' => 20, 'repeticiones' => 5]);
        }
        $this->get(route('progreso.estadisticas'))->assertOk()->assertViewHas('estadisticas', fn ($datos) => $datos->total() === 16 && $datos->count() === 15);
        $this->get(route('progreso.estadisticas', ['page' => 2]))->assertOk()->assertViewHas('estadisticas', fn ($datos) => $datos->count() === 1);
    }

    public function test_personal_no_puede_consultar_estadisticas_de_clientes(): void
    {
        foreach (['Administrador', 'Secretaria', 'Entrenador'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]), 'web')->get(route('progreso.estadisticas'))->assertForbidden();
        }
    }

    public function test_comando_crea_administrador_con_clave_privada_y_no_crea_un_segundo(): void
    {
        $this->artisan('alpha:crear-admin')
            ->expectsQuestion('Nombre del administrador', 'Administrador Inicial')
            ->expectsQuestion('Correo del administrador', 'admin.inicial@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo)', 'ClaveInicial!123')
            ->expectsQuestion('Repite la contraseña', 'ClaveInicial!123')
            ->assertExitCode(0);
        $administrador = User::where('email', 'admin.inicial@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('ClaveInicial!123', $administrador->password));
        $this->assertSame('Administrador', $administrador->rol);
        $this->artisan('alpha:crear-admin')->assertExitCode(1);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_comando_admin_rechaza_contrasena_debil_sin_crear_cuenta(): void
    {
        $this->artisan('alpha:crear-admin')
            ->expectsQuestion('Nombre del administrador', 'Administrador Inicial')
            ->expectsQuestion('Correo del administrador', 'admin.inicial@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo)', 'debil')
            ->expectsQuestion('Repite la contraseña', 'debil')
            ->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_datos_demo_son_idempotentes_y_preservan_catalogos_y_claves(): void
    {
        $producto = Producto::create(['nombre' => 'Producto existente', 'precio' => 9, 'categoria' => 'Bebidas', 'stock' => 40, 'activo' => true]);
        $ejercicio = Ejercicio::create(['nombre' => 'Ejercicio existente', 'grupo_muscular' => 'Piernas', 'activo' => false]);
        $this->artisan('alpha:datos-prueba --con-venta')->assertExitCode(0);
        $hashInicial = User::where('email', 'secretaria.demo@alpha.example.test')->value('password');
        $this->artisan('alpha:datos-prueba --con-venta')->assertExitCode(0);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('membresias', 1);
        $this->assertDatabaseCount('pagos_membresia', 1);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('personal_records', 1);
        $this->assertDatabaseCount('rutinas', 1);
        $this->assertSame($hashInicial, User::where('email', 'secretaria.demo@alpha.example.test')->value('password'));
        $this->assertSame(40, $producto->fresh()->stock);
        $this->assertFalse($ejercicio->fresh()->activo);
        $this->assertDatabaseHas('productos', ['nombre' => 'Producto Demo Alpha', 'stock' => 4]);
    }

    public function test_comando_admin_no_permite_el_truncamiento_bcrypt_de_claves_multibyte(): void
    {
        $clave = 'Aa1!'.str_repeat('ñ', 35);
        $this->artisan('alpha:crear-admin')
            ->expectsQuestion('Nombre del administrador', 'Administrador Inicial')
            ->expectsQuestion('Correo del administrador', 'admin.inicial@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo)', $clave)
            ->expectsQuestion('Repite la contraseña', $clave)
            ->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_datos_demo_se_rechazan_en_produccion(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->artisan('alpha:datos-prueba --con-venta')->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_venta_demo_respeta_el_limite_de_cinco_productos_y_no_vende_stock_real(): void
    {
        for ($indice = 1; $indice <= 5; $indice++) {
            Producto::create(['nombre' => 'Producto existente '.$indice, 'precio' => 9, 'categoria' => 'Bebidas', 'stock' => 40, 'activo' => true]);
        }
        $this->artisan('alpha:datos-prueba --con-venta')->assertExitCode(1);
        $this->assertDatabaseCount('productos', 5);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(200, (int) Producto::sum('stock'));
        $this->artisan('alpha:datos-prueba')->assertExitCode(0);
        $this->assertDatabaseCount('productos', 5);
    }
}

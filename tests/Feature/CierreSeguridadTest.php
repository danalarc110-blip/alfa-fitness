<?php

namespace Tests\Feature;

use App\Http\Middleware\EncabezadosSeguros;
use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\Producto;
use App\Models\Rutina;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class CierreSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo = 'propio@example.test'): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente', 'correo' => $correo, 'activo' => true]);
    }

    private function rutina(string $guard, int $id): Rutina
    {
        return Rutina::create(['user_type' => $guard, 'user_id' => $id, 'nombre' => 'Privada', 'objetivo' => 'Fuerza', 'nivel' => 'Intermedio', 'dias_por_semana' => 1]);
    }

    private function membresia(Cliente $cliente): Membresia
    {
        return Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => 25, 'inicio' => today(), 'fin' => today()->addDays(30), 'cancelada' => false]);
    }

    public function test_empleado_no_puede_asignar_rutina_privada_ajena(): void
    {
        $cliente = $this->cliente();
        $rutina = $this->rutina('cliente', $cliente->id);
        $this->actingAs(User::factory()->create(['rol' => 'Entrenador']))
            ->post(route('entrenamientos.asignar', $rutina), ['cliente_id' => $cliente->id])->assertForbidden();
        $this->assertDatabaseCount('rutinas', 1);
    }

    public function test_empleado_puede_seguir_asignando_su_rutina(): void
    {
        $empleado = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->cliente();
        $rutina = $this->rutina('web', $empleado->id);
        $rutina->dias()->create(['orden' => 1, 'titulo' => 'Día 1']);
        $this->actingAs($empleado)->post(route('entrenamientos.asignar', $rutina), ['cliente_id' => $cliente->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rutinas', ['user_type' => 'cliente', 'user_id' => $cliente->id, 'nombre' => 'Privada']);
        $this->assertDatabaseCount('rutina_dias', 2);
    }

    public function test_cliente_no_toma_privilegios_staff_para_pausar_ni_reanudar(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $ajeno = $this->cliente('ajeno@example.test');
        $propia = $this->membresia($cliente);
        $ajena = $this->membresia($ajeno);
        $datos = ['dias' => 3, 'motivo' => 'Vacaciones', 'inicio_pausa' => today()->toDateString()];
        $this->actingAs($staff)->actingAs($cliente, 'cliente');
        $this->post(route('membresias.pausa.solicitar', $ajena), $datos)->assertForbidden();
        $this->patch(route('membresias.reanudar', $ajena))->assertForbidden();
        $this->post(route('membresias.pausa.solicitar', $propia), $datos)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pausas_membresia', ['membresia_id' => $propia->id, 'estado' => 'pendiente', 'aprobada_por' => null]);
        $this->assertSame(today()->addDays(30)->toDateString(), $propia->fresh()->fin->toDateString());
    }

    public function test_entrenadores_no_muestran_datos_administrativos_con_dos_guards(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        User::factory()->create(['rol' => 'Entrenador', 'activo' => false]);
        $this->actingAs($admin)->actingAs($this->cliente(), 'cliente')->get(route('entrenadores.index'))
            ->assertOk()->assertViewHas('esAdmin', false)->assertViewHas('entrenadores', fn ($lista) => $lista->total() === 0);
    }

    public function test_secretaria_edita_stock_pero_no_cambia_precios(): void
    {
        $producto = Producto::create(['nombre' => 'Agua', 'precio' => 2, 'stock' => 10, 'activo' => true]);
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));
        $this->put(route('productos.update', $producto), ['nombre' => 'Agua', 'precio' => '2.00', 'stock' => 9, 'activo' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(9, $producto->fresh()->stock);
        $this->put(route('productos.update', $producto), ['nombre' => 'Agua', 'precio' => '3.00', 'stock' => 8, 'activo' => 1])->assertForbidden();
        $this->assertSame('2.00', $producto->fresh()->precio);
        $this->assertSame(9, $producto->fresh()->stock);
    }

    public function test_precio_se_audita_solo_tras_confirmar_transaccion_sin_datos_personales(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $producto = Producto::create(['nombre' => 'Producto privado', 'precio' => 2, 'stock' => 10, 'activo' => true]);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->with('precio_cambiado', Mockery::on(fn ($datos) => $datos === [
            'anterior' => 2.0, 'nuevo' => 3.0, 'tabla' => 'productos', 'registro_id' => $producto->id,
            'guard' => 'web', 'actor_id' => $admin->id,
        ]));
        Log::shouldReceive('channel')->with('auditoria')->andReturn($logger);
        $this->actingAs($admin)->put(route('productos.update', $producto), ['nombre' => 'Producto privado', 'precio' => 3, 'stock' => 10, 'activo' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();
        try {
            DB::transaction(function () use ($producto) {
                $producto->update(['precio' => 4]);
                throw new \RuntimeException('Rollback de prueba');
            });
        } catch (\RuntimeException) {
            $this->assertSame('3.00', $producto->fresh()->precio);
        }
    }

    public function test_login_fallido_se_registra_sin_credenciales(): void
    {
        $usuario = User::factory()->create();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->with('login_fallido', ['guard' => 'web', 'cuenta_id' => $usuario->id]);
        Log::shouldReceive('channel')->with('auditoria')->andReturn($logger);
        $this->post(route('login.submit'), ['email' => $usuario->email, 'password' => 'secreto-incorrecto'])->assertSessionHasErrors('email');
    }

    public function test_cambio_de_rol_y_borrado_se_auditan_por_id(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $rutina = $this->rutina('web', $admin->id);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->with('rol_cambiado', [
            'anterior' => 'Secretaria', 'nuevo' => 'Entrenador', 'tabla' => 'users', 'registro_id' => $staff->id,
            'guard' => 'web', 'actor_id' => $admin->id,
        ]);
        $logger->shouldReceive('info')->once()->with('registro_eliminado', [
            'tabla' => 'rutinas', 'registro_id' => $rutina->id, 'guard' => 'web', 'actor_id' => $admin->id,
        ]);
        Log::shouldReceive('channel')->with('auditoria')->andReturn($logger);
        $this->actingAs($admin);
        $staff->update(['rol' => 'Entrenador']);
        $this->delete(route('entrenamientos.eliminar', $rutina))->assertRedirect();
        $this->assertDatabaseMissing('rutinas', ['id' => $rutina->id]);
    }

    public function test_csv_no_ejecuta_formulas_de_nombre_o_empleado(): void
    {
        $cliente = $this->cliente();
        $cliente->update(['nombre' => '=1+1']);
        $staff = User::factory()->create(['name' => "\t=2+2", 'rol' => 'Secretaria']);
        Asistencia::create(['cliente_id' => $cliente->id, 'registrado_por' => $staff->id, 'fecha_hora' => now()]);
        $contenido = $this->actingAs($staff)->get(route('asistencia.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=1+1", $contenido);
        $this->assertStringContainsString("'\t=2+2", $contenido);
    }

    public function test_filtros_ventas_malformados_se_rechazan(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));
        $this->getJson(route('ventas.index', ['desde' => ['mal']]))->assertUnprocessable();
        $this->getJson(route('ventas.index', ['desde' => '2026-10-03', 'hasta' => '2026-10-01']))->assertUnprocessable();
        $this->get(route('ventas.index', ['hasta' => '2026-10-04']))->assertOk();
    }

    public function test_fallo_smtp_no_registra_correo_ni_texto_excepcion(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $venta = Venta::create(['user_id' => $staff->id, 'total' => 2, 'metodo_pago' => 'Efectivo']);
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('smtp://usuario:secreto@servidor'));
        Log::shouldReceive('warning')->once()->with('Fallo al enviar comprobante de venta por correo.', ['venta_id' => $venta->id, 'exception' => \RuntimeException::class]);
        $this->actingAs($staff)->post(route('ventas.enviar-correo', $venta), ['correo' => 'privado@example.test'])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_venta_rechaza_datos_de_tarjeta_y_conserva_transferencia(): void
    {
        $producto = Producto::create(['nombre' => 'Agua', 'precio' => 2, 'stock' => 10, 'activo' => true]);
        $datos = ['metodo_pago' => 'Transferencia', 'items' => [['producto_id' => $producto->id, 'cantidad' => 1]]];
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->post(route('ventas.store'), $datos + ['cvv' => '123'])->assertSessionHasErrors('cvv');
        $this->assertDatabaseCount('ventas', 0);
        $this->post(route('ventas.store'), $datos)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ventas', ['metodo_pago' => 'Transferencia', 'total' => 2]);
    }

    public function test_catalogo_tpv_trata_nombres_como_texto(): void
    {
        Producto::create(['nombre' => '</select><img src=x onerror=alert(1)>', 'precio' => 2, 'stock' => 10, 'activo' => true]);
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->get(route('ventas.index'))
            ->assertOk()->assertSee('opcion.textContent', false)->assertDontSee('</select><img src=x onerror=alert(1)>', false);
    }

    public function test_produccion_redirige_https_a_host_configurado_y_no_host_solicitado(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://gimnasio.example.test', 'app.debug' => true]);
        $request = Request::create('http://host-atacante.example.test/login?destino=1');
        $respuesta = app(EncabezadosSeguros::class)->handle($request, fn () => response('No debe ejecutarse'));
        $this->assertSame(308, $respuesta->getStatusCode());
        $this->assertSame('https://gimnasio.example.test/login?destino=1', $respuesta->headers->get('Location'));
        $this->assertFalse(config('app.debug'));
        $this->assertTrue(config('session.secure'));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertTrue(config('session.encrypt'));
    }

    public function test_http_local_se_conserva_y_https_produccion_tiene_hsts(): void
    {
        $this->app['env'] = 'local';
        $local = app(EncabezadosSeguros::class)->handle(Request::create('http://localhost/login'), fn () => response('Local'));
        $this->assertSame(200, $local->getStatusCode());
        $this->assertFalse($local->headers->has('Strict-Transport-Security'));
        $this->app['env'] = 'production';
        $segura = app(EncabezadosSeguros::class)->handle(Request::create('https://gimnasio.example.test/login'), fn () => response('Seguro'));
        $this->assertSame(200, $segura->getStatusCode());
        $this->assertStringContainsString('max-age=', $segura->headers->get('Strict-Transport-Security'));
    }
}

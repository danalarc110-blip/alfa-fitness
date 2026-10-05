<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PlanMembresia;
use App\Models\Rutina;
use App\Models\SolicitudMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

/**
 * Contrato de los flujos existentes antes de la auditoría.
 * Los datos son sintéticos y RefreshDatabase utiliza SQLite en memoria.
 * Solo se simula Google, la frontera externa; el dominio y permisos son reales.
 */
class CaracterizacionFlujosTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'ClaveSegura!2026';

    private function cliente(array $atributos = []): Cliente
    {
        return Cliente::create(array_merge([
            'nombre' => 'Cliente de caracterización',
            'correo' => 'cliente-base@example.test',
            'password' => self::PASSWORD,
            'activo' => true,
        ], $atributos));
    }

    private function google(string $id, string $correo, bool $verificado = true): void
    {
        $usuario = (new GoogleUser)->setRaw(['email_verified' => $verificado])->map([
            'id' => $id,
            'email' => $correo,
            'name' => 'Cliente Google de prueba',
            'avatar' => null,
        ]);
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($usuario);
    }

    public function test_login_empleado_y_salir_conservan_panel_y_logout_cierra_la_sesion(): void
    {
        $empleado = User::factory()->create(['rol' => 'Secretaria', 'password' => self::PASSWORD]);
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login');

        $this->post(route('login.submit'), [
            'email' => $empleado->email,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($empleado, 'web');
        $this->assertGuest('cliente');
        $this->get(route('login'))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk();
        $this->post(route('salir'))->assertRedirect(route('informacion'));
        $this->assertAuthenticatedAs($empleado, 'web');
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_credenciales_invalidas_no_autentican_ninguno_de_los_guards(): void
    {
        $empleado = User::factory()->create(['password' => self::PASSWORD]);
        $cliente = $this->cliente();

        $this->post(route('login.submit'), [
            'email' => $empleado->email,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');
        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('correo');
        $this->assertGuest('web');
        $this->assertGuest('cliente');
    }

    public function test_cliente_se_registra_y_edita_su_perfil_sin_alterar_otra_cuenta(): void
    {
        $otro = $this->cliente(['nombre' => 'Cliente privado']);
        $this->post(route('cliente.registro'), [
            'nombre' => 'Cliente registrado',
            'correo' => 'registro-base@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            // La línea base conservada documenta el estado anterior a esta aceptación obligatoria.
            'consentimiento' => 1,
            'aceptacion_legal' => 1,
            'legal_version' => config('legal.version'),
        ])->assertRedirect(route('cliente.dashboard'))->assertSessionHasNoErrors();

        $nuevo = Cliente::where('correo', 'registro-base@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($nuevo, 'cliente');
        $this->assertGuest('web');
        $this->assertTrue($nuevo->activo);
        $this->assertTrue(Hash::check(self::PASSWORD, $nuevo->password));
        $this->assertNotSame(self::PASSWORD, $nuevo->password);
        $this->assertNull($nuevo->google_id);
        $this->get(route('configuracion'))->assertOk()->assertSee('Cliente registrado');

        $this->post(route('configuracion.perfil'), [
            'nombre' => 'Nombre actualizado',
            'cliente_id' => $otro->id,
            'correo' => 'no-se-cambia@example.test',
            'activo' => false,
            'rol' => 'Administrador',
        ])->assertRedirect()->assertSessionHas('status')->assertSessionHasNoErrors();

        $this->assertSame('Nombre actualizado', $nuevo->fresh()->nombre);
        $this->assertSame('registro-base@example.test', $nuevo->fresh()->correo);
        $this->assertTrue($nuevo->fresh()->activo);
        $this->assertSame('Cliente privado', $otro->fresh()->nombre);
        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_login_cliente_cierra_guard_empleado_y_logout_cierra_el_cliente(): void
    {
        $cliente = $this->cliente();
        $empleado = User::factory()->create();
        $this->actingAs($empleado, 'web');

        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo,
            'password' => self::PASSWORD,
        ])->assertRedirect(route('cliente.dashboard'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->assertGuest('web');
        $this->get(route('cliente.dashboard'))->assertOk();
        $this->get(route('login'))->assertRedirect(route('cliente.dashboard'));
        $this->post(route('cliente.salir'))->assertRedirect(route('informacion'));
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->post(route('cliente.logout'))->assertRedirect(route('login'));
        $this->assertGuest('cliente');
    }

    public function test_google_verificado_crea_cliente_nuevo_sin_password_local(): void
    {
        $this->google('google-nuevo', 'google-nuevo@example.test');
        $this->get(route('cliente.google.callback'))
            ->assertRedirect(route('cliente.google.consentimiento'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('clientes', 0);
        $this->post(route('cliente.google.aceptar'), ['aceptacion_legal' => 1, 'legal_version' => config('legal.version')])
            ->assertRedirect(route('cliente.dashboard'))->assertSessionHasNoErrors();

        $cliente = Cliente::where('google_id', 'google-nuevo')->firstOrFail();
        $this->assertSame('google-nuevo@example.test', $cliente->correo);
        $this->assertSame('Cliente Google de prueba', $cliente->nombre);
        $this->assertNull($cliente->password);
        $this->assertTrue($cliente->activo);
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->assertGuest('web');
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_google_existente_usa_identidad_estable_y_no_duplica_cuenta(): void
    {
        $cliente = $this->cliente([
            'correo' => 'google-anterior@example.test',
            'google_id' => 'google-existente',
            'password' => null,
        ]);
        $this->google('google-existente', 'google-nuevo-correo@example.test');
        $this->get(route('cliente.google.callback'))->assertRedirect(route('cliente.dashboard'));
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->assertDatabaseCount('clientes', 1);
        $this->assertSame('google-anterior@example.test', $cliente->fresh()->correo);
    }

    public function test_google_no_verificado_no_crea_ni_autentica_cliente(): void
    {
        $this->google('google-no-verificado', 'no-verificado@example.test', false);
        $this->get(route('cliente.google.callback'))
            ->assertRedirect(route('login'))->assertSessionHasErrors('correo');
        $this->assertGuest('cliente');
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_solicitud_pago_y_renovacion_conservan_snapshot_y_dias_pagados(): void
    {
        $cliente = $this->cliente();
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $plan = PlanMembresia::where('nombre', 'Mensual')->firstOrFail();
        $precioOriginal = $plan->precio;
        $this->actingAs($cliente, 'cliente')->post(route('membresias.solicitar'), [
            'plan_id' => $plan->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $solicitud = SolicitudMembresia::firstOrFail();
        $this->assertSame($cliente->id, $solicitud->cliente_id);
        $this->assertSame('pendiente', $solicitud->estado);
        $this->assertSame($precioOriginal, $solicitud->precio_acordado);
        $this->assertDatabaseCount('membresias', 0);
        $this->assertDatabaseCount('pagos_membresia', 0);
        $plan->update(['precio' => 30]);
        $this->assertSame($precioOriginal, $solicitud->fresh()->precio_acordado);

        $this->post(route('cliente.logout'));
        $this->actingAs($secretaria, 'web')->patch(route('membresias.activar', $solicitud), [
            'importe' => 25,
            'referencia' => 'EFECTIVO-PRUEBA',
            'metodo_pago' => 'efectivo',
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status');

        $membresia = Membresia::where('solicitud_id', $solicitud->id)->firstOrFail();
        $pago = PagoMembresia::where('solicitud_id', $solicitud->id)->firstOrFail();
        $this->assertSame('activada', $solicitud->fresh()->estado);
        $this->assertSame('Mensual', $membresia->plan);
        $this->assertSame('25.00', $membresia->importe);
        $this->assertTrue($membresia->inicio->isSameDay(today()));
        $this->assertTrue($membresia->fin->isSameDay(today()->addDays(29)));
        $this->assertSame($membresia->id, $pago->membresia_id);
        $this->assertSame($secretaria->id, $pago->registrado_por);
        $this->assertSame('EFECTIVO-PRUEBA', $pago->referencia);

        $this->patch(route('membresias.activar', $solicitud), ['importe' => 25])
            ->assertSessionHasErrors('importe');
        $this->assertDatabaseCount('membresias', 1);
        $this->assertDatabaseCount('pagos_membresia', 1);

        $this->post(route('logout'));
        $this->actingAs($cliente, 'cliente')->post(route('membresias.solicitar'), ['plan_id' => $plan->id])
            ->assertSessionHasNoErrors();
        $segunda = SolicitudMembresia::where('id', '!=', $solicitud->id)->firstOrFail();
        $this->post(route('cliente.logout'));
        $this->actingAs($secretaria, 'web')->patch(route('membresias.activar', $segunda), ['importe' => 30, 'metodo_pago' => 'efectivo'])
            ->assertSessionHasNoErrors();
        $renovacion = Membresia::where('solicitud_id', $segunda->id)->firstOrFail();
        $this->assertTrue($renovacion->inicio->isSameDay($membresia->fin->copy()->addDay()));
        $this->assertDatabaseCount('pagos_membresia', 2);
    }

    public function test_asistencia_registra_entrada_salida_y_rechaza_duplicados(): void
    {
        $cliente = $this->cliente();
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $otraSecretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria, 'web')->get(route('asistencia.index'))->assertOk()->assertSee($cliente->nombre);
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status');
        $asistencia = Asistencia::firstOrFail();
        $this->assertSame($cliente->id, $asistencia->cliente_id);
        $this->assertSame($secretaria->id, $asistencia->registrado_por);
        $this->assertNull($asistencia->fecha_salida);
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])
            ->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('asistencias', 1);

        $this->actingAs($otraSecretaria, 'web')->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($asistencia->fresh()->fecha_salida);
        $this->assertSame($otraSecretaria->id, $asistencia->fresh()->salida_registrada_por);
        $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])
            ->assertSessionHasErrors('cliente_id');
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('asistencias', 2);
    }

    public function test_constructor_rutina_crea_edita_dias_ejercicios_y_elimina_en_cascada(): void
    {
        $cliente = $this->cliente();
        $ejercicio = Ejercicio::create(['nombre' => 'Sentadilla de prueba', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->post(route('entrenamientos.crear'))->assertRedirect();
        $rutina = Rutina::firstOrFail();
        $dia = $rutina->dias()->firstOrFail();
        $this->assertSame($cliente->id, $rutina->user_id);
        $this->assertSame('cliente', $rutina->user_type);
        $this->assertSame('Nueva rutina', $rutina->nombre);
        $this->get(route('entrenamientos.index'))->assertOk()->assertSee('Nueva rutina');
        $this->get(route('entrenamientos.editar', $rutina))->assertOk();
        $this->putJson(route('entrenamientos.actualizar', $rutina), [
            'nombre' => 'Piernas lunes',
            'objetivo' => 'Ganar fuerza',
            'nivel' => 'Principiante',
            'dias_por_semana' => 2,
        ])->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('Piernas lunes', $rutina->fresh()->nombre);

        $this->postJson(route('entrenamientos.dias.crear', $rutina))->assertOk()->assertJsonPath('dia.orden', 2);
        $segundoDia = $rutina->dias()->where('orden', 2)->firstOrFail();
        $this->putJson(route('entrenamientos.dias.renombrar', $segundoDia), ['titulo' => 'Piernas martes'])
            ->assertOk()->assertJsonPath('dia.titulo', 'Piernas martes');

        $this->getJson(route('entrenamientos.catalogo.buscar', ['q' => 'Sentadilla', 'grupo' => 'Piernas']))
            ->assertOk()->assertJsonPath('ejercicios.0.id', $ejercicio->id);
        $this->postJson(route('entrenamientos.ejercicios.crear', $dia), ['ejercicio_id' => $ejercicio->id])
            ->assertOk()->assertJsonPath('rutina_ejercicio.series', 3);
        $asignado = $dia->ejercicios()->firstOrFail();
        $this->putJson(route('entrenamientos.ejercicios.actualizar', $asignado), [
            'series' => 4,
            'repeticiones' => '8-12',
            'peso' => 40,
            'descanso_segundos' => 90,
        ])->assertOk();
        $this->assertSame(4, $asignado->fresh()->series);
        $this->assertSame('40.00', $asignado->fresh()->peso);
        $this->putJson(route('entrenamientos.ejercicios.reordenar', $dia), ['orden' => [$asignado->id]])
            ->assertOk()->assertJsonPath('ok', true);
        $this->get(route('entrenamientos.entrenar', $rutina))->assertOk()->assertSee($ejercicio->nombre);
        $this->get(route('entrenamientos.imprimir', $rutina))->assertOk()->assertSee('Piernas lunes');

        $this->deleteJson(route('entrenamientos.dias.eliminar', $segundoDia))->assertOk();
        $this->delete(route('entrenamientos.eliminar', $rutina))->assertRedirect(route('entrenamientos.index'));
        $this->assertDatabaseMissing('rutinas', ['id' => $rutina->id]);
        $this->assertDatabaseMissing('rutina_dias', ['id' => $dia->id]);
        $this->assertDatabaseMissing('rutina_ejercicios', ['id' => $asignado->id]);
        $this->assertDatabaseHas('ejercicios', ['id' => $ejercicio->id]);
    }

    public function test_cliente_no_puede_leer_ni_mutar_rutina_o_membresia_de_otro(): void
    {
        $propietario = $this->cliente();
        $otro = $this->cliente(['correo' => 'otro-base@example.test']);
        $rutina = Rutina::create([
            'user_id' => $propietario->id,
            'user_type' => 'cliente',
            'nombre' => 'Rutina privada',
            'objetivo' => 'Fuerza',
            'nivel' => 'Principiante',
            'dias_por_semana' => 1,
        ]);
        $dia = $rutina->dias()->create(['orden' => 1, 'titulo' => 'Día privado']);
        $solicitud = SolicitudMembresia::create([
            'cliente_id' => $propietario->id,
            'plan_id' => PlanMembresia::firstOrFail()->id,
            'plan_nombre' => 'Plan privado',
            'precio_acordado' => 25,
            'duracion_dias' => 30,
        ]);

        $this->actingAs($otro, 'cliente')->get(route('entrenamientos.editar', $rutina))->assertForbidden();
        $this->get(route('entrenamientos.entrenar', $rutina))->assertForbidden();
        $this->get(route('entrenamientos.imprimir', $rutina))->assertForbidden();
        $this->putJson(route('entrenamientos.actualizar', $rutina), ['nombre' => 'Intrusión'])->assertForbidden();
        $this->delete(route('entrenamientos.eliminar', $rutina))->assertForbidden();
        $this->deleteJson(route('entrenamientos.dias.eliminar', $dia))->assertForbidden();
        $this->patch(route('membresias.solicitudes.cancelar', $solicitud))->assertForbidden();
        $this->get(route('membresias.index'))->assertOk()->assertDontSee('Plan privado');
        $this->assertSame('Rutina privada', $rutina->fresh()->nombre);
        $this->assertSame('pendiente', $solicitud->fresh()->estado);
    }

    public function test_ejercicios_admin_crea_edita_busca_y_desactiva_con_historial(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin, 'web')->post(route('ejercicios.store'), [
            'nombre' => 'Press catálogo de prueba',
            'grupo_muscular' => 'Pecho',
            'subgrupo' => 'Pectoral',
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status');
        $ejercicio = Ejercicio::where('nombre', 'Press catálogo de prueba')->firstOrFail();
        $this->assertTrue($ejercicio->activo);
        $this->put(route('ejercicios.update', $ejercicio), [
            'nombre' => 'Press actualizado de prueba',
            'grupo_muscular' => 'Pecho',
            'subgrupo' => 'Pectoral mayor',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('ejercicios.index', ['q' => 'Press actualizado', 'grupo' => 'Pecho']))
            ->assertOk()->assertSee('Press actualizado de prueba')->assertSee('Imagen no disponible');
        $this->patch(route('ejercicios.toggle', $ejercicio))->assertRedirect();
        $this->assertFalse($ejercicio->fresh()->activo);
        $this->assertDatabaseHas('ejercicios', ['id' => $ejercicio->id, 'nombre' => 'Press actualizado de prueba']);

        Auth::guard('web')->logout();
        $cliente = $this->cliente();
        $this->actingAs($cliente, 'cliente')->get(route('ejercicios.index'))
            ->assertOk()
            ->assertViewHas('ejercicios', fn ($catalogo) => ! $catalogo->getCollection()->contains('id', $ejercicio->id))
            ->assertSee('No se encontraron ejercicios');
        $this->post(route('ejercicios.store'), ['nombre' => 'No permitido', 'grupo_muscular' => 'Pecho'])->assertForbidden();
        $this->put(route('ejercicios.update', $ejercicio), ['nombre' => 'No permitido'])->assertForbidden();
        $this->patch(route('ejercicios.toggle', $ejercicio))->assertForbidden();
    }
}

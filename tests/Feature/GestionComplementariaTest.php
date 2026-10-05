<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PlanMembresia;
use App\Models\SesionEntrenador;
use App\Models\SolicitudMembresia;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GestionComplementariaTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo = 'gestion@example.test'): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente de gestión', 'correo' => $correo, 'password' => 'ClaveSegura!2026', 'activo' => true]);
    }

    private function datosSesion(Cliente $cliente, User $entrenador, array $extra = []): array
    {
        return array_merge([
            'cliente_id' => $cliente->id,
            'entrenador_id' => $entrenador->id,
            'fecha_inicio' => '2026-10-05T09:00',
            'fecha_fin' => '2026-10-05T10:00',
            'estado' => 'programada',
            'notas' => 'Practicar técnica',
        ], $extra);
    }

    public function test_admin_crea_invita_edita_y_desactiva_empleado_sin_borrar_historial(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->get(route('gestion.usuarios.create'))->assertOk();
        $this->post(route('gestion.usuarios.store'), ['name' => 'Nueva secretaria', 'email' => 'secretaria-nueva@example.test', 'rol' => 'Secretaria'])
            ->assertRedirect(route('gestion.usuarios.index'))->assertSessionHasNoErrors();
        $usuario = User::where('email', 'secretaria-nueva@example.test')->firstOrFail();
        $this->assertTrue($usuario->activo);
        $this->assertFalse($usuario->password_establecida);
        Notification::assertSentTo($usuario, ResetPassword::class);
        $this->get(route('gestion.usuarios.index', ['q' => 'secretaria-nueva@example.test']))
            ->assertOk()->assertViewHas('usuarios', fn ($usuarios) => $usuarios->total() === 1);
        $this->get(route('gestion.usuarios.edit', $usuario))->assertOk();
        $this->put(route('gestion.usuarios.update', $usuario), ['name' => 'Nuevo entrenador', 'email' => 'entrenador-nuevo@example.test', 'rol' => 'Entrenador'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Entrenador', $usuario->fresh()->rol);
        $this->delete(route('gestion.usuarios.destroy', $usuario))->assertRedirect();
        $this->assertFalse($usuario->fresh()->activo);
        $this->assertDatabaseCount('users', 2);
        $this->patch(route('gestion.usuarios.reactivar', $usuario))->assertRedirect();
        $this->assertTrue($usuario->fresh()->activo);
    }

    public function test_roles_y_admin_unico_estan_protegidos_en_gestion(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->post(route('gestion.usuarios.store'), ['name' => 'Segundo admin', 'email' => 'segundo-admin@example.test', 'rol' => 'Administrador'])
            ->assertSessionHasErrors('rol');
        $this->delete(route('gestion.usuarios.destroy', $admin))->assertForbidden();
        $this->put(route('gestion.usuarios.update', $admin), ['name' => 'Modificado', 'email' => 'modificado@example.test', 'rol' => 'Secretaria'])
            ->assertForbidden();
        $this->assertSame('Administrador', $admin->fresh()->rol);
        $this->assertTrue($admin->fresh()->activo);
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->get(route('gestion.usuarios.index'))->assertForbidden();
        $this->get(route('gestion.planes.index'))->assertForbidden();
        $this->post(route('gestion.usuarios.store'), [])->assertForbidden();
    }

    public function test_secretaria_crea_edita_cliente_asigna_entrenador_y_preserva_membresia(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);
        $this->actingAs($secretaria)->get(route('gestion.clientes.create'))->assertOk();
        $this->post(route('gestion.clientes.store'), [
            'nombre' => 'Nuevo cliente', 'correo' => 'nuevo-cliente@example.test', 'entrenador_id' => $entrenador->id,
            'password' => 'ClaveSegura!2026', 'password_confirmation' => 'ClaveSegura!2026',
            'aceptacion_legal' => 1, 'legal_version' => 'falsificada',
        ])->assertRedirect(route('gestion.clientes.index'))->assertSessionHasNoErrors();
        $cliente = Cliente::where('correo', 'nuevo-cliente@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('ClaveSegura!2026', $cliente->password));
        $this->assertSame($entrenador->id, $cliente->entrenador_id);
        $this->assertNull($cliente->legal_aceptado_en);
        $this->assertNull($cliente->legal_version);
        $this->get(route('gestion.clientes.edit', $cliente))->assertOk();
        $this->put(route('gestion.clientes.update', $cliente), ['nombre' => 'Cliente actualizado', 'correo' => 'actualizado@example.test', 'entrenador_id' => $entrenador->id])
            ->assertSessionHasNoErrors();
        $this->assertSame('Cliente actualizado', $cliente->fresh()->nombre);
        $this->assertNull($cliente->fresh()->legal_aceptado_en);
        $this->get(route('gestion.clientes.index', ['q' => 'actualizado@example.test']))->assertOk()->assertSee('Cliente actualizado');
        $membresia = Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Histórico', 'importe' => 25, 'inicio' => today(), 'fin' => today()->addDays(29), 'cancelada' => false]);
        $this->delete(route('gestion.clientes.destroy', $cliente))->assertRedirect();
        $this->assertFalse($cliente->fresh()->activo);
        $this->assertSame($secretaria->id, $cliente->fresh()->baneado_por);
        $this->assertDatabaseHas('membresias', ['id' => $membresia->id]);
        $this->patch(route('gestion.clientes.reactivar', $cliente))->assertRedirect();
        $this->assertTrue($cliente->fresh()->activo);
    }

    public function test_alta_cliente_rechaza_password_debil_y_entrenador_inactivo(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador', 'activo' => false]);
        $this->actingAs($secretaria)->post(route('gestion.clientes.store'), [
            'nombre' => 'Cliente', 'correo' => 'debil@example.test', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');
        $this->post(route('gestion.clientes.store'), [
            'nombre' => 'Cliente', 'correo' => 'inactivo@example.test', 'password' => 'ClaveSegura!2026', 'password_confirmation' => 'ClaveSegura!2026', 'entrenador_id' => $entrenador->id,
        ])->assertSessionHasErrors('entrenador_id');
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_admin_gestiona_planes_sin_modificar_snapshots_ni_borrar(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->cliente();
        $this->actingAs($admin)->get(route('gestion.planes.create'))->assertOk();
        $data = ['nombre' => 'Plan de gestión', 'precio' => 42, 'duracion_dias' => 35, 'condiciones' => 'Acceso al gimnasio'];
        $this->post(route('gestion.planes.store'), $data)->assertSessionHasNoErrors();
        $plan = PlanMembresia::where('nombre', 'Plan de gestión')->firstOrFail();
        $solicitud = SolicitudMembresia::create(['cliente_id' => $cliente->id, 'plan_id' => $plan->id, 'plan_nombre' => $plan->nombre, 'precio_acordado' => $plan->precio, 'duracion_dias' => $plan->duracion_dias]);
        $this->get(route('gestion.planes.edit', $plan))->assertOk();
        $this->put(route('gestion.planes.update', $plan), array_merge($data, ['precio' => 50]))->assertSessionHasNoErrors();
        $this->assertSame('50.00', $plan->fresh()->precio);
        $this->assertSame('42.00', $solicitud->fresh()->precio_acordado);
        $this->get(route('gestion.planes.index', ['q' => 'Plan de gestión']))->assertOk()->assertSee('Plan de gestión');
        $this->delete(route('gestion.planes.destroy', $plan))->assertRedirect();
        $this->assertFalse($plan->fresh()->activo);
        $this->assertDatabaseHas('solicitudes_membresia', ['id' => $solicitud->id, 'plan_id' => $plan->id]);
        $this->patch(route('gestion.planes.reactivar', $plan))->assertRedirect();
        $this->assertTrue($plan->fresh()->activo);
        $this->post(route('gestion.planes.store'), $data)->assertSessionHasErrors('nombre');
    }

    public function test_agenda_crea_edita_cancela_y_permite_horarios_contiguos(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->cliente();
        $data = $this->datosSesion($cliente, $entrenador);
        $this->actingAs($secretaria)->get(route('gestion.sesiones.create'))->assertOk();
        $this->post(route('gestion.sesiones.store'), $data)->assertSessionHasNoErrors();
        $sesion = SesionEntrenador::firstOrFail();
        $this->assertSame($secretaria->id, $sesion->registrado_por);
        $this->assertSame('2026-10-05 09:00:00', $sesion->fecha_inicio->format('Y-m-d H:i:s'));
        $this->get(route('gestion.sesiones.index', ['q' => 'Cliente de gestión']))->assertOk()->assertSee($cliente->nombre);
        $this->get(route('gestion.sesiones.edit', $sesion))->assertOk();
        $this->put(route('gestion.sesiones.update', $sesion), array_merge($data, ['notas' => 'Sesión actualizada']))->assertSessionHasNoErrors();
        $this->assertSame('Sesión actualizada', $sesion->fresh()->notas);
        $this->post(route('gestion.sesiones.store'), array_merge($data, ['fecha_inicio' => '2026-10-05T10:00', 'fecha_fin' => '2026-10-05T11:00']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sesiones_entrenador', 2);
        $this->delete(route('gestion.sesiones.destroy', $sesion))->assertRedirect();
        $this->assertSame('cancelada', $sesion->fresh()->estado);
        $this->post(route('gestion.sesiones.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sesiones_entrenador', 3);
    }

    public function test_agenda_rechaza_solapamiento_de_entrenador_o_cliente_y_horas_invalidas(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);
        $otroEntrenador = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->cliente();
        $otroCliente = $this->cliente('otro-agenda@example.test');
        $this->actingAs($secretaria)->post(route('gestion.sesiones.store'), $this->datosSesion($cliente, $entrenador))->assertSessionHasNoErrors();
        $this->post(route('gestion.sesiones.store'), $this->datosSesion($otroCliente, $entrenador, ['fecha_inicio' => '2026-10-05T09:30', 'fecha_fin' => '2026-10-05T10:30']))
            ->assertSessionHasErrors('fecha_inicio');
        $this->post(route('gestion.sesiones.store'), $this->datosSesion($cliente, $otroEntrenador, ['fecha_inicio' => '2026-10-05T08:30', 'fecha_fin' => '2026-10-05T09:30']))
            ->assertSessionHasErrors('fecha_inicio');
        $this->post(route('gestion.sesiones.store'), $this->datosSesion($cliente, $entrenador, ['fecha_fin' => '2026-10-05T08:00']))
            ->assertSessionHasErrors('fecha_fin');
        $this->assertDatabaseCount('sesiones_entrenador', 1);
    }

    public function test_entrenador_solo_ve_y_edita_sesiones_propias_cliente_solo_lee_propias(): void
    {
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);
        $otroEntrenador = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->cliente();
        $otroCliente = $this->cliente('otro-privado@example.test');
        $otroCliente->update(['nombre' => 'Cliente privado ajeno']);
        $propia = SesionEntrenador::create($this->datosSesion($cliente, $entrenador));
        $ajena = SesionEntrenador::create($this->datosSesion($otroCliente, $otroEntrenador));
        $this->actingAs($entrenador)->get(route('gestion.sesiones.index'))->assertOk()
            ->assertViewHas('sesiones', fn ($sesiones) => $sesiones->getCollection()->pluck('id')->all() === [$propia->id]);
        $this->get(route('gestion.sesiones.edit', $ajena))->assertForbidden();
        $this->put(route('gestion.sesiones.update', $ajena), $this->datosSesion($cliente, $entrenador))->assertForbidden();
        $this->delete(route('gestion.sesiones.destroy', $ajena))->assertForbidden();
        $this->post(route('gestion.sesiones.store'), $this->datosSesion($cliente, $otroEntrenador))->assertForbidden();
        Auth::guard('web')->logout();
        $this->actingAs($cliente, 'cliente')->get(route('gestion.sesiones.index'))->assertOk()->assertDontSee('Cliente privado ajeno')
            ->assertViewHas('sesiones', fn ($sesiones) => $sesiones->getCollection()->pluck('id')->all() === [$propia->id]);
        $this->get(route('gestion.sesiones.create'))->assertForbidden();
        $this->get(route('gestion.sesiones.edit', $propia))->assertForbidden();
        $this->put(route('gestion.sesiones.update', $propia), $this->datosSesion($cliente, $entrenador))->assertForbidden();
        $this->delete(route('gestion.sesiones.destroy', $propia))->assertForbidden();
        $this->assertSame('programada', $propia->fresh()->estado);
    }

    public function test_guard_cliente_no_hereda_poderes_admin_aunque_coexistan_sesiones(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->cliente();
        $this->actingAs($admin, 'web')->actingAs($cliente, 'cliente');
        foreach (['usuarios', 'clientes', 'planes'] as $modulo) {
            $this->get(route("gestion.{$modulo}.index"))->assertForbidden();
            $this->get(route("gestion.{$modulo}.create"))->assertForbidden();
            $this->post(route("gestion.{$modulo}.store"), [])->assertForbidden();
        }
        $this->get(route('gestion.sesiones.index'))->assertOk();
        $this->post(route('gestion.sesiones.store'), [])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccesoEntrenadoresTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $nombre): Cliente
    {
        return Cliente::create(['nombre' => $nombre, 'correo' => uniqid().'@example.test', 'activo' => true]);
    }

    private function asignar(Cliente $cliente, User $entrenador, string $nombre = 'Rutina asignada'): void
    {
        Rutina::create([
            'user_type' => 'cliente', 'user_id' => $cliente->id, 'asignado_por_id' => $entrenador->id,
            'nombre' => $nombre, 'objetivo' => 'Fuerza', 'nivel' => 'Principiante', 'dias_por_semana' => 3,
        ]);
    }

    public function test_admin_can_resend_a_pending_invitation_and_broker_throttles_retries(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador', 'password_establecida' => false]);
        $password = $entrenador->password;
        $this->actingAs($admin)->from(route('entrenadores.index'))->post(route('entrenadores.invitar', $entrenador))
            ->assertRedirect(route('entrenadores.index'))
            ->assertSessionHas('status', fn ($mensaje) => str_contains($mensaje, 'no se entregará por correo'));
        Notification::assertSentTo($entrenador, ResetPassword::class);
        $this->assertSame($password, $entrenador->fresh()->password);
        $this->assertFalse($entrenador->fresh()->password_establecida);

        $this->post(route('entrenadores.invitar', $entrenador))->assertSessionHas('error');
        Notification::assertSentToTimes($entrenador, ResetPassword::class, 1);
        $this->get(route('entrenadores.index'))->assertOk()
            ->assertSee('No se pudo reenviar la invitación. Espera un minuto')
            ->assertSee('role="alert"', false);
    }

    public function test_invitation_rejects_inactive_established_and_non_staff_accounts(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin);
        foreach ([['activo' => false, 'password_establecida' => false], ['activo' => true, 'password_establecida' => true]] as $datos) {
            $entrenador = User::factory()->create(array_merge(['rol' => 'Entrenador'], $datos));
            $this->post(route('entrenadores.invitar', $entrenador))->assertSessionHas('error');
        }
        $this->post(route('entrenadores.invitar', $admin))->assertNotFound();
        Notification::assertNothingSent();
    }

    public function test_only_admin_can_resend_invitation(): void
    {
        Notification::fake();
        $pendiente = User::factory()->create(['rol' => 'Entrenador', 'password_establecida' => false]);
        foreach (['Entrenador', 'Secretaria'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]))
                ->post(route('entrenadores.invitar', $pendiente))->assertForbidden();
        }
        $this->post(route('logout'));
        $this->actingAs($this->cliente('Miembro'), 'cliente')
            ->post(route('entrenadores.invitar', $pendiente))->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_only_admin_sees_delivery_configuration_and_smtp_invitation_reports_transport_success(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador', 'password_establecida' => false]);
        $this->actingAs($admin)->get(route('entrenadores.index'))
            ->assertOk()->assertSee('las invitaciones no se entregan');
        config(['mail.default' => 'smtp']);
        $this->get(route('entrenadores.index'))->assertOk()->assertDontSee('las invitaciones no se entregan');
        $this->post(route('entrenadores.invitar', $entrenador))
            ->assertSessionHas('status', fn ($mensaje) => str_starts_with($mensaje, 'Invitación enviada.'));
        Notification::assertSentTo($entrenador, ResetPassword::class);
    }

    public function test_client_sees_own_trainers_independent_of_directory_search_and_without_private_email(): void
    {
        $cliente = $this->cliente('Cliente vinculado');
        $propio = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Coach propio']);
        $ajeno = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Coach ajeno']);
        $inactivo = User::factory()->create(['rol' => 'Entrenador', 'activo' => false]);
        $administracion = User::factory()->create(['rol' => 'Administrador']);
        $this->asignar($cliente, $propio);
        $this->asignar($cliente, $propio, 'Otra rutina');
        $this->asignar($cliente, $inactivo);
        $this->asignar($cliente, $administracion);
        $this->asignar($this->cliente('Otro cliente'), $ajeno);

        $this->actingAs($cliente, 'cliente')->get(route('entrenadores.index', ['q' => 'Coach ajeno']))
            ->assertOk()->assertSee('Mis entrenadores')->assertSee($propio->name)
            ->assertDontSee($propio->email)->assertDontSee($ajeno->email)
            ->assertDontSee('las invitaciones no se entregan')
            ->assertDontSee('Reenviar invitación')
            ->assertViewHas('misEntrenadores', fn ($entrenadores) => $entrenadores->pluck('id')->all() === [$propio->id]);
    }

    public function test_admin_filters_clients_by_trainer_with_unique_clients_and_matching_routines(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $primero = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Ana Coach']);
        $segundo = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Luis Coach']);
        $compartido = $this->cliente('Cliente compartido');
        $ajeno = $this->cliente('Cliente de Luis');
        $this->asignar($compartido, $primero, 'Fuerza con Ana');
        $this->asignar($compartido, $primero, 'Movilidad con Ana');
        $this->asignar($compartido, $segundo, 'Cardio con Luis');
        $this->asignar($ajeno, $segundo);

        $this->actingAs($admin)->get(route('entrenador.clientes', ['entrenador_id' => $primero->id]))
            ->assertOk()->assertSee('Fuerza con Ana')->assertSee('Movilidad con Ana')
            ->assertDontSee('Cardio con Luis')->assertDontSee($ajeno->nombre)
            ->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 1 && $clientes->first()->rutinas->count() === 2)
            ->assertViewHas('responsables', fn ($responsables) => $responsables->get($primero->id) === $primero->name);
        $this->get(route('entrenador.clientes', ['entrenador_id' => $admin->id]))->assertSessionHasErrors('entrenador_id');
    }

    public function test_trainer_cannot_use_admin_filter_to_view_foreign_clients(): void
    {
        $propio = User::factory()->create(['rol' => 'Entrenador']);
        $ajeno = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->cliente('Cliente privado');
        $this->asignar($cliente, $ajeno);
        $this->actingAs($propio)->get(route('entrenador.clientes', ['entrenador_id' => $ajeno->id]))->assertForbidden();
        $this->get(route('entrenador.clientes'))->assertOk()->assertDontSee($cliente->nombre);
        $this->get(route('entrenador.cliente', $cliente))->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class RevisionSeguridadDiariaTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): Cliente
    {
        return Cliente::create([
            'nombre' => 'Cliente de prueba',
            'correo' => 'revocacion@example.test',
            'password' => 'ClaveSegura!2026',
            'activo' => true,
        ]);
    }

    private function nuevaSesion(array $datos = []): void
    {
        $this->app['session']->flush();
        $this->app['auth']->forgetGuards();
        $this->withSession($datos);
    }

    private function bloquearYRestaurar(Cliente $cliente): void
    {
        $this->nuevaSesion();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin, 'web')
            ->patch(route('cuentas.banear', $cliente))->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('cuentas.restaurar', $cliente))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($cliente->fresh()->activo);
    }

    public function test_restoring_access_never_restores_a_session_opened_before_the_ban(): void
    {
        $cliente = $this->cliente();
        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo,
            'password' => 'ClaveSegura!2026',
        ])->assertSessionHasNoErrors();
        $sesionAnterior = $this->app['session']->all();

        $this->bloquearYRestaurar($cliente);
        $this->nuevaSesion();
        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo,
            'password' => 'ClaveSegura!2026',
            'remember' => true,
        ])->assertSessionHasNoErrors();
        $nuevoTokenRecordado = $cliente->fresh()->remember_token;
        $this->nuevaSesion($sesionAnterior);

        $this->getJson(route('configuracion'))->assertForbidden();
        $this->assertGuest('cliente');
        $this->assertSame($nuevoTokenRecordado, $cliente->fresh()->remember_token);
    }

    public function test_restoring_access_never_restores_an_old_remember_cookie(): void
    {
        $cliente = $this->cliente();
        $cliente->forceFill(['remember_token' => str_repeat('a', 60)])->save();
        $guard = auth('cliente');
        $cookieNombre = $guard->getRecallerName();
        $cookie = $cliente->id.'|'.$cliente->remember_token.'|'.$guard->hashPasswordForCookie($cliente->password);

        $this->bloquearYRestaurar($cliente);
        $this->nuevaSesion();

        $this->withCredentials()->withCookie($cookieNombre, $cookie)
            ->getJson(route('configuracion'))->assertUnauthorized();
        $this->assertGuest('cliente');
    }

    public function test_restored_client_can_login_again_and_use_a_new_session(): void
    {
        $cliente = $this->cliente();
        $password = $cliente->password;
        $this->bloquearYRestaurar($cliente);
        $this->nuevaSesion();

        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo,
            'password' => 'ClaveSegura!2026',
            'remember' => true,
        ])->assertRedirect(route('cliente.dashboard'))->assertSessionHasNoErrors();
        $this->get(route('configuracion'))->assertOk();
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->assertSame($password, $cliente->fresh()->password);
    }

    public function test_legacy_session_without_marker_only_survives_if_access_was_never_revoked(): void
    {
        $cliente = $this->cliente();
        $sesionAnterior = [auth('cliente')->getName() => $cliente->id];
        $this->nuevaSesion($sesionAnterior);
        $this->get(route('configuracion'))->assertOk()->assertSessionHas('version_acceso_cliente', 0);

        $this->bloquearYRestaurar($cliente);
        $this->nuevaSesion($sesionAnterior);

        $this->getJson(route('configuracion'))->assertForbidden();
        $this->assertGuest('cliente');
    }

    public function test_restored_google_account_without_password_gets_a_valid_new_session(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Google',
            'correo' => 'google-revocacion@example.test',
            'google_id' => 'google-revocacion',
            'activo' => true,
        ]);
        $this->bloquearYRestaurar($cliente);
        $this->nuevaSesion();
        $google = (new GoogleUser)->setRaw(['email_verified' => true])->map([
            'id' => $cliente->google_id,
            'email' => $cliente->correo,
            'name' => $cliente->nombre,
        ]);
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($google);

        $this->get(route('cliente.google.callback'))
            ->assertRedirect(route('cliente.dashboard'))->assertSessionHas('version_acceso_cliente', 2);
        $this->get(route('configuracion'))->assertOk();
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->assertNull($cliente->fresh()->password);
    }

    public function test_restoring_a_legacy_banned_account_revokes_predeployment_sessions_and_cookies(): void
    {
        $cliente = $this->cliente();
        $cliente->forceFill(['activo' => false, 'remember_token' => str_repeat('b', 60)])->save();
        $guard = auth('cliente');
        $sesionAnterior = [$guard->getName() => $cliente->id];
        $cookieNombre = $guard->getRecallerName();
        $cookie = $cliente->id.'|'.$cliente->remember_token.'|'.$guard->hashPasswordForCookie($cliente->password);

        $this->actingAs(User::factory()->create(['rol' => 'Administrador']), 'web')
            ->patch(route('cuentas.restaurar', $cliente))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($cliente->fresh()->activo);
        $this->nuevaSesion();
        $this->withCredentials()->withCookie($cookieNombre, $cookie)
            ->getJson(route('configuracion'))->assertUnauthorized();

        $this->nuevaSesion($sesionAnterior);
        $this->getJson(route('configuracion'))->assertForbidden();
        $this->assertGuest('cliente');
    }
}

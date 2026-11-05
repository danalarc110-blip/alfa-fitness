<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RevisionIntegralSeguridadTest extends TestCase
{
    use RefreshDatabase;

    public static function contrasenasIncompatibles(): array
    {
        return [
            'mas de 72 bytes ASCII' => [str_repeat('a', 72).'A1!'],
            'mas de 72 bytes Unicode' => [str_repeat('Á', 34).'aA1!x'],
            'byte nulo' => ['ClaveSegura!2026'.chr(0).'sufijo'],
        ];
    }

    #[DataProvider('contrasenasIncompatibles')]
    public function test_registration_rejects_passwords_that_bcrypt_would_truncate(string $password): void
    {
        $this->reconocerPrivacidad();
        $this->postJson(route('cliente.registro'), [
            'nombre' => 'Cliente',
            'correo' => 'registro@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('clientes', 0);
        $this->assertGuest('cliente');
    }

    #[DataProvider('contrasenasIncompatibles')]
    public function test_password_change_rejects_truncation_and_preserves_the_original_password(string $password): void
    {
        $user = User::factory()->create(['password' => 'OriginalSegura!2026']);
        $this->actingAs($user)->postJson(route('configuracion.password'), [
            'password_actual' => 'OriginalSegura!2026',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check('OriginalSegura!2026', $user->fresh()->password));
    }

    #[DataProvider('contrasenasIncompatibles')]
    public function test_password_reset_rejects_truncation_without_consuming_the_token(string $password): void
    {
        $cliente = $this->cliente();
        $token = Password::broker('clientes')->createToken($cliente);

        $this->postJson(route('password.update'), [
            'email' => $cliente->correo,
            'token' => $token,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check('OriginalSegura!2026', $cliente->fresh()->password));
        $this->assertTrue(Password::broker('clientes')->tokenExists($cliente, $token));
    }

    public function test_exactly_72_bytes_remain_usable_but_an_appended_suffix_cannot_authenticate(): void
    {
        $password = str_repeat('Á', 33).'aA1!xy';
        $this->assertSame(72, strlen($password));
        $this->reconocerPrivacidad();
        $this->post(route('cliente.registro'), [
            'nombre' => 'Cliente',
            'correo' => 'limite@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));

        $this->post(route('cliente.logout'));
        $this->reconocerPrivacidad();
        $this->postJson(route('cliente.login.submit'), [
            'correo' => 'limite@example.test', 'password' => $password.'sufijo',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertGuest('cliente');
        $this->post(route('cliente.login.submit'), [
            'correo' => 'limite@example.test', 'password' => $password,
        ])->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));
    }

    public function test_staff_login_rejects_a_null_suffix_instead_of_accepting_a_different_password(): void
    {
        $user = User::factory()->create(['password' => 'OriginalSegura!2026']);
        $this->reconocerPrivacidad();
        $this->postJson(route('login.submit'), [
            'email' => $user->email, 'password' => 'OriginalSegura!2026'.chr(0).'sufijo',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertGuest('web');
    }

    public function test_ban_revokes_the_previous_remember_token_after_access_is_restored(): void
    {
        $cliente = $this->cliente();
        $cliente->forceFill(['remember_token' => 'token-anterior'])->save();
        $provider = auth('cliente')->getProvider();
        $this->assertNotNull($provider->retrieveByToken($cliente->id, 'token-anterior'));
        $cookie = auth('cliente')->getRecallerName();
        $recaller = $cliente->id.'|token-anterior|'.auth('cliente')->hashPasswordForCookie($cliente->password);
        $this->restaurarSesion([]);
        $this->withCookie($cookie, $recaller)->get(route('cliente.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->withCookie($cookie, '');
        $this->restaurarSesion([]);

        $this->actingAs(User::factory()->create(['rol' => 'Administrador']), 'web')
            ->patch(route('cuentas.banear', $cliente))->assertRedirect();
        $this->patch(route('cuentas.restaurar', $cliente))->assertRedirect();

        $this->assertTrue($cliente->fresh()->activo);
        $this->assertNull($provider->retrieveByToken($cliente->id, 'token-anterior'));
        $this->assertTrue(Hash::check('OriginalSegura!2026', $cliente->fresh()->password));
        $this->restaurarSesion([]);
        $this->withCookie($cookie, $recaller)->get(route('cliente.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest('cliente');
    }

    public static function sesionesPrevias(): array
    {
        return ['sesion actual' => [false], 'sesion legado sin version' => [true]];
    }

    #[DataProvider('sesionesPrevias')]
    public function test_a_session_issued_before_a_ban_cannot_return_after_restoration(bool $legado): void
    {
        $cliente = $this->cliente();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->reconocerPrivacidad();
        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo, 'password' => 'OriginalSegura!2026',
        ])->assertSessionHasNoErrors();
        $this->get(route('cliente.dashboard'))->assertOk();
        $sesionAnterior = session()->all();
        if ($legado) {
            unset($sesionAnterior['access_version_cliente']);
        }
        $this->restaurarSesion($sesionAnterior);
        $this->get(route('cliente.dashboard'))->assertOk();

        $this->restaurarSesion([]);
        $this->actingAs($admin, 'web')->patch(route('cuentas.banear', $cliente))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($cliente->fresh()->activo);
        $this->restaurarSesion($sesionAnterior);
        $this->getJson(route('cliente.dashboard'))->assertForbidden();
        $this->assertGuest('cliente');

        $this->restaurarSesion([]);
        $this->actingAs($admin, 'web')->patch(route('cuentas.restaurar', $cliente))->assertRedirect();
        $this->restaurarSesion($sesionAnterior);
        $this->getJson(route('cliente.dashboard'))->assertUnauthorized();
        $this->assertGuest('cliente');

        $this->reconocerPrivacidad();
        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo, 'password' => 'OriginalSegura!2026',
        ])->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));
        $this->get(route('cliente.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($cliente->fresh(), 'cliente');

        $sesionNueva = session()->all();
        $tokenNuevo = $cliente->fresh()->remember_token;
        $this->restaurarSesion($sesionAnterior);
        $this->getJson(route('cliente.dashboard'))->assertUnauthorized();
        $this->assertSame($tokenNuevo, $cliente->fresh()->remember_token);
        $this->restaurarSesion($sesionNueva);
        $this->get(route('cliente.dashboard'))->assertOk();
        $this->actingAs($admin, 'web')->get(route('cuentas.index'))->assertForbidden();
    }

    private function restaurarSesion(array $sesion): void
    {
        session()->flush();
        session()->replace($sesion);
        session()->save();
        Auth::forgetGuards();
    }

    public function test_google_login_without_a_local_password_records_the_restored_access_version(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Google', 'correo' => 'google@example.test',
            'google_id' => 'subject-google', 'activo' => true,
        ]);
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']), 'web')
            ->patch(route('cuentas.banear', $cliente))->assertRedirect();
        $this->patch(route('cuentas.restaurar', $cliente))->assertRedirect();
        $this->restaurarSesion([]);
        $this->reconocerPrivacidad();

        $google = (new GoogleUser)->setRaw(['email_verified' => true])->map([
            'id' => 'subject-google', 'email' => $cliente->correo, 'name' => 'Google',
        ]);
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($google);
        $this->get(route('cliente.google.callback'))
            ->assertRedirect(route('cliente.dashboard'))->assertSessionHas('access_version_cliente', 1);
        $this->get(route('cliente.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($cliente->fresh(), 'cliente');
    }

    private function cliente(): Cliente
    {
        return Cliente::create([
            'nombre' => 'Cliente', 'correo' => 'cliente@example.test',
            'password' => 'OriginalSegura!2026', 'activo' => true,
        ]);
    }
}

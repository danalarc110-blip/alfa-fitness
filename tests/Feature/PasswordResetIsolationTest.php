<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function accounts(): array
    {
        $user = User::factory()->create(['email' => 'shared@example.test', 'password' => 'OriginalStaff!2026']);
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => $user->email, 'password' => 'OriginalClient!2026', 'activo' => true]);

        return [$user, $cliente];
    }

    private function reset(string $token, string $email)
    {
        return $this->post(route('password.update'), [
            'token' => $token,
            'email' => $email,
            'password' => 'NuevaClave!2026Segura',
            'password_confirmation' => 'NuevaClave!2026Segura',
        ]);
    }

    public function test_client_token_cannot_change_staff_password_at_the_same_address(): void
    {
        [$user, $cliente] = $this->accounts();
        $user->update(['rol' => 'Administrador']);
        $token = Password::broker('clientes')->createToken($cliente);

        $this->reset($token, $cliente->correo)->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('OriginalStaff!2026', $user->fresh()->password));
        $this->assertTrue(Hash::check('NuevaClave!2026Segura', $cliente->fresh()->password));
        $this->reset($token, $cliente->correo)->assertSessionHasErrors('email');
    }

    public function test_provider_tokens_are_independent_and_cannot_be_used_in_the_other_broker(): void
    {
        [$user, $cliente] = $this->accounts();
        $userToken = Password::broker('users')->createToken($user);
        $clientToken = Password::broker('clientes')->createToken($cliente);

        $this->assertFalse(Password::broker('users')->tokenExists($user, $clientToken));
        $this->assertFalse(Password::broker('clientes')->tokenExists($cliente, $userToken));
        $this->reset($userToken, $user->email)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('OriginalClient!2026', $cliente->fresh()->password));
        $this->reset($clientToken, $cliente->correo)->assertSessionHasNoErrors();
        $this->reset($userToken, $user->email)->assertSessionHasErrors('email');
    }

    public function test_request_sends_independent_links_to_both_active_accounts_at_one_address(): void
    {
        Notification::fake();
        [$user, $cliente] = $this->accounts();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertSentTo($cliente, ResetPassword::class);
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertDatabaseCount('cliente_password_reset_tokens', 1);
    }

    public function test_inactive_accounts_cannot_reset_password_even_with_an_existing_token(): void
    {
        [$user, $cliente] = $this->accounts();
        $userToken = Password::broker('users')->createToken($user);
        $clientToken = Password::broker('clientes')->createToken($cliente);
        $user->update(['activo' => false]);
        $cliente->update(['activo' => false]);

        $this->reset($userToken, $user->email)->assertSessionHasErrors('email');
        $this->reset($clientToken, $cliente->correo)->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OriginalStaff!2026', $user->fresh()->password));
        $this->assertTrue(Hash::check('OriginalClient!2026', $cliente->fresh()->password));
    }

    public function test_expired_client_token_is_rejected(): void
    {
        [, $cliente] = $this->accounts();
        $token = Password::broker('clientes')->createToken($cliente);
        $this->travel(61)->minutes();

        $this->reset($token, $cliente->correo)->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OriginalClient!2026', $cliente->fresh()->password));
    }

    public function test_mail_failure_does_not_expose_transport_details_or_change_the_generic_response(): void
    {
        Password::shouldReceive('broker')->with('users')->once()->andReturnSelf();
        Password::shouldReceive('broker')->with('clientes')->once()->andReturnSelf();
        Password::shouldReceive('sendResetLink')->twice()->andThrow(new \RuntimeException('Private transport details'));
        Log::shouldReceive('warning')->twice()->with('No se pudo enviar un enlace de recuperación.', ['exception' => \RuntimeException::class]);

        $this->post(route('password.email'), ['email' => 'shared@example.test'])
            ->assertRedirect()->assertSessionHas('status')->assertSessionHasNoErrors();
    }

    public function test_isolation_migration_expires_ambiguous_old_tokens_and_preserves_accounts(): void
    {
        [$user, $cliente] = $this->accounts();
        $oldToken = Password::broker('users')->createToken($user);
        $migration = require database_path('migrations/2026_10_07_100000_isolate_client_password_reset_tokens.php');

        $migration->down();
        $migration->up();

        $this->assertFalse(Password::broker('users')->tokenExists($user, $oldToken));
        $this->assertDatabaseCount('cliente_password_reset_tokens', 0);
        $this->assertTrue(Hash::check('OriginalStaff!2026', $user->fresh()->password));
        $this->assertTrue(Hash::check('OriginalClient!2026', $cliente->fresh()->password));
    }
}

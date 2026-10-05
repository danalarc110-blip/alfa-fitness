<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Tests\TestCase;

class GoogleTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_button_remains_available_when_credentials_are_not_configured(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null, 'services.google.redirect' => null]);
        $this->get(route('login'))->assertOk()->assertSee('Continuar con Google')
            ->assertSee('href="'.route('cliente.google').'"', false);
    }

    public function test_unconfigured_google_reports_an_error_without_authenticating_or_calling_provider(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        Socialite::shouldReceive('driver')->never();
        $this->get(route('cliente.google'))->assertRedirect(route('login'))->assertSessionHasErrors('correo');
        $this->assertGuest('cliente');
        $this->assertGuest('web');
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_configured_google_redirect_uses_session_state_and_original_callback(): void
    {
        config(['services.google.client_id' => 'synthetic-client-id', 'services.google.client_secret' => 'synthetic-not-a-real-secret', 'services.google.redirect' => 'http://127.0.0.1:8000/cliente/google/callback']);
        // Construct the actual provider URL locally; this never contacts Google.
        $response = $this->get(route('cliente.google'))->assertStatus(302)->assertSessionHas('state');
        $url = $response->headers->get('Location');
        $this->assertSame('https', parse_url($url, PHP_URL_SCHEME));
        $this->assertSame('accounts.google.com', parse_url($url, PHP_URL_HOST));
        parse_str(parse_url($url, PHP_URL_QUERY), $parametros);
        $this->assertSame('synthetic-client-id', $parametros['client_id']);
        $this->assertSame('http://127.0.0.1:8000/cliente/google/callback', $parametros['redirect_uri']);
        $this->assertSame(session('state'), $parametros['state']);
        $this->assertNotEmpty($parametros['state']);
        $this->assertGuest('cliente');
    }

    public function test_google_callback_with_invalid_state_cannot_authenticate(): void
    {
        config(['services.google.client_id' => 'synthetic-client-id', 'services.google.client_secret' => 'synthetic-not-a-real-secret', 'services.google.redirect' => 'http://127.0.0.1:8000/cliente/google/callback']);
        $this->withSession(['state' => 'expected-state'])->get(route('cliente.google.callback', ['state' => 'wrong-state', 'code' => 'synthetic-code']))
            ->assertRedirect(route('login'))->assertSessionHasErrors('correo');
        $this->assertGuest('cliente');
        $this->assertDatabaseCount('clientes', 0);
    }

    private function provider(string $id, string $email, bool $verified = true): void
    {
        $user = (new User)->setRaw(['email_verified' => $verified])->map(['id' => $id, 'email' => $email, 'name' => 'Google User', 'avatar' => null]);
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($user);
    }

    public function test_google_does_not_silently_link_an_existing_local_account(): void
    {
        $client = Cliente::create(['nombre' => 'Local', 'correo' => 'local@example.test', 'password' => 'ClaveLocal!2026', 'activo' => true]);
        $this->provider('subject-1', $client->correo);
        $this->get(route('cliente.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('correo');
        $this->assertNull($client->fresh()->google_id);
        $this->assertGuest('cliente');
    }

    public function test_link_requires_current_password_and_consumes_short_lived_authorization(): void
    {
        config(['services.google.client_id' => 'test', 'services.google.client_secret' => 'test']);
        $client = Cliente::create(['nombre' => 'Local', 'correo' => 'local@example.test', 'password' => 'ClaveLocal!2026', 'activo' => true]);
        $this->actingAs($client, 'cliente')->post(route('configuracion.google'), ['password_actual' => 'incorrecta'])->assertSessionHasErrors('password_actual');
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com'));
        $this->post(route('configuracion.google'), ['password_actual' => 'ClaveLocal!2026'])->assertRedirect('https://accounts.google.com')->assertSessionHas('google_link');
        $this->provider('subject-1', $client->correo);
        $this->get(route('cliente.google.callback'))->assertRedirect(route('cliente.dashboard'))->assertSessionMissing('google_link');
        $this->assertSame('subject-1', $client->fresh()->google_id);
    }

    public function test_google_subject_survives_email_changes_without_creating_another_account(): void
    {
        $client = Cliente::create(['nombre' => 'Google', 'correo' => 'old@example.test', 'google_id' => 'subject-1', 'activo' => true]);
        $this->provider('subject-1', 'new@example.test');
        $this->get(route('cliente.google.callback'))->assertRedirect(route('cliente.dashboard'));
        $this->assertAuthenticatedAs($client, 'cliente');
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_unverified_google_email_cannot_create_an_account(): void
    {
        $this->provider('subject-1', 'unverified@example.test', false);
        $this->get(route('cliente.google.callback'))->assertSessionHasErrors('correo');
        $this->assertDatabaseCount('clientes', 0);
    }
}

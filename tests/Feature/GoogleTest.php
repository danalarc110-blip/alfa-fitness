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

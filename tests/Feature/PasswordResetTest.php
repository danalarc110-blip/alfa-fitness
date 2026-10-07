<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_renders_successfully(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertOk()
            ->assertSee('Recuperar contraseña');
    }

    public function test_reset_link_is_sent_for_registered_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'staff@alfafitness.test']);

        $response = $this->post(route('password.email'), [
            'email' => 'staff@alfafitness.test',
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_request_returns_same_generic_message_for_unregistered_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'noexiste@alfafitness.test',
        ]);

        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'staff@alfafitness.test']);
        $token = Password::broker('users')->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'staff@alfafitness.test',
            'password' => 'NuevaClave!2026Segura',
            'password_confirmation' => 'NuevaClave!2026Segura',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NuevaClave!2026Segura', $user->fresh()->password));
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'staff@alfafitness.test']);

        $response = $this->post(route('password.update'), [
            'token' => 'token-invalido',
            'email' => 'staff@alfafitness.test',
            'password' => 'NuevaClave!2026Segura',
            'password_confirmation' => 'NuevaClave!2026Segura',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

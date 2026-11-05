<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthInputIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_form_rejects_an_array_email_without_a_server_error(): void
    {
        $this->getJson(route('password.reset', ['token' => 'test-token', 'email' => ['unexpected']]))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_password_reset_form_still_accepts_a_missing_or_valid_email(): void
    {
        $this->get(route('password.reset', ['token' => 'test-token']))->assertOk();
        $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'persona@example.test']))
            ->assertOk()->assertSee('persona@example.test');
    }

    public function test_invalid_array_email_does_not_break_the_forgot_password_form_after_validation(): void
    {
        $this->from(route('password.request'))->followingRedirects()
            ->post(route('password.email'), ['email' => ['unexpected']])
            ->assertOk()->assertSee('Recuperar contraseña');
    }

    public function test_invalid_array_email_does_not_break_the_reset_form_after_validation(): void
    {
        $url = route('password.reset', ['token' => 'test-token', 'email' => 'persona@example.test']);
        $this->from($url)->followingRedirects()->post(route('password.update'), [
            'token' => 'test-token',
            'email' => ['unexpected'],
            'password' => 'NuevaClave!2026Segura',
            'password_confirmation' => 'NuevaClave!2026Segura',
        ])->assertOk()->assertSee('Establecer contraseña');
    }

    public static function malformedAccessForms(): array
    {
        return [
            'staff email' => ['login.submit', ['email' => ['unexpected'], 'password' => 'irrelevant']],
            'client email' => ['cliente.login.submit', ['correo' => ['unexpected'], 'password' => 'irrelevant']],
            'registration name' => ['cliente.registro', [
                'nombre' => ['unexpected'],
                'correo' => 'nuevo@example.test',
                'password' => 'NuevaClave!2026Segura',
                'password_confirmation' => 'NuevaClave!2026Segura',
            ]],
        ];
    }

    #[DataProvider('malformedAccessForms')]
    public function test_invalid_array_fields_do_not_break_access_forms_after_validation(string $route, array $input): void
    {
        $this->reconocerPrivacidad();
        $this->from(route('login'))->followingRedirects()->post(route($route), $input)
            ->assertOk()->assertSee('Alpha Fitness');
        $this->assertDatabaseCount('clientes', 0);
    }
}

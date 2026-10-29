<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class PrivacidadAccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_aviso_precede_a_los_formularios_y_es_publico(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Tus datos, con claridad.')
            ->assertSee('privacy_read')->assertDontSee('id="form-usuario"', false);
        $this->get(route('privacidad'))->assertOk()->assertSee('Proyecto estudiantil.')
            ->assertSee('Cookies')->assertSee('Tus derechos')->assertSee('Menores');
        $this->reconocerPrivacidad();
        $this->get(route('login'))->assertOk()->assertSee('id="form-usuario"', false)->assertSee('Continuar con Google');
    }

    public function test_no_se_puede_saltar_por_post_ni_google(): void
    {
        foreach (['login.submit', 'cliente.login.submit', 'cliente.registro'] as $route) {
            $this->post(route($route))->assertRedirect(route('login'))->assertSessionHasErrors('privacy_notice');
        }
        Socialite::shouldReceive('driver')->never();
        foreach (['cliente.google', 'cliente.google.callback'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'))->assertSessionHasErrors('privacy_notice');
        }
        $this->assertGuest('web');
        $this->assertGuest('cliente');
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_requiere_confirmacion_explicita_y_version_actual(): void
    {
        $this->post(route('privacidad.continuar'), ['privacy_version' => config('privacy.version')])
            ->assertSessionHasErrors('privacy_read')->assertSessionMissing('privacy_notice');
        $this->post(route('privacidad.continuar'), ['privacy_read' => '1', 'privacy_version' => 'antigua'])
            ->assertSessionHasErrors('privacy_version')->assertSessionMissing('privacy_notice');
        $this->reconocerPrivacidad();
        config(['privacy.version' => 'nueva-version']);
        $this->post(route('cliente.login.submit'))->assertRedirect(route('login'))->assertSessionHasErrors('privacy_notice');
    }

    public function test_guarda_recibo_en_cada_rol_del_personal(): void
    {
        foreach (['Administrador', 'Secretaria', 'Entrenador'] as $role) {
            $user = User::factory()->create(['rol' => $role, 'password' => 'Clave!2026Segura', 'activo' => true]);
            $this->reconocerPrivacidad();
            $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'Clave!2026Segura'])
                ->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user, 'web');
            $this->assertSame(config('privacy.version'), $user->fresh()->privacy_notice_version);
            $this->assertNotNull($user->fresh()->privacy_notice_read_at);
            $this->post(route('logout'))->assertRedirect(route('login'))->assertSessionMissing('privacy_notice');
        }
    }

    public function test_registro_y_acceso_cliente_guardan_recibo_sin_exponerlo_a_mass_assignment(): void
    {
        $this->reconocerPrivacidad();
        $this->post(route('cliente.registro'), ['nombre' => 'Prueba', 'correo' => 'privacy@example.test',
            'password' => 'Clave!2026Segura', 'password_confirmation' => 'Clave!2026Segura',
            'privacy_notice_version' => 'falsificada'])->assertRedirect(route('cliente.dashboard'));
        $client = Cliente::where('correo', 'privacy@example.test')->firstOrFail();
        $this->assertSame(config('privacy.version'), $client->privacy_notice_version);
        $this->post(route('cliente.logout'));
        $this->reconocerPrivacidad();
        $this->post(route('cliente.login.submit'), ['correo' => $client->correo, 'password' => 'Clave!2026Segura'])
            ->assertRedirect(route('cliente.dashboard'));
        $this->assertAuthenticatedAs($client, 'cliente');
        $this->assertNotNull($client->fresh()->privacy_notice_read_at);
    }
}

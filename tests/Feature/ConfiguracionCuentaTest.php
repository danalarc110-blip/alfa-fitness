<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Services\ImagenSegura;
use App\Support\Apariencia;
use App\Support\InformacionGimnasio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionCuentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_account_settings_are_available_to_every_role(): void
    {
        foreach (['Administrador', 'Secretaria', 'Entrenador', 'Cliente'] as $rol) {
            $guard = $rol === 'Cliente' ? 'cliente' : 'web';
            $user = $guard === 'web'
                ? User::factory()->create(['rol' => $rol])
                : Cliente::create(['nombre' => 'Miembro', 'correo' => 'cuenta@example.test', 'activo' => true]);

            $response = $this->actingAs($user, $guard)->get(route('configuracion'));
            $response->assertOk()->assertSee('Acerca de Alpha Fitness')->assertSee('Apex Logic')->assertSee('href="#acerca-de"', false)
                ->assertSee('Mi perfil')->assertSee('Apariencia')->assertSee('Seguridad')
                ->assertSee(route('privacidad'))->assertSee(route('informacion'));
            if ($rol === 'Administrador') {
                $response->assertSee('Guardar información del gimnasio');
            } else {
                $response->assertDontSee('Guardar información del gimnasio');
            }
            $this->post(route($guard === 'web' ? 'logout' : 'cliente.logout'));
        }
    }

    public function test_guests_cannot_read_settings_or_remove_photos(): void
    {
        $this->get(route('configuracion'))->assertRedirect(route('login'));
        $this->deleteJson(route('configuracion.avatar.eliminar'))->assertUnauthorized();
    }

    public function test_accounts_can_remove_only_their_own_photo_and_retry(): void
    {
        $other = User::factory()->create(['avatar' => 'web_other.webp']);
        foreach (['web', 'cliente'] as $guard) {
            $user = $guard === 'web'
                ? User::factory()->create()
                : Cliente::create(['nombre' => 'Miembro', 'correo' => 'foto@example.test', 'activo' => true]);
            $photo = $guard.'_'.$user->id.'_test.webp';
            $user->update(['avatar' => $photo]);
            $this->mock(ImagenSegura::class, function ($mock) use ($guard, $user, $photo) {
                $mock->shouldReceive('eliminar')->once()->with($photo, 'avatars', $guard.'_'.$user->id);
                $mock->shouldReceive('eliminar')->once()->with(null, 'avatars', $guard.'_'.$user->id);
            });

            $this->actingAs($user, $guard)->from(route('configuracion'))
                ->delete(route('configuracion.avatar.eliminar'), ['user_id' => $other->id, 'avatar' => $other->avatar])
                ->assertRedirect(route('configuracion'))->assertSessionHas('status');
            $this->assertNull($user->fresh()->avatar);
            $this->assertSame('web_other.webp', $other->fresh()->avatar);
            $this->delete(route('configuracion.avatar.eliminar'))->assertRedirect();
            $this->post(route($guard === 'web' ? 'logout' : 'cliente.logout'));
        }
    }

    public function test_dual_session_removes_client_photo_without_changing_employee(): void
    {
        $employee = User::factory()->create(['avatar' => 'employee.webp']);
        $client = Cliente::create(['nombre' => 'Cliente', 'correo' => 'dual-foto@example.test', 'activo' => true, 'avatar' => 'client.webp']);
        $this->mock(ImagenSegura::class, function ($mock) use ($client) {
            $mock->shouldReceive('eliminar')->once()->with('client.webp', 'avatars', 'cliente_'.$client->id);
        });

        $this->actingAs($employee, 'web')->actingAs($client, 'cliente')
            ->delete(route('configuracion.avatar.eliminar'))->assertRedirect();
        $this->assertNull($client->fresh()->avatar);
        $this->assertSame('employee.webp', $employee->fresh()->avatar);
    }

    public function test_unconfigured_google_does_not_offer_a_dead_link(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        $user = Cliente::create(['nombre' => 'Miembro', 'correo' => 'google-config@example.test', 'password' => 'PruebaSegura123!', 'activo' => true]);
        $this->actingAs($user, 'cliente')->get(route('configuracion'))->assertOk()
            ->assertSee('El acceso con Google aún no está habilitado.')
            ->assertDontSee('Continuar con Google');
    }

    public function test_invalid_array_input_shows_errors_without_breaking_settings(): void
    {
        $user = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($user)->from(route('configuracion'))->followingRedirects()
            ->post(route('configuracion.perfil'), ['nombre' => ['valor inesperado']])
            ->assertOk()->assertSee('Revisa los siguientes datos:')->assertSee($user->name);

        $datos = InformacionGimnasio::predeterminada();
        foreach (array_keys($datos) as $campo) {
            $datos[$campo] = ['valor inesperado'];
        }
        $this->from(route('configuracion'))->followingRedirects()
            ->post(route('configuracion.gimnasio'), ['gimnasio' => $datos])
            ->assertOk()->assertSee('Revisa los siguientes datos:')->assertSee('Guardar información del gimnasio');

        $colors = array_fill_keys(array_keys(Apariencia::PALETAS['light']), ['valor inesperado']);
        $this->from(route('configuracion'))->followingRedirects()
            ->post(route('configuracion.apariencia'), ['design' => ['invalid'], 'mode' => ['invalid'], 'motion' => ['invalid'], 'colors' => $colors])
            ->assertOk()->assertSee('Revisa los siguientes datos:')->assertSee('Guardar apariencia');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertNull($user->fresh()->apariencia);
        $this->assertDatabaseCount('configuracion_gimnasio', 0);
    }
}

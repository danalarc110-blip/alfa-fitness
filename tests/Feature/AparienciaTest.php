<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Apariencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AparienciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_write_preferences(): void
    {
        $this->postJson(route('configuracion.apariencia'), [])->assertUnauthorized();
    }

    public function test_each_mode_is_persisted_for_both_account_types(): void
    {
        $users = [
            'web' => User::factory()->create(),
            'cliente' => Cliente::create(['nombre' => 'Cliente', 'correo' => 'tema@example.test', 'activo' => true]),
        ];
        foreach ($users as $guard => $user) {
            $this->actingAs($user, $guard);
            foreach (['light', 'dark', 'custom'] as $mode) {
                $colors = Apariencia::PALETAS['dark'];
                $colors['accent'] = '#aabbcc';
                $this->postJson(route('configuracion.apariencia'), compact('mode', 'colors'))->assertOk();
                $this->assertEquals(compact('mode', 'colors') + ['design' => 'elegant'], $user->fresh()->apariencia);
                $this->get('/configuracion')->assertOk()->assertSee('Vista previa de la apariencia');
            }
            $this->post(route('logout'));
            $this->assertSame('custom', $user->fresh()->apariencia['mode']);
        }
    }

    public function test_invalid_colors_contrast_and_extra_keys_are_rejected_without_mutation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach ([
            ['primary' => 'red; background:url(https://example.test)'],
            ['text' => '#ffffff'],
            ['background' => '#18212f'],
            ['surface' => '#18212f'],
            ['extra' => '#000000'],
        ] as $invalid) {
            $this->postJson(route('configuracion.apariencia'), ['mode' => 'custom', 'colors' => array_merge(Apariencia::PALETAS['light'], $invalid)])->assertUnprocessable();
            $this->assertNull($user->fresh()->apariencia);
        }
        $this->postJson(route('configuracion.apariencia'), ['mode' => 'other', 'colors' => Apariencia::PALETAS['light']])->assertUnprocessable();
    }

    public function test_ids_and_roles_in_payload_never_change_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->postJson(route('configuracion.apariencia'), ['mode' => 'dark', 'colors' => Apariencia::PALETAS['light'], 'user_id' => $other->id, 'rol' => 'Administrador'])->assertOk();
        $this->assertNull($other->fresh()->apariencia);
        $this->assertSame('Secretaria', $user->fresh()->rol);
    }

    public function test_design_is_independent_of_mode_and_persists_for_both_guards(): void
    {
        foreach (['web', 'cliente'] as $guard) {
            $user = $guard === 'web' ? User::factory()->create() : Cliente::create(['nombre' => 'Cliente verde', 'correo' => 'verde@example.test', 'activo' => true]);
            $this->actingAs($user, $guard);
            foreach (Apariencia::DISENOS as $design) {
                foreach (['light', 'dark', 'custom'] as $mode) {
                    $colors = Apariencia::PALETAS['light'];
                    $this->postJson(route('configuracion.apariencia'), compact('design', 'mode', 'colors'))->assertOk()->assertJsonPath('appearance.design', $design);
                    $this->assertEquals(compact('design', 'mode', 'colors'), Apariencia::preferencia($user->fresh()));
                    $this->get('/configuracion')->assertOk()->assertSee('Elegante')->assertSee('Verde');
                }
            }
            $this->post(route($guard === 'web' ? 'logout' : 'cliente.logout'));
        }
    }

    public function test_legacy_preferences_get_elegant_without_losing_custom_colors(): void
    {
        $colors = Apariencia::PALETAS['dark'];
        $user = User::factory()->create(['apariencia' => ['mode' => 'custom', 'colors' => $colors]]);
        $this->assertEquals(['design' => 'elegant', 'mode' => 'custom', 'colors' => $colors], Apariencia::preferencia($user));
        $this->actingAs($user)->postJson(route('configuracion.apariencia'), ['design' => 'green', 'mode' => 'custom', 'colors' => $colors])->assertOk();
        $this->postJson(route('configuracion.apariencia'), ['mode' => 'dark', 'colors' => $colors])->assertOk()->assertJsonPath('appearance.design', 'green');
        $this->postJson(route('configuracion.apariencia'), ['design' => 'url(unsafe)', 'mode' => 'dark', 'colors' => $colors])->assertUnprocessable();
        $this->assertSame('green', $user->fresh()->apariencia['design']);
    }
}

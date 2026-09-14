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
                $this->assertSame(compact('mode', 'colors'), $user->fresh()->apariencia);
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
}

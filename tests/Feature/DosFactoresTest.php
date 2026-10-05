<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DosFactoresTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_rfc6238_sha1_test_vectors_including_64_bit_time(): void
    {
        $totp = new Totp;
        foreach ([59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471', 1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130'] as $time => $code) {
            $this->assertSame($code, $totp->code(self::SECRET, intdiv($time, 30), 8));
        }
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/D', $totp->secret());
    }

    public function test_enabled_admin_cannot_access_after_first_factor_or_replay_a_code(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador', 'password' => 'Fuerte!2026Clave']);
        $admin->forceFill(['two_factor_secret' => self::SECRET, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => []])->save();
        $this->post(route('login.submit'), ['email' => $admin->email, 'password' => 'Fuerte!2026Clave', 'remember' => 1])->assertRedirect(route('dos-factores.desafio'));
        $this->assertGuest('web');
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $codigo = app(Totp::class)->code(self::SECRET, intdiv(now()->timestamp, 30));
        $this->post(route('dos-factores.verificar'), ['codigo' => $codigo])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('dashboard'))->assertOk();
        $this->post(route('logout'));
        $this->post(route('login.submit'), ['email' => $admin->email, 'password' => 'Fuerte!2026Clave'])->assertRedirect(route('dos-factores.desafio'));
        $this->post(route('dos-factores.verificar'), ['codigo' => $codigo])->assertSessionHasErrors('codigo');
        $this->assertGuest('web');
    }

    public function test_recovery_code_is_single_use_and_password_change_expires_pending_login(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador', 'password' => 'Fuerte!2026Clave']);
        $recovery = 'abcd-1234-abcd-5678';
        $admin->forceFill(['two_factor_secret' => self::SECRET, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => [hash('sha256', $recovery)]])->save();
        $this->post(route('login.submit'), ['email' => $admin->email, 'password' => 'Fuerte!2026Clave']);
        $this->post(route('dos-factores.verificar'), ['codigo' => $recovery])->assertRedirect(route('dashboard'));
        $this->assertSame([], $admin->fresh()->two_factor_recovery_codes);
        $this->assertStringNotContainsString(self::SECRET, $admin->getRawOriginal('two_factor_secret'));
        $this->assertArrayNotHasKey('two_factor_secret', $admin->toArray());
        $this->post(route('logout'));
        $this->post(route('login.submit'), ['email' => $admin->email, 'password' => 'Fuerte!2026Clave']);
        $admin->update(['password' => 'Nueva!2026Clave']);
        $this->post(route('dos-factores.verificar'), ['codigo' => $recovery])->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    public function test_setup_requires_password_and_confirmation_before_enabling(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador', 'password' => 'Fuerte!2026Clave']);
        $this->actingAs($admin)->post(route('dos-factores.preparar'), ['password_actual' => 'incorrecta'])->assertSessionHasErrors('password_actual');
        $this->assertNull($admin->fresh()->two_factor_secret);
        $this->post(route('dos-factores.preparar'), ['password_actual' => 'Fuerte!2026Clave'])->assertSessionHasNoErrors();
        $setup = session('two_factor_setup');
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);
        $codigo = app(Totp::class)->code($setup['secret'], intdiv(now()->timestamp, 30));
        $this->post(route('dos-factores.activar'), ['password_actual' => 'Fuerte!2026Clave', 'codigo' => $codigo])->assertSessionHasNoErrors();
        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
        $this->assertCount(8, $admin->fresh()->two_factor_recovery_codes);
        $this->get(route('dos-factores.configurar'))->assertOk();
    }

    public function test_existing_unverified_session_is_revoked_after_enabling_two_factor(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $admin->forceFill(['two_factor_secret' => self::SECRET, 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest('web');
    }
}

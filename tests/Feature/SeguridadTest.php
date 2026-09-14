<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo = 'cliente@prueba.test'): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente', 'correo' => $correo, 'password' => 'Password!123', 'activo' => true]);
    }

    public function test_login_is_rate_limited_and_inactive_session_is_revoked(): void
    {
        $user = User::factory()->create(['password' => 'Password!123']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertStatus(429);
        $inactivo = User::factory()->create(['activo' => false]);
        $this->actingAs($inactivo)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    public function test_clients_self_register_with_a_strong_password(): void
    {
        $this->post(route('cliente.registro'), ['nombre' => 'Debil', 'correo' => 'debil@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertSessionHasErrors('password');
        $this->post(route('cliente.registro'), ['nombre' => 'Fuerte', 'correo' => 'fuerte@example.com', 'password' => 'Fuerte!2026Clave', 'password_confirmation' => 'Fuerte!2026Clave'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clientes', ['correo' => 'fuerte@example.com', 'activo' => true]);
    }

    public function test_only_one_admin_is_possible_and_the_existing_admin_is_protected(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        try {
            User::create(['name' => 'Segundo', 'email' => 'segundo@example.com', 'password' => 'Fuerte!2026Clave', 'rol' => 'Administrador', 'activo' => true]);
            $this->fail('La restriccion unica no se aplico.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
        $this->expectException(\LogicException::class);
        $admin->delete();
    }

    public function test_secretary_cannot_open_or_change_account_management(): void
    {
        $cliente = $this->cliente();
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->get(route('cuentas.index'))->assertForbidden();
        $this->patch(route('cuentas.banear', $cliente))->assertForbidden();
        $this->assertTrue($cliente->fresh()->activo);
    }

    public function test_staff_cannot_access_private_progress_even_with_direct_requests(): void
    {
        foreach (['Administrador', 'Secretaria', 'Entrenador'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]));
            $this->get(route('progreso.index', ['cliente_id' => 999]))->assertForbidden();
            $this->post(route('progreso.store'), ['cliente_id' => 999])->assertForbidden();
        }
    }

    public function test_passwords_are_hidden_from_serialization_and_profile_ignores_role_injection(): void
    {
        $user = User::factory()->create(['rol' => 'Secretaria']);
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->actingAs($user)->post('/configuracion/perfil', ['nombre' => 'Nombre nuevo', 'rol' => 'Administrador'])->assertSessionHasNoErrors();
        $this->assertSame('Secretaria', $user->fresh()->rol);
        $this->post('/configuracion/password', ['password_actual' => 'secreto-actual', 'password' => 'x'])->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password_actual');
    }
}

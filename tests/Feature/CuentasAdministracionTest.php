<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CuentasAdministracionTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(array $attributes = []): Cliente
    {
        return Cliente::create(array_merge([
            'nombre' => 'Cliente de prueba',
            'correo' => 'cliente@example.test',
            'password' => 'Fuerte!2026Clave',
            'activo' => true,
        ], $attributes));
    }

    public function test_only_the_administrator_can_open_or_mutate_account_management(): void
    {
        $cliente = $this->cliente();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);

        $this->actingAs($admin)
            ->get(route('cuentas.index'))
            ->assertOk();

        $this->actingAs($secretaria)
            ->get(route('cuentas.index'))
            ->assertForbidden();
        $this->patch(route('cuentas.banear', $cliente))->assertForbidden();
        $this->patch(route('cuentas.restaurar', $cliente))->assertForbidden();

        auth('web')->logout();
        $this->actingAs($cliente, 'cliente')
            ->get(route('cuentas.index'))
            ->assertForbidden();
        $this->patch(route('cuentas.banear', $cliente))->assertForbidden();
        $this->patch(route('cuentas.restaurar', $cliente))->assertForbidden();
    }

    public function test_index_only_searches_clients_and_shows_registration_date_and_status(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $objetivo = $this->cliente([
            'nombre' => 'Cliente Objetivo',
            'correo' => 'objetivo@example.test',
        ]);
        $objetivo->forceFill(['created_at' => '2026-08-03 14:25:00'])->save();
        $objetivoInactivo = $this->cliente([
            'nombre' => 'Cliente Objetivo Inactivo',
            'correo' => 'objetivo-inactivo@example.test',
            'activo' => false,
        ]);
        $this->cliente([
            'nombre' => 'Cliente Distinto',
            'correo' => 'distinto@example.test',
        ]);
        User::factory()->create([
            'name' => 'Entrenador Objetivo',
            'email' => 'entrenador-objetivo@example.test',
            'rol' => 'Entrenador',
        ]);

        $response = $this->actingAs($admin)->get(route('cuentas.index', [
            'q' => 'Objetivo',
            'tipo' => 'personal',
        ]));

        $response->assertOk()
            ->assertSee('Cliente Objetivo')
            ->assertSee('datetime="2026-08-03"', false)
            ->assertSee('Activo')
            ->assertSee('Baneado')
            ->assertSee(route('cuentas.banear', $objetivo), false)
            ->assertSee(route('cuentas.restaurar', $objetivoInactivo), false)
            ->assertDontSee('objetivo@example.test')
            ->assertDontSee('Cliente Distinto')
            ->assertDontSee('Entrenador Objetivo')
            ->assertDontSee('entrenador-objetivo@example.test')
            ->assertDontSee('Registrar empleado')
            ->assertDontSee('Editar cuenta')
            ->assertDontSee('Todos los estados');

        $this->get(route('cuentas.index', ['q' => 'objetivo@example.test']))
            ->assertOk()
            ->assertSee('Cliente Objetivo');
    }

    public function test_legacy_account_mutation_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('cuentas.store'));
        $this->assertFalse(Route::has('cuentas.update'));
        $this->assertFalse(Route::has('cuentas.invitar'));
        $this->assertTrue(Route::has('cuentas.banear'));
        $this->assertTrue(Route::has('cuentas.restaurar'));
    }

    public function test_explicit_actions_only_change_access_and_ignore_identity_injection(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->cliente();
        $nombreOriginal = $cliente->nombre;
        $correoOriginal = $cliente->correo;

        $this->actingAs($admin)
            ->patch(route('cuentas.banear', $cliente), [
                'nombre' => 'Nombre inyectado',
                'correo' => 'inyectado@example.test',
                'rol' => 'Administrador',
                'activo' => true,
            ])
            ->assertRedirect();

        $cliente->refresh();
        $this->assertFalse($cliente->activo);
        $this->assertNotNull($cliente->baneado_en);
        $this->assertSame($admin->id, $cliente->baneado_por);
        $this->assertSame($nombreOriginal, $cliente->nombre);
        $this->assertSame($correoOriginal, $cliente->correo);

        $this->patch(route('cuentas.restaurar', $cliente), [
            'nombre' => 'Otro nombre inyectado',
            'correo' => 'otro@example.test',
            'rol' => 'Administrador',
            'activo' => false,
        ])->assertRedirect();

        $cliente->refresh();
        $this->assertTrue($cliente->activo);
        $this->assertNull($cliente->baneado_en);
        $this->assertNull($cliente->baneado_por);
        $this->assertSame($nombreOriginal, $cliente->nombre);
        $this->assertSame($correoOriginal, $cliente->correo);
    }

    public function test_ban_blocks_login_and_revokes_an_authenticated_client_on_the_next_request(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->cliente();

        $this->actingAs($admin)
            ->patch(route('cuentas.banear', $cliente))
            ->assertRedirect();
        $this->assertFalse($cliente->fresh()->activo);

        // actingAs represents a browser session that was authenticated before the ban.
        $this->actingAs($cliente->fresh(), 'cliente')
            ->get(route('cliente.dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest('cliente');

        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo,
            'password' => 'Fuerte!2026Clave',
        ])->assertSessionHasErrors('correo');
        $this->assertGuest('cliente');
    }

    public function test_restoring_a_client_allows_login_again(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->cliente(['activo' => false]);

        $this->actingAs($admin)
            ->patch(route('cuentas.restaurar', $cliente))
            ->assertRedirect();
        $this->assertTrue($cliente->fresh()->activo);

        auth('web')->logout();
        $this->post(route('cliente.login.submit'), [
            'correo' => $cliente->correo,
            'password' => 'Fuerte!2026Clave',
        ])->assertRedirect(route('cliente.dashboard'));
        $this->assertAuthenticatedAs($cliente->fresh(), 'cliente');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MatrizRolesTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return [
            'administrador' => ['Administrador'],
            'secretaria' => ['Secretaria'],
            'entrenador' => ['Entrenador'],
            'cliente' => ['Cliente'],
        ];
    }

    private function iniciarSesion(string $rol): void
    {
        if ($rol === 'Cliente') {
            $this->actingAs(Cliente::create([
                'nombre' => 'Cliente de la matriz', 'correo' => 'matriz@example.test', 'activo' => true,
            ]), 'cliente');

            return;
        }

        $this->actingAs(User::factory()->create(['rol' => $rol]), 'web');
    }

    #[DataProvider('roles')]
    public function test_each_role_has_exactly_the_expected_module_access(string $rol): void
    {
        $this->iniciarSesion($rol);
        $todos = ['Administrador', 'Secretaria', 'Entrenador', 'Cliente'];
        $matriz = [
            'configuracion' => $todos,
            'entrenamientos.index' => $todos,
            'entrenamientos.historial' => $todos,
            'ejercicios.index' => $todos,
            'entrenadores.index' => $todos,
            'productos.index' => $todos,
            'cuentas.index' => ['Administrador'],
            'analitica.index' => ['Administrador'],
            'asistencia.index' => ['Administrador', 'Secretaria'],
            'ventas.index' => ['Administrador', 'Secretaria'],
            'membresias.index' => ['Administrador', 'Secretaria', 'Cliente'],
            'entrenador.clientes' => ['Administrador', 'Entrenador'],
            'progreso.index' => ['Cliente'],
        ];
        foreach ($matriz as $ruta => $permitidos) {
            $this->get(route($ruta))->assertStatus(in_array($rol, $permitidos, true) ? 200 : 403);
        }
        $this->get(route($rol === 'Cliente' ? 'cliente.dashboard' : 'dashboard'))->assertOk();
        foreach (['informacion', 'privacidad'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
        if ($rol === 'Cliente') {
            $this->get(route('cliente.asistencia'))->assertOk();
        } else {
            $this->get(route('cliente.asistencia'))->assertRedirect(route('login'));
        }
    }

    #[DataProvider('roles')]
    public function test_direct_write_requests_cannot_borrow_another_roles_permissions(string $rol): void
    {
        $this->iniciarSesion($rol);
        $operaciones = [
            'entrenadores.store' => ['Administrador'],
            'planes.store' => ['Administrador'],
            'configuracion.gimnasio' => ['Administrador'],
            'productos.store' => ['Administrador', 'Secretaria'],
            'ventas.store' => ['Administrador', 'Secretaria'],
            'asistencia.store' => ['Administrador', 'Secretaria'],
            'progreso.store' => ['Cliente'],
            'membresias.solicitar' => ['Cliente'],
        ];
        foreach ($operaciones as $ruta => $permitidos) {
            if (! in_array($rol, $permitidos, true)) {
                $this->postJson(route($ruta), [])->assertForbidden();
            }
        }
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('membresias', 0);
        $this->assertDatabaseCount('configuracion_gimnasio', 0);
    }

    public function test_disabling_either_account_type_revokes_its_existing_session(): void
    {
        foreach (['web', 'cliente'] as $guard) {
            $user = $guard === 'web'
                ? User::factory()->create(['rol' => 'Entrenador'])
                : Cliente::create(['nombre' => 'Cliente desactivado', 'correo' => 'inactivo-matriz@example.test', 'activo' => true]);
            $this->actingAs($user, $guard);
            $user->update(['activo' => false]);

            $this->getJson(route('configuracion'))->assertForbidden();
            $this->assertGuest('web');
            $this->assertGuest('cliente');
        }
    }
}

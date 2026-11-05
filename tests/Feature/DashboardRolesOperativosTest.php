<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardRolesOperativosTest extends TestCase
{
    use RefreshDatabase;

    public static function rolesDeGestion(): array
    {
        return [['Administrador'], ['Secretaria']];
    }

    #[DataProvider('rolesDeGestion')]
    public function test_management_home_prioritizes_expiring_memberships_over_personal_routine_creation(string $rol): void
    {
        $user = User::factory()->create(['rol' => $rol]);
        $cliente = Cliente::create(['nombre' => 'Socio Por Vencer', 'correo' => 'vence@example.test', 'activo' => true]);
        Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
            'inicio' => today()->subDays(27), 'fin' => today()->addDays(2), 'cancelada' => false,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Membresías por vencer')
            ->assertSee($cliente->nombre)
            ->assertSee(route('membresias.index', ['q' => $cliente->nombre]), false)
            ->assertDontSee('action="'.route('entrenamientos.crear').'"', false);
    }

    public function test_training_roles_keep_their_personal_routine_creation_action(): void
    {
        $trainer = User::factory()->create(['rol' => 'Entrenador']);
        $this->actingAs($trainer)->get(route('dashboard'))->assertOk()
            ->assertSee('Mis rutinas')->assertSee('action="'.route('entrenamientos.crear').'"', false)
            ->assertDontSee('Membresías por vencer');

        auth('web')->logout();
        $cliente = Cliente::create(['nombre' => 'Socio Atleta', 'correo' => 'entrena@example.test', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('cliente.dashboard'))->assertOk()
            ->assertSee('Mis rutinas')->assertSee('action="'.route('entrenamientos.crear').'"', false)
            ->assertDontSee('Membresías por vencer');
    }
}

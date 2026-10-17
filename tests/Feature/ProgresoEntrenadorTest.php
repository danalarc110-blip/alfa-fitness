<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgresoEntrenadorTest extends TestCase
{
    use RefreshDatabase;

    private function crearCliente(array $attrs = []): Cliente
    {
        static $counter = 1;
        $counter++;

        return Cliente::create(array_merge([
            'nombre' => "Cliente {$counter}",
            'correo' => "cliente{$counter}@fitness.test",
            'password' => 'Password!123',
            'activo' => true,
        ], $attrs));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertRedirect(route('login'));
    }

    public function test_cliente_cannot_access_coach_progress_route(): void
    {
        $cliente = $this->crearCliente();
        $otroCliente = $this->crearCliente();

        $response = $this->actingAs($cliente, 'cliente')
            ->get(route('entrenador.cliente.progreso', $otroCliente));

        $response->assertForbidden();
    }

    public function test_administrador_can_view_any_client_progress(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->crearCliente(['nombre' => 'Pedro Atleta']);
        $ejercicio = Ejercicio::create([
            'nombre' => 'Sentadilla Profunda',
            'grupo_muscular' => 'Piernas',
            'activo' => true,
        ]);
        PersonalRecord::create([
            'cliente_id' => $cliente->id,
            'ejercicio_id' => $ejercicio->id,
            'peso_kg' => 120,
            'repeticiones' => 5,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertOk();
        $response->assertSee('Progreso: Pedro Atleta');
        $response->assertSee('Sentadilla Profunda');
        $response->assertSee('120');
    }

    public function test_coach_can_view_assigned_client_progress(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Coach Carlos']);
        $cliente = $this->crearCliente(['nombre' => 'Laura Alumna']);

        // Assign a routine
        Rutina::create([
            'user_id' => $cliente->id,
            'user_type' => 'cliente',
            'nombre' => 'Fuerza 5x5',
            'objetivo' => 'Ganar fuerza',
            'nivel' => 'Intermedio',
            'asignado_por' => 'Coach Carlos',
            'asignado_por_id' => $coach->id,
            'dias_por_semana' => 3,
        ]);

        $response = $this->actingAs($coach, 'web')
            ->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertOk();
        $response->assertSee('Progreso: Laura Alumna');
    }

    public function test_coach_is_forbidden_if_not_assigned_to_client(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Coach Carlos']);
        $cliente = $this->crearCliente();

        $response = $this->actingAs($coach, 'web')
            ->get(route('entrenador.cliente.progreso', $cliente));

        $response->assertForbidden();
    }

    private function asignar(Cliente $cliente, User $coach, array $attrs = []): Rutina
    {
        return Rutina::create(array_merge([
            'user_id' => $cliente->id, 'user_type' => 'cliente', 'asignado_por_id' => $coach->id,
            'nombre' => 'Rutina propia', 'objetivo' => 'Fuerza', 'nivel' => 'Intermedio', 'dias_por_semana' => 3,
        ], $attrs));
    }

    public function test_my_clients_deduplicates_searches_and_only_shows_own_assignments(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $otroCoach = User::factory()->create(['rol' => 'Entrenador']);
        $ana = $this->crearCliente(['nombre' => 'Ana Vinculada']);
        $luis = $this->crearCliente(['nombre' => 'Luis Vinculado']);
        $ajeno = $this->crearCliente(['nombre' => 'Cliente Ajeno']);
        $colision = $this->crearCliente(['nombre' => 'Identidad Web']);
        $this->asignar($ana, $coach);
        $this->asignar($ana, $coach, ['nombre' => 'Rutina archivada propia', 'activa' => false]);
        $this->asignar($ana, $otroCoach, ['nombre' => 'Rutina de otro entrenador']);
        $this->asignar($luis, $coach);
        $this->asignar($ajeno, $otroCoach);
        $this->asignar($colision, $coach, ['user_type' => 'web']);

        $this->actingAs($coach, 'web')->get(route('entrenador.clientes'))
            ->assertOk()->assertSee('Mis clientes')->assertSee('Ana Vinculada')->assertSee('Luis Vinculado')
            ->assertSee('Rutina archivada propia')->assertDontSee('Cliente Ajeno')->assertDontSee('Identidad Web')
            ->assertDontSee('Rutina de otro entrenador')
            ->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 2 && $clientes->firstWhere('id', $ana->id)->rutinas->count() === 2);
        $this->get(route('entrenador.clientes', ['q' => 'Ana']))
            ->assertOk()->assertSee('Ana Vinculada')->assertDontSee('Luis Vinculado')
            ->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 1);
        $this->get(route('entrenador.clientes', ['q' => 'inexistente']))->assertOk()->assertSee('No se encontraron clientes');
    }

    public function test_client_details_show_assigned_routines_and_link_existing_progress(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $otroCoach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->crearCliente(['nombre' => 'Ana Atleta']);
        $propia = $this->asignar($cliente, $coach, ['nombre' => 'Plan fuerza propio']);
        $this->asignar($cliente, $otroCoach, ['nombre' => 'Plan privado de otro entrenador']);
        $ejercicio = Ejercicio::create(['nombre' => 'Sentadilla de seguimiento', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $dia = $propia->dias()->create(['orden' => 1, 'titulo' => 'Fuerza de piernas']);
        $dia->ejercicios()->create(['ejercicio_id' => $ejercicio->id, 'orden' => 1, 'series' => 4, 'repeticiones' => '8', 'peso' => 40, 'descanso_segundos' => 60]);

        $this->actingAs($coach, 'web')->get(route('entrenador.cliente', $cliente))
            ->assertOk()->assertSee('Rutinas de Ana Atleta')->assertSee('Plan fuerza propio')->assertSee('Sentadilla de seguimiento')
            ->assertDontSee('Plan privado de otro entrenador')->assertSee(route('entrenador.cliente.progreso', $cliente));
        $this->get(route('entrenador.cliente.progreso', $cliente))->assertOk();
        $this->get(route('ventas.index'))->assertForbidden();
        $this->get(route('analitica.index'))->assertForbidden();
    }

    public function test_wrong_guard_assignment_and_other_coach_never_authorize_direct_urls(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $otroCoach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->crearCliente();
        $this->asignar($cliente, $coach, ['user_type' => 'web']);
        $this->asignar($cliente, $otroCoach);
        $this->actingAs($coach, 'web')->get(route('entrenador.cliente', $cliente))->assertForbidden();
        $this->get(route('entrenador.cliente.progreso', $cliente))->assertForbidden();
        $this->get(route('entrenador.clientes'))
            ->assertOk()->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 0);
    }

    public function test_removing_last_assignment_revokes_all_client_views(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->crearCliente();
        $rutina = $this->asignar($cliente, $coach);
        $this->actingAs($coach, 'web')->get(route('entrenador.cliente', $cliente))->assertOk();
        $rutina->delete();
        $this->get(route('entrenador.clientes'))->assertOk()->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 0);
        $this->get(route('entrenador.cliente', $cliente))->assertForbidden();
        $this->get(route('entrenador.cliente.progreso', $cliente))->assertForbidden();
    }

    public function test_administrator_keeps_access_to_clients_without_assignments(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->crearCliente();
        $this->actingAs($admin, 'web')->get(route('entrenador.clientes'))->assertOk()
            ->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 1);
        $this->get(route('entrenador.cliente', $cliente))->assertOk()->assertSee('Sin rutinas asignadas.');
        $this->get(route('entrenador.cliente.progreso', $cliente))->assertOk();
    }

    public function test_secretary_and_client_cannot_access_my_clients_or_details(): void
    {
        $cliente = $this->crearCliente();
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria, 'web')->get(route('entrenador.clientes'))->assertForbidden();
        $this->get(route('entrenador.cliente', $cliente))->assertForbidden();
        $this->get(route('entrenador.cliente.progreso', $cliente))->assertForbidden();
        $this->actingAs($cliente, 'cliente')->get(route('entrenador.clientes'))->assertForbidden();
        $this->get(route('entrenador.cliente', $cliente))->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardCalidadDatosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-04 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_popular_exercises_only_include_exercises_with_real_votes(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        for ($i = 0; $i < 6; $i++) {
            Ejercicio::create(['nombre' => 'Sin votos '.$i, 'grupo_muscular' => 'Pecho', 'activo' => true]);
        }
        $valorado = Ejercicio::create(['nombre' => 'Ejercicio valorado', 'grupo_muscular' => 'Pecho', 'activo' => true]);
        $valorado->calificaciones()->create(['user_id' => $coach->id, 'user_type' => 'web', 'estrellas' => 5]);

        $this->actingAs($coach)->get(route('dashboard'))->assertOk()
            ->assertViewHas('actividad', fn ($actividad) => $actividad->modelKeys() === [$valorado->id]);
    }

    public function test_popular_exercises_remain_empty_until_a_vote_is_recorded(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        Ejercicio::create(['nombre' => 'Sin votos', 'grupo_muscular' => 'Pecho', 'activo' => true]);

        $this->actingAs($coach)->get(route('dashboard'))->assertOk()
            ->assertViewHas('actividad', fn ($actividad) => $actividad->isEmpty())
            ->assertSee('No hay calificaciones de ejercicios registradas aún.');
    }

    public static function rolesDeGestion(): array
    {
        return [['Administrador'], ['Secretaria']];
    }

    #[DataProvider('rolesDeGestion')]
    public function test_expiring_memberships_show_the_five_earliest_expirations(string $rol): void
    {
        $user = User::factory()->create(['rol' => $rol]);
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'prioridad@example.test', 'activo' => true]);
        $membresias = collect();
        foreach ([7, 6, 5, 4, 3, 1] as $dias) {
            $membresias->push(Membresia::create([
                'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
                'inicio' => today()->subDays(25), 'fin' => today()->addDays($dias), 'cancelada' => false,
            ]));
        }
        $esperadas = $membresias->reverse()->take(5)->pluck('id')->all();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('porVencer', fn ($porVencer) => $porVencer->modelKeys() === array_values($esperadas));
    }

    public function test_analytics_inputs_reflect_the_resolved_month_and_ignore_unrelated_stale_range_values(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $this->actingAs($admin)->get(route('analitica.index', [
            'preset' => 'mes', 'mes' => '2026-10', 'desde' => '2026-01-01', 'hasta' => '2026-01-02',
        ]))->assertOk()->assertViewHas('desdeInput', '2026-10-01')->assertViewHas('hastaInput', '2026-10-31');
    }

    public function test_analytics_keeps_personalized_range_values_available_after_validation_errors(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->from(route('analitica.index'))->get(route('analitica.index', [
            'preset' => 'personalizado', 'desde' => '2026-10-20', 'hasta' => '2026-10-01',
        ]))->assertRedirect(route('analitica.index'))->assertSessionHasErrors('periodo');

        $this->get(route('analitica.index'))->assertOk()
            ->assertViewHas('desdeInput', '2026-10-20')->assertViewHas('hastaInput', '2026-10-01');
    }

    public function test_analytics_never_passes_malformed_old_input_arrays_into_date_fields(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->withSession(['_old_input' => [
            'preset' => 'personalizado', 'desde' => ['2026-10-01'], 'hasta' => ['2026-10-02'],
        ]])->get(route('analitica.index'))->assertOk()
            ->assertViewHas('desdeInput', '2026-11-01')->assertViewHas('hastaInput', '2026-11-30');
    }

    public function test_home_financial_form_recovers_from_invalid_structured_previous_dates(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->withSession(['_old_input' => [
            'desde' => ['unexpected'], 'hasta' => ['unexpected'],
        ]])->get(route('dashboard'))->assertOk()
            ->assertSee('value="2026-11-01"', false)->assertSee('value="2026-11-04"', false);
    }
}

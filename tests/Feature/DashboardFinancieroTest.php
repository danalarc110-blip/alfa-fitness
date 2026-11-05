<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ReporteFinancieroLog;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardFinancieroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/El_Salvador']);
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'America/El_Salvador'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_home_uses_registered_income_at_current_cutoff_and_keeps_operational_metrics(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->venta($admin, '0.10', '2026-11-01 10:00:00');
        $this->venta($admin, '0.20', '2026-11-04 12:00:00');
        $this->venta($admin, '100.00', '2026-11-04 12:00:01');
        $this->venta($admin, '200.00', '2026-11-05 10:00:00');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertViewHas('finanzas', fn ($finanzas) => $finanzas['datos']['kpis']['ingresos_totales'] === 0.3
                && $finanzas['datos']['kpis']['transacciones_totales'] === 2
                && count($finanzas['datos']['serie_diaria']) === 4
                && $finanzas['hasta'] === '2026-11-04'
                && $finanzas['mes'] === '2026-11')
            ->assertViewHas('metricas', fn ($metricas) => array_column($metricas, 0) === ['Clientes activos', 'Personal activo']);
    }

    public function test_admin_can_select_a_calendar_month_and_compare_the_previous_equal_duration(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->venta($admin, '40.00', '2026-10-31 23:59:59');
        $this->venta($admin, '10.00', '2026-09-30 10:00:00');
        $this->venta($admin, '999.00', '2026-11-01 10:00:00');

        foreach (['dashboard', 'analitica.index'] as $route) {
            $response = $this->actingAs($admin)->get(route($route, ['preset' => 'mes', 'mes' => '2026-10']))->assertOk();
            $datos = $route === 'dashboard' ? $response->viewData('finanzas')['datos'] : $response->viewData('datos');
            $this->assertSame('01/10/2026', $datos['periodo']['desde']);
            $this->assertSame('31/10/2026', $datos['periodo']['hasta']);
            $this->assertSame(40.0, $datos['kpis']['ingresos_totales']);
            $this->assertSame(10.0, $datos['kpis']['prev_ingresos_totales']);
            $this->assertSame(300.0, $datos['kpis']['variacion_porcentual']);
            $this->assertCount(31, $datos['serie_diaria']);
        }
    }

    public function test_admin_can_select_one_day_with_inclusive_boundaries_and_zero_comparison_base(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->venta($admin, '10.00', '2026-10-10 00:00:00');
        $this->venta($admin, '20.00', '2026-10-10 23:59:59');
        $this->venta($admin, '999.00', '2026-10-11 00:00:00');

        $this->actingAs($admin)->get(route('dashboard', ['preset' => 'personalizado', 'desde' => '2026-10-10', 'hasta' => '2026-10-10']))
            ->assertOk()->assertViewHas('finanzas', fn ($finanzas) => $finanzas['desde'] === '2026-10-10'
                && $finanzas['hasta'] === '2026-10-10'
                && $finanzas['datos']['kpis']['ingresos_totales'] === 30.0
                && $finanzas['datos']['kpis']['variacion_porcentual'] === null
                && count($finanzas['datos']['serie_diaria']) === 1);
    }

    public function test_month_exports_preserve_the_selected_calendar_dates(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        foreach (['analitica.pdf' => 'pdf', 'analitica.excel' => 'excel'] as $route => $tipo) {
            $this->actingAs($admin)->get(route($route, ['preset' => 'mes', 'mes' => '2026-10']))
                ->assertOk()->assertHeader('content-disposition');
            $registro = ReporteFinancieroLog::where('user_id', $admin->id)->where('tipo', $tipo)->firstOrFail();
            $this->assertSame('2026-10-01', $registro->fecha_inicio->format('Y-m-d'));
            $this->assertSame('2026-10-31', $registro->fecha_fin->format('Y-m-d'));
        }
    }

    public static function filtrosInvalidos(): array
    {
        return [
            'mes ausente' => [['preset' => 'mes'], 'mes'],
            'mes inválido' => [['preset' => 'mes', 'mes' => '2026-13'], 'mes'],
            'mes futuro' => [['preset' => 'mes', 'mes' => '2026-12'], 'periodo'],
            'rango invertido' => [['preset' => 'personalizado', 'desde' => '2026-10-10', 'hasta' => '2026-10-01'], 'periodo'],
            'rango excesivo' => [['preset' => 'personalizado', 'desde' => '2026-01-01', 'hasta' => '2026-05-01'], 'periodo'],
            'rango incompleto' => [['preset' => 'personalizado', 'desde' => '2026-10-01'], 'periodo'],
            'tipo inválido' => [['preset' => ['mes']], 'preset'],
        ];
    }

    #[DataProvider('filtrosInvalidos')]
    public function test_admin_filters_are_validated(array $query, string $error): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->getJson(route('dashboard', $query))->assertUnprocessable()->assertJsonValidationErrors($error);
    }

    public function test_non_admin_roles_never_compute_or_receive_business_finances_even_with_filters(): void
    {
        $this->mock(AnaliticaFinancieraService::class, function ($mock) {
            $mock->shouldNotReceive('resolverPeriodo');
            $mock->shouldNotReceive('obtenerAnalitica');
        });
        foreach (['Secretaria', 'Entrenador'] as $rol) {
            $user = User::factory()->create(['rol' => $rol]);
            $this->actingAs($user)->get(route('dashboard', ['preset' => 'mes', 'mes' => '2026-10']))
                ->assertOk()->assertViewHas('finanzas', null)->assertDontSee('Cómo va el negocio');
        }
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'socio.finanzas@example.test', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('cliente.dashboard', ['preset' => 'mes', 'mes' => '2026-10']))
            ->assertOk()->assertViewHas('finanzas', null)->assertDontSee('Cómo va el negocio');
    }

    public function test_client_home_never_inherits_finances_from_a_simultaneous_admin_session(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'sesion.finanzas@example.test', 'activo' => true]);
        $this->actingAs($admin)->actingAs($cliente, 'cliente')->get(route('cliente.dashboard'))
            ->assertOk()->assertViewHas('perfil', 'cliente')->assertViewHas('finanzas', null)->assertDontSee('Cómo va el negocio');
    }

    private function venta(User $admin, string $importe, string $fecha): void
    {
        $venta = new Venta(['user_id' => $admin->id, 'total' => $importe, 'metodo_pago' => 'Efectivo']);
        $venta->created_at = Carbon::parse($fecha, config('app.timezone'));
        $venta->save();
    }
}

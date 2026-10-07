<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use App\Services\ReportePdfService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_is_public_and_contact_is_escaped(): void
    {
        config(['privacy.responsible_name' => '<script>alert(1)</script>', 'privacy.contact_email' => null]);
        $this->get(route('privacidad'))->assertOk()
            ->assertSee('Política de privacidad')
            ->assertSee('Pendiente de configurar')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('login'))->assertOk()->assertSee(route('privacidad'));
    }

    public function test_analytics_rejects_arrays_and_invalid_dates_in_all_outputs(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin);
        foreach (['analitica.index', 'analitica.pdf', 'analitica.excel'] as $route) {
            $this->getJson(route($route, ['preset' => ['hoy']]))->assertUnprocessable()->assertJsonValidationErrors('preset');
            $this->getJson(route($route, ['preset' => 'personalizado', 'desde' => '2026-02-30', 'hasta' => '2026-03-01']))
                ->assertUnprocessable()->assertJsonValidationErrors('desde');
        }
        $this->assertDatabaseCount('reportes_financieros_logs', 0);
    }

    public function test_service_rejects_relative_dates(): void
    {
        $this->expectException(ValidationException::class);
        app(AnaliticaFinancieraService::class)->resolverPeriodo('personalizado', 'yesterday', 'today');
    }

    public function test_sensitive_access_forms_and_reset_link_are_not_cacheable(): void
    {
        $this->get(route('login'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('password.request'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'example@example.test']))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_previous_month_at_march_end_is_february(): void
    {
        try {
            Carbon::setTestNow('2026-03-31 12:00:00');
            $periodo = app(AnaliticaFinancieraService::class)->resolverPeriodo('mes_anterior', null, null);
            $this->assertSame('2026-02-01', $periodo['desde']->format('Y-m-d'));
            $this->assertSame('2026-02-28', $periodo['hasta']->format('Y-m-d'));
            $this->assertSame(28, $periodo['dias']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_financial_aggregation_rounds_once_and_keeps_day_totals_consistent(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        foreach (['0.10', '0.20'] as $importe) {
            Venta::create(['user_id' => $admin->id, 'total' => $importe, 'metodo_pago' => 'Efectivo']);
        }
        $service = app(AnaliticaFinancieraService::class);
        $periodo = $service->resolverPeriodo('hoy', null, null);
        $datos = $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);
        $this->assertSame(0.30, $datos['kpis']['ingresos_totales']);
        $this->assertSame(0.15, $datos['kpis']['ticket_promedio']);
        $this->assertSame($datos['kpis']['ingresos_totales'], $datos['serie_diaria'][0]['total']);
    }

    public function test_export_limit_blocks_abuse_and_is_independent_per_account(): void
    {
        $this->mock(ReportePdfService::class, function ($mock) {
            $mock->shouldReceive('descargar')->times(10)->andReturn(response('PDF de prueba'));
        });
        $first = User::factory()->create(['rol' => 'Administrador']);
        $second = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($first);
        for ($request = 0; $request < 10; $request++) {
            $this->get(route('analitica.pdf', ['preset' => 'hoy']))->assertOk();
        }
        $this->get(route('analitica.pdf', ['preset' => 'hoy']))->assertStatus(429);
        $this->actingAs($second)->get(route('asistencia.exportar'))->assertOk();
        $this->assertDatabaseCount('reportes_financieros_logs', 10);
    }
}

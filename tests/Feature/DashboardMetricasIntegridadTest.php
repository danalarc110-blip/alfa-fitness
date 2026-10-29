<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricasIntegridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_analytics_only_count_registered_membership_payments_as_income(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'socio@example.test', 'activo' => true]);
        Membresia::create([
            'cliente_id' => $cliente->id,
            'plan' => 'Membresía heredada sin cobro registrado',
            'importe' => '25.00',
            'inicio' => today(),
            'fin' => today()->addDays(29),
            'cancelada' => false,
        ]);
        Venta::create(['user_id' => $admin->id, 'total' => '0.10', 'metodo_pago' => 'Efectivo']);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertViewHas('metricas', function ($metricas) {
                $valores = collect($metricas)->pluck(1, 0);

                return $valores['Membresías cobradas'] === '$0.00'
                    && $valores['Ingresos este mes'] === '$0.10';
            });

        $analitica = app(AnaliticaFinancieraService::class)->obtenerAnalitica(
            now()->startOfMonth(), now()->endOfMonth(), now()->daysInMonth
        );
        $this->assertSame(0.0, $analitica['kpis']['ingresos_membresias']);
        $this->assertSame(0.10, $analitica['kpis']['ingresos_totales']);
    }

    public function test_reception_counter_and_capacity_exclude_the_same_orphaned_entries(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        foreach ([13, 1] as $horas) {
            $cliente = Cliente::create([
                'nombre' => 'Socio', 'correo' => "socio{$horas}@example.test", 'activo' => true,
            ]);
            Asistencia::create([
                'cliente_id' => $cliente->id,
                'fecha_hora' => now()->subHours($horas),
                'tipo_acceso' => 'entrada',
            ]);
        }

        $this->actingAs($secretaria)->get(route('dashboard'))->assertOk()
            ->assertViewHas('metricas', fn ($metricas) => collect($metricas)->pluck(1, 0)['Dentro ahora'] === 1)
            ->assertViewHas('aforo', fn ($aforo) => $aforo['actual'] === 1);
    }
}

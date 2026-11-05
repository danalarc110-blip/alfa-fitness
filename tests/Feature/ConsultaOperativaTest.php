<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\SolicitudMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultaOperativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_orphan_visits_remain_in_history_but_not_current_occupancy(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(15));
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $actual = $this->cliente('Socio actual', 'actual@example.test');
        $anterior = $this->cliente('Socio anterior', 'anterior@example.test');
        Asistencia::create(['cliente_id' => $actual->id, 'fecha_hora' => now()->subHour(), 'tipo_acceso' => 'entrada']);
        $huerfana = Asistencia::create(['cliente_id' => $anterior->id, 'fecha_hora' => now()->subHours(13), 'tipo_acceso' => 'entrada']);

        $this->actingAs($secretaria)->get(route('asistencia.index'))->assertOk()
            ->assertViewHas('dentro', 1)
            ->assertViewHas('dentroAhora', fn ($visitas) => $visitas->pluck('cliente_id')->all() === [$actual->id])
            ->assertViewHas('huerfanasCount', 1)
            ->assertViewHas('asistencias', fn ($visitas) => $visitas->total() === 2)
            ->assertViewHas('clientes', fn ($clientes) => $clientes->firstWhere('id', $anterior->id)->asistencias->isEmpty());

        auth('web')->logout();
        $this->actingAs($anterior, 'cliente')->get(route('cliente.asistencia'))->assertOk()
            ->assertViewHas('visitaActual', null)
            ->assertViewHas('totalVisitas', 1);
        $this->assertNull($huerfana->fresh()->fecha_salida);
    }

    public function test_membership_search_filters_history_and_pending_pauses_by_client(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $buscado = $this->cliente('Socio Buscado', 'buscado@example.test');
        $ajeno = $this->cliente('Socio Ajeno', 'ajeno@example.test');
        foreach ([$buscado, $ajeno] as $cliente) {
            $membresia = Membresia::create([
                'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
                'inicio' => today()->subDays(5), 'fin' => today()->addDays(25), 'cancelada' => false,
            ]);
            PausaMembresia::create([
                'cliente_id' => $cliente->id, 'membresia_id' => $membresia->id, 'dias' => 3,
                'motivo' => 'Viaje', 'estado' => 'pendiente',
                'inicio_pausa' => today()->addDay(), 'fin_pausa_estimada' => today()->addDays(3),
            ]);
        }

        $this->actingAs($secretaria)->get(route('membresias.index', ['q' => $buscado->correo]))->assertOk()
            ->assertViewHas('membresias', fn ($membresias) => $membresias->pluck('cliente_id')->all() === [$buscado->id])
            ->assertViewHas('pausasPendientes', fn ($pausas) => $pausas->pluck('cliente_id')->all() === [$buscado->id])
            ->assertSee($buscado->nombre)->assertDontSee($ajeno->nombre);

        auth('web')->logout();
        $this->actingAs($ajeno, 'cliente')->get(route('membresias.index', ['q' => $buscado->correo]))->assertOk()
            ->assertViewHas('membresias', fn ($membresias) => $membresias->pluck('cliente_id')->all() === [$ajeno->id])
            ->assertDontSee($buscado->nombre);
    }

    public function test_zero_is_a_real_account_search_instead_of_an_empty_filter(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $buscado = $this->cliente('Socio 0', 'cero@example.test');
        $this->cliente('Socio ajeno', 'ajeno@example.test');

        $this->actingAs($admin)->get(route('cuentas.index', ['q' => '0']))->assertOk()
            ->assertViewHas('cuentas', fn ($cuentas) => $cuentas->pluck('id')->all() === [$buscado->id]);
    }

    public function test_zero_search_matches_the_same_attendance_on_screen_and_csv(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $buscado = $this->cliente('Socio 0', 'cero@example.test');
        $ajeno = $this->cliente('Socio ajeno', 'ajeno@example.test');
        foreach ([$buscado, $ajeno] as $cliente) {
            Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subHour(),
                'fecha_salida' => now(), 'tipo_acceso' => 'entrada']);
        }

        $this->actingAs($secretaria)->get(route('asistencia.index', ['q' => '0']))->assertOk()
            ->assertViewHas('clientes', fn ($clientes) => $clientes->pluck('id')->all() === [$buscado->id])
            ->assertViewHas('asistencias', fn ($visitas) => $visitas->pluck('cliente_id')->all() === [$buscado->id]);
        $csv = $this->get(route('asistencia.exportar', ['q' => '0']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Socio 0', $csv);
        $this->assertStringNotContainsString('Socio ajeno', $csv);
    }

    public function test_zero_search_filters_memberships_requests_and_pauses_consistently(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $buscado = $this->cliente('Socio 0', 'cero@example.test');
        $ajeno = $this->cliente('Socio ajeno', 'ajeno@example.test');
        foreach ([$buscado, $ajeno] as $cliente) {
            SolicitudMembresia::create(['cliente_id' => $cliente->id, 'plan_nombre' => 'Mensual',
                'precio_acordado' => '25.00', 'duracion_dias' => 30, 'estado' => 'pendiente']);
            $membresia = Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Mensual',
                'importe' => '25.00', 'inicio' => today()->subDays(5), 'fin' => today()->addDays(25), 'cancelada' => false]);
            PausaMembresia::create(['cliente_id' => $cliente->id, 'membresia_id' => $membresia->id,
                'dias' => 3, 'motivo' => 'Viaje', 'estado' => 'pendiente',
                'inicio_pausa' => today()->addDay(), 'fin_pausa_estimada' => today()->addDays(3)]);
        }

        $this->actingAs($secretaria)->get(route('membresias.index', ['q' => '0']))->assertOk()
            ->assertViewHas('solicitudes', fn ($filas) => $filas->pluck('cliente_id')->all() === [$buscado->id])
            ->assertViewHas('membresias', fn ($filas) => $filas->pluck('cliente_id')->all() === [$buscado->id])
            ->assertViewHas('pausasPendientes', fn ($filas) => $filas->pluck('cliente_id')->all() === [$buscado->id]);
    }

    public function test_zero_sales_search_still_offers_a_clear_filter_link(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $html = $this->actingAs($secretaria)->get(route('ventas.index', ['q' => '0']))->assertOk()->getContent();
        $this->assertTrue(str_contains($html, '>Limpiar</a>'), 'Una búsqueda por cero debe ofrecer limpiar el filtro.');
    }

    private function cliente(string $nombre, string $correo): Cliente
    {
        return Cliente::create(['nombre' => $nombre, 'correo' => $correo, 'activo' => true]);
    }
}

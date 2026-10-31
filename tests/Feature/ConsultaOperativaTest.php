<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
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

    private function cliente(string $nombre, string $correo): Cliente
    {
        return Cliente::create(['nombre' => $nombre, 'correo' => $correo, 'activo' => true]);
    }
}

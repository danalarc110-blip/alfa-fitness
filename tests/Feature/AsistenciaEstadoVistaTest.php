<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaEstadoVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_orphaned_visit_is_not_presented_as_current_training_in_client_screens(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio de prueba', 'correo' => 'asistencia-vista@example.test', 'activo' => true]);
        Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subHours(13), 'tipo_acceso' => 'entrada']);

        $this->actingAs($cliente, 'cliente')->get(route('cliente.dashboard'))->assertOk()
            ->assertViewHas('aforo', fn ($aforo) => $aforo['actual'] === 0)
            ->assertSee('Salida sin registrar')->assertDontSee('En el gimnasio');
        $this->get(route('cliente.asistencia'))->assertOk()
            ->assertSee('Fuera del club')->assertSee('Salida sin registrar')
            ->assertDontSee('Entrenando actualmente')->assertDontSee('En curso');
    }

    public function test_reception_and_csv_do_not_label_an_orphaned_visit_as_current(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Socio de prueba', 'correo' => 'asistencia-csv@example.test', 'activo' => true]);
        Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subHours(13), 'tipo_acceso' => 'entrada']);

        $this->actingAs($secretaria)->get(route('dashboard'))->assertOk()
            ->assertSee('Salida sin registrar')->assertDontSee('En el gimnasio');
        $csv = $this->get(route('asistencia.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Salida sin registrar', $csv);
        $this->assertStringNotContainsString('En gimnasio', $csv);
    }

    public function test_recent_and_completed_visits_keep_their_correct_status(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio de prueba', 'correo' => 'asistencia-reciente@example.test', 'activo' => true]);
        Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subHours(2), 'fecha_salida' => now()->subHour(), 'tipo_acceso' => 'entrada']);
        Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subMinutes(15), 'tipo_acceso' => 'entrada']);

        $this->actingAs($cliente, 'cliente')->get(route('cliente.dashboard'))->assertOk()
            ->assertSee('En el gimnasio')->assertSee('Visita completada')->assertDontSee('Salida sin registrar');
        $this->get(route('cliente.asistencia'))->assertOk()
            ->assertSee('Actualmente en sala')->assertSee('Completada')->assertDontSee('Salida sin registrar');
    }
}

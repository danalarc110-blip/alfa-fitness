<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PausaRenovacionIntegridadTest extends TestCase
{
    use RefreshDatabase;

    public static function caminos(): array
    {
        return ['automática por personal' => [false], 'aprobación de pendiente' => [true]];
    }

    private function contexto(): array
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'socio@example.test', 'activo' => true]);
        $membresia = Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00', 'inicio' => today(), 'fin' => today()->addDays(29), 'cancelada' => false]);

        return [$staff, $cliente, $membresia];
    }

    private function extender(User $staff, Membresia $membresia, bool $pendiente): TestResponse
    {
        $this->actingAs($staff);
        if ($pendiente) {
            $pausa = PausaMembresia::create(['cliente_id' => $membresia->cliente_id, 'membresia_id' => $membresia->id, 'dias' => 3, 'motivo' => 'Viaje', 'estado' => 'pendiente', 'inicio_pausa' => today(), 'fin_pausa_estimada' => today()->addDays(2)]);

            return $this->patch(route('membresias.pausa.aprobar', $pausa));
        }

        return $this->post(route('membresias.pausa.solicitar', $membresia), ['dias' => 3, 'motivo' => 'Viaje', 'inicio_pausa' => today()->toDateString()]);
    }

    #[DataProvider('caminos')]
    public function test_extension_rejects_overlap_and_preserves_both_periods_and_pause(bool $pendiente): void
    {
        [$staff, $cliente, $membresia] = $this->contexto();
        $fin = $membresia->fin->toDateString();
        $atributosMembresia = $membresia->fresh()->getAttributes();
        $renovacion = Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Renovación', 'importe' => '25.00', 'inicio' => $membresia->fin->copy()->addDay(), 'fin' => $membresia->fin->copy()->addDays(30), 'cancelada' => false]);
        $atributosRenovacion = $renovacion->fresh()->getAttributes();

        $this->extender($staff, $membresia, $pendiente)->assertSessionHasErrors($pendiente ? 'pausa' : 'dias');
        $this->assertSame($fin, $membresia->fresh()->fin->toDateString());
        $this->assertSame($atributosMembresia, $membresia->fresh()->getAttributes());
        $this->assertSame($atributosRenovacion, $renovacion->fresh()->getAttributes());
        $this->assertDatabaseCount('pausas_membresia', $pendiente ? 1 : 0);
        if ($pendiente) {
            $this->assertDatabaseHas('pausas_membresia', ['membresia_id' => $membresia->id, 'estado' => 'pendiente', 'aprobada_por' => null]);
        }
    }

    #[DataProvider('caminos')]
    public function test_extension_without_conflict_remains_available(bool $pendiente): void
    {
        [$staff, $cliente, $membresia] = $this->contexto();
        $esperado = $membresia->fin->copy()->addDays(3)->toDateString();
        $this->extender($staff, $membresia, $pendiente)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($esperado, $membresia->fresh()->fin->toDateString());
        $this->assertDatabaseHas('pausas_membresia', ['membresia_id' => $membresia->id, 'estado' => 'aprobada', 'aprobada_por' => $staff->id]);
    }

    #[DataProvider('caminos')]
    public function test_cancelled_renewal_does_not_block_extension(bool $pendiente): void
    {
        [$staff, $cliente, $membresia] = $this->contexto();
        $esperado = $membresia->fin->copy()->addDays(3)->toDateString();
        $renovacion = Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Cancelada', 'importe' => '25.00', 'inicio' => $membresia->fin->copy()->addDay(), 'fin' => $membresia->fin->copy()->addDays(30), 'cancelada' => true]);
        $atributosRenovacion = $renovacion->fresh()->getAttributes();
        $this->extender($staff, $membresia, $pendiente)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($esperado, $membresia->fresh()->fin->toDateString());
        $this->assertSame($atributosRenovacion, $renovacion->fresh()->getAttributes());
    }

    #[DataProvider('caminos')]
    public function test_next_period_may_start_the_day_after_extended_end(bool $pendiente): void
    {
        [$staff, $cliente, $membresia] = $this->contexto();
        $esperado = $membresia->fin->copy()->addDays(3)->toDateString();
        Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Futura', 'importe' => '25.00', 'inicio' => $membresia->fin->copy()->addDays(4), 'fin' => $membresia->fin->copy()->addDays(34), 'cancelada' => false]);
        $this->extender($staff, $membresia, $pendiente)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($esperado, $membresia->fresh()->fin->toDateString());
    }
}

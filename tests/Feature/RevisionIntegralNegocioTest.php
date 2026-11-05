<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RevisionIntegralNegocioTest extends TestCase
{
    use RefreshDatabase;

    private function contexto(string $estado): array
    {
        $this->travelTo(now()->startOfDay()->addHours(10));
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Socio pausa', 'correo' => 'revision-negocio@example.test', 'activo' => true]);
        $membresia = Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
            'inicio' => today()->subDays(10), 'fin' => today()->addDays(24), 'cancelada' => false,
        ]);
        $pausa = PausaMembresia::create([
            'cliente_id' => $cliente->id, 'membresia_id' => $membresia->id, 'dias' => 3,
            'motivo' => 'Viaje', 'estado' => $estado,
            'inicio_pausa' => today()->subDays(2), 'fin_pausa_estimada' => today(),
        ]);
        // DATE columns also contain canonical dates after imports or SQL writes.
        DB::table('pausas_membresia')->where('id', $pausa->id)->update([
            'inicio_pausa' => today()->subDays(2)->toDateString(),
            'fin_pausa_estimada' => today()->toDateString(),
        ]);

        return [$staff, $membresia, $pausa->fresh()];
    }

    public static function estadosVigentes(): array
    {
        return ['aprobada' => ['aprobada'], 'pendiente' => ['pendiente']];
    }

    #[DataProvider('estadosVigentes')]
    public function test_last_calendar_day_of_a_pause_prevents_a_second_pause(string $estado): void
    {
        [$staff, $membresia, $pausa] = $this->contexto($estado);
        $fin = $membresia->fin->toDateString();

        $this->actingAs($staff, 'web')->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 3, 'motivo' => 'Segunda pausa', 'inicio_pausa' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHasErrors('dias');

        $this->assertDatabaseCount('pausas_membresia', 1);
        $this->assertSame($estado, $pausa->fresh()->estado);
        $this->assertSame($fin, $membresia->fresh()->fin->toDateString());
    }

    public function test_an_approved_pause_can_be_resumed_on_its_last_calendar_day(): void
    {
        [$staff, $membresia, $pausa] = $this->contexto('aprobada');
        $fin = $membresia->fin->toDateString();
        $this->assertSame('En Pausa', $membresia->fresh()->estado);

        $this->actingAs($staff, 'web')->patch(route('membresias.reanudar', $membresia))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('reanudada_anticipada', $pausa->fresh()->estado);
        $this->assertSame(3, $pausa->fresh()->dias);
        $this->assertSame($fin, $membresia->fresh()->fin->toDateString());
        $this->assertSame('Vigente', $membresia->fresh()->estado);
    }

    public function test_resuming_and_pausing_again_cannot_credit_the_same_day_twice(): void
    {
        [$staff, $membresia] = $this->contexto('rechazada');
        $fin = $membresia->fin->copy();
        $this->actingAs($staff, 'web')->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 3, 'motivo' => 'Viaje', 'inicio_pausa' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('membresias.reanudar', $membresia))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($fin->copy()->addDay()->toDateString(), $membresia->fresh()->fin->toDateString());
        $this->assertSame(1, $membresia->fresh()->diasPausadosAcumulados());

        $this->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 3, 'motivo' => 'Repetir día', 'inicio_pausa' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHasErrors('inicio_pausa');

        $this->assertDatabaseCount('pausas_membresia', 2);
        $this->assertSame($fin->copy()->addDay()->toDateString(), $membresia->fresh()->fin->toDateString());

        // A later, disjoint pause remains available.
        $this->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => 3, 'motivo' => 'Nuevas fechas', 'inicio_pausa' => today()->addDay()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pausas_membresia', 3);
    }

    public function test_a_pending_pause_cannot_be_approved_over_a_day_already_credited(): void
    {
        [$staff, $membresia, $anterior] = $this->contexto('reanudada_anticipada');
        $anterior->update(['inicio_pausa' => today(), 'dias' => 1, 'fecha_reanudacion' => today()]);
        $pendiente = PausaMembresia::create([
            'cliente_id' => $membresia->cliente_id, 'membresia_id' => $membresia->id, 'dias' => 3,
            'motivo' => 'Solicitud anterior', 'estado' => 'pendiente',
            'inicio_pausa' => today(), 'fin_pausa_estimada' => today()->addDays(2),
        ]);
        $fin = $membresia->fin->toDateString();

        $this->actingAs($staff, 'web')->patch(route('membresias.pausa.aprobar', $pendiente))
            ->assertRedirect()->assertSessionHasErrors('pausa');

        $this->assertSame('pendiente', $pendiente->fresh()->estado);
        $this->assertSame($fin, $membresia->fresh()->fin->toDateString());
    }

    public function test_cancelling_a_future_pause_with_zero_used_days_allows_new_dates(): void
    {
        [$staff, $membresia] = $this->contexto('rechazada');
        $fin = $membresia->fin->toDateString();
        $datos = ['dias' => 3, 'motivo' => 'Viaje', 'inicio_pausa' => today()->addDays(2)->toDateString()];
        $this->actingAs($staff, 'web')->post(route('membresias.pausa.solicitar', $membresia), $datos)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('membresias.reanudar', $membresia))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($fin, $membresia->fresh()->fin->toDateString());
        $this->assertSame(0, $membresia->fresh()->diasPausadosAcumulados());

        $this->post(route('membresias.pausa.solicitar', $membresia), $datos)
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pausas_membresia', 3);
    }
}

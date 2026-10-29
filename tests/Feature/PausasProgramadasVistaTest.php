<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PausasProgramadasVistaTest extends TestCase
{
    use RefreshDatabase;

    private function contexto(string $estado = 'aprobada', bool $membresiaFutura = false): array
    {
        $cliente = Cliente::create(['nombre' => 'Socio de prueba', 'correo' => 'pausa-vista@example.test', 'activo' => true]);
        $inicio = today()->addDays($membresiaFutura ? 2 : -5);
        $finBase = today()->addDays(30);
        $membresia = Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
            'inicio' => $inicio, 'fin' => $finBase->copy()->addDays($estado === 'aprobada' ? 7 : 0), 'cancelada' => false,
        ]);
        $pausa = PausaMembresia::create([
            'cliente_id' => $cliente->id, 'membresia_id' => $membresia->id, 'dias' => 7,
            'motivo' => 'Viaje', 'estado' => $estado,
            'inicio_pausa' => today()->addDays(5), 'fin_pausa_estimada' => today()->addDays(11),
        ]);

        return [$cliente, $membresia, $pausa, $finBase];
    }

    public static function gestores(): array
    {
        return ['propietario' => [true], 'secretaría' => [false]];
    }

    #[DataProvider('gestores')]
    public function test_owner_and_secretary_can_see_and_cancel_future_pause_without_consuming_days(bool $propietario): void
    {
        [$cliente, $membresia, $pausa, $finBase] = $this->contexto();
        $this->actingAs($propietario ? $cliente : User::factory()->create(['rol' => 'Secretaria']), $propietario ? 'cliente' : 'web');

        $this->get(route('membresias.index'))->assertOk()
            ->assertSee('Anular pausa programada')->assertSee('Programada desde '.$pausa->inicio_pausa->format('d/m/Y'))
            ->assertSee('data-confirm="¿Anular esta pausa programada?', false)
            ->assertSee(route('membresias.reanudar', $membresia))
            ->assertDontSee('onclick="abrirModalPausa(', false);

        $this->patch(route('membresias.reanudar', $membresia))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0, $pausa->fresh()->dias);
        $this->assertSame('reanudada_anticipada', $pausa->fresh()->estado);
        $this->assertSame($finBase->toDateString(), $membresia->fresh()->fin->toDateString());
        $this->assertSame(0, $membresia->fresh()->diasPausadosAcumulados());
        $this->get(route('membresias.index'))->assertOk()->assertDontSee('Anular pausa programada')
            ->assertSee('onclick="abrirModalPausa(', false);
    }

    public function test_future_membership_also_exposes_its_scheduled_pause_to_owner(): void
    {
        [$cliente, $membresia] = $this->contexto(membresiaFutura: true);
        $this->assertSame('Programada', $membresia->estado);
        $this->actingAs($cliente, 'cliente')->get(route('membresias.index'))->assertOk()
            ->assertSee('Anular pausa programada')->assertDontSee('onclick="abrirModalPausa(', false);
    }

    public function test_pending_pause_hides_second_pause_action_without_offering_an_approved_pause_cancellation(): void
    {
        [$cliente] = $this->contexto('pendiente');
        $this->actingAs($cliente, 'cliente')->get(route('membresias.index'))->assertOk()
            ->assertSee('Pausa pendiente de aprobación')->assertDontSee('Anular pausa programada')
            ->assertDontSee('onclick="abrirModalPausa(', false);
    }

    public function test_admin_can_consult_schedule_but_cannot_operate_it_and_other_client_cannot_see_it(): void
    {
        [$cliente, $membresia, $pausa] = $this->contexto();
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']), 'web')
            ->get(route('membresias.index'))->assertOk()->assertSee('Programada desde '.$pausa->inicio_pausa->format('d/m/Y'))
            ->assertDontSee('Anular pausa programada')->assertDontSee('onclick="abrirModalPausa(', false);
        $this->patch(route('membresias.reanudar', $membresia))->assertForbidden();
        auth('web')->logout();
        $ajeno = Cliente::create(['nombre' => 'Ajeno', 'correo' => 'ajeno-pausa@example.test', 'activo' => true]);
        $this->actingAs($ajeno, 'cliente')->get(route('membresias.index'))->assertOk()
            ->assertDontSee('Socio de prueba')->assertDontSee('Anular pausa programada');
        $this->patch(route('membresias.reanudar', $membresia))->assertForbidden();
        $this->assertSame('aprobada', $pausa->fresh()->estado);
    }

    public function test_cancelled_membership_does_not_offer_pause_actions_even_with_old_scheduled_record(): void
    {
        [$cliente, $membresia] = $this->contexto();
        $membresia->update(['cancelada' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('membresias.index'))->assertOk()
            ->assertDontSee('Anular pausa programada')->assertDontSee('onclick="abrirModalPausa(', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PausaMembresia;
use App\Models\PlanMembresia;
use App\Models\SolicitudMembresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PausasRenovacionTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    private User $secretaria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-04 10:00:00'));
        $this->cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'cola@example.test', 'activo' => true]);
        $this->secretaria = User::factory()->create(['rol' => 'Secretaria']);
    }

    private function cobrar(int $dias = 30): Membresia
    {
        $plan = PlanMembresia::firstOrFail();
        $solicitud = SolicitudMembresia::create([
            'cliente_id' => $this->cliente->id, 'plan_id' => $plan->id,
            'plan_nombre' => $plan->nombre, 'precio_acordado' => 25, 'duracion_dias' => $dias,
        ]);
        auth('cliente')->logout();
        $this->actingAs($this->secretaria)->patch(route('membresias.activar', $solicitud), ['importe' => 25, 'metodo_pago' => 'efectivo'])
            ->assertRedirect()->assertSessionHasNoErrors();

        return Membresia::where('solicitud_id', $solicitud->id)->firstOrFail();
    }

    private function pausar(Membresia $membresia, int $dias = 10, int $inicioEn = 0): void
    {
        $this->post(route('membresias.pausa.solicitar', $membresia), [
            'dias' => $dias, 'motivo' => 'Vacaciones', 'inicio_pausa' => today()->addDays($inicioEn)->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function assertPeriodo(Membresia $membresia, Carbon $inicio, Carbon $fin): void
    {
        $membresia->refresh();
        $this->assertSame($inicio->toDateString(), $membresia->inicio->toDateString());
        $this->assertSame($fin->toDateString(), $membresia->fin->toDateString());
    }

    public function test_pausa_secretaria_desplaza_todas_las_renovaciones_sin_alterar_cobros(): void
    {
        $actual = $this->cobrar();
        $segunda = $this->cobrar();
        $tercera = $this->cobrar(15);
        $pagosAntes = PagoMembresia::orderBy('id')->get()->toArray();
        $this->pausar($actual);
        $this->assertPeriodo($actual, today(), today()->addDays(39));
        $this->assertPeriodo($segunda, today()->addDays(40), today()->addDays(69));
        $this->assertPeriodo($tercera, today()->addDays(70), today()->addDays(84));
        $this->assertSame($pagosAntes, PagoMembresia::orderBy('id')->get()->toArray());
    }

    public function test_solicitud_cliente_no_mueve_cola_hasta_aprobacion(): void
    {
        $actual = $this->cobrar();
        $renovacion = $this->cobrar();
        $this->actingAs($this->cliente, 'cliente');
        $this->pausar($actual, 5);
        $this->assertPeriodo($renovacion, today()->addDays(30), today()->addDays(59));
        auth('cliente')->logout();
        $pausa = PausaMembresia::firstOrFail();
        $this->actingAs($this->secretaria)->patch(route('membresias.pausa.aprobar', $pausa))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertPeriodo($actual, today(), today()->addDays(34));
        $this->assertPeriodo($renovacion, today()->addDays(35), today()->addDays(64));
        $this->assertSame('aprobada', $pausa->fresh()->estado);
    }

    public function test_reanudacion_anticipada_devuelve_cola_y_conserva_dias_efectivos(): void
    {
        $base = today();
        $actual = $this->cobrar();
        $segunda = $this->cobrar();
        $tercera = $this->cobrar(15);
        $this->pausar($actual);
        $this->travel(3)->days();
        $this->patch(route('membresias.reanudar', $actual))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertPeriodo($actual, $base, $base->copy()->addDays(33));
        $this->assertPeriodo($segunda, $base->copy()->addDays(34), $base->copy()->addDays(63));
        $this->assertPeriodo($tercera, $base->copy()->addDays(64), $base->copy()->addDays(78));
        $this->assertSame(4, PausaMembresia::firstOrFail()->dias);
        $this->assertDatabaseCount('pagos_membresia', 3);
    }

    public function test_cancelar_pausa_futura_restaura_fechas_originales(): void
    {
        $actual = $this->cobrar();
        $renovacion = $this->cobrar();
        $this->pausar($actual, 10, 7);
        $this->patch(route('membresias.reanudar', $actual))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertPeriodo($actual, today(), today()->addDays(29));
        $this->assertPeriodo($renovacion, today()->addDays(30), today()->addDays(59));
        $this->assertSame(0, PausaMembresia::firstOrFail()->dias);
    }

    public function test_rechazar_solicitud_no_mueve_renovacion(): void
    {
        $actual = $this->cobrar();
        $renovacion = $this->cobrar();
        $this->actingAs($this->cliente, 'cliente');
        $this->pausar($actual);
        auth('cliente')->logout();
        $this->actingAs($this->secretaria)->patch(route('membresias.pausa.rechazar', PausaMembresia::firstOrFail()))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertPeriodo($actual, today(), today()->addDays(29));
        $this->assertPeriodo($renovacion, today()->addDays(30), today()->addDays(59));
    }

    public function test_cola_respeta_huecos_y_no_mueve_canceladas_o_otro_cliente(): void
    {
        $actual = $this->cobrar();
        $renovacion = $this->cobrar();
        $renovacion->update(['inicio' => today()->addDays(32), 'fin' => today()->addDays(61)]);
        $cancelada = Membresia::create(['cliente_id' => $this->cliente->id, 'plan' => 'Cancelada', 'importe' => 25, 'inicio' => today()->addDays(30), 'fin' => today()->addDays(59), 'cancelada' => true]);
        $otro = Cliente::create(['nombre' => 'Otro', 'correo' => 'otro-cola@example.test', 'activo' => true]);
        $ajena = Membresia::create(['cliente_id' => $otro->id, 'plan' => 'Mensual', 'importe' => 25, 'inicio' => today()->addDays(30), 'fin' => today()->addDays(59)]);
        $this->pausar($actual, 5, 7);
        $this->assertPeriodo($renovacion, today()->addDays(37), today()->addDays(66));
        $this->patch(route('membresias.reanudar', $actual))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertPeriodo($renovacion, today()->addDays(32), today()->addDays(61));
        $this->assertPeriodo($cancelada, today()->addDays(30), today()->addDays(59));
        $this->assertPeriodo($ajena, today()->addDays(30), today()->addDays(59));
    }

    public function test_reanudar_datos_legados_no_cambia_periodo_que_ya_empezo(): void
    {
        $actual = Membresia::create(['cliente_id' => $this->cliente->id, 'plan' => 'Legada', 'importe' => 25, 'inicio' => today()->subDays(40), 'fin' => today()->subDays(2)]);
        $iniciada = Membresia::create(['cliente_id' => $this->cliente->id, 'plan' => 'Usada', 'importe' => 25, 'inicio' => today()->subDay(), 'fin' => today()->addDays(28)]);
        $futura = Membresia::create(['cliente_id' => $this->cliente->id, 'plan' => 'Futura', 'importe' => 25, 'inicio' => today()->addDays(29), 'fin' => today()->addDays(58)]);
        PausaMembresia::create(['cliente_id' => $this->cliente->id, 'membresia_id' => $actual->id, 'dias' => 10, 'motivo' => 'Importada', 'estado' => 'aprobada', 'inicio_pausa' => today()->addDays(2), 'fin_pausa_estimada' => today()->addDays(11)]);
        $this->actingAs($this->secretaria)->patch(route('membresias.reanudar', $actual))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertPeriodo($iniciada, today()->subDay(), today()->addDays(28));
        $this->assertPeriodo($futura, today()->addDays(29), today()->addDays(58));
    }
}

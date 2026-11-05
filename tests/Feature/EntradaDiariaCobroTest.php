<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoEntrada;
use App\Models\PausaMembresia;
use App\Models\User;
use App\Services\RegistroAsistencia;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class EntradaDiariaCobroTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo = 'entrada@example.test'): Cliente
    {
        return Cliente::create(['nombre' => 'Socio de entrada', 'correo' => $correo, 'activo' => true]);
    }

    private function membresia(Cliente $cliente, array $cambios = []): Membresia
    {
        return Membresia::create(array_merge([
            'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
            'inicio' => today()->subDays(5), 'fin' => today()->addDays(24), 'cancelada' => false,
        ], $cambios));
    }

    private function pago(Cliente $cliente, ?string $uuid = null): array
    {
        return ['cliente_id' => $cliente->id, 'pago_entrada_confirmado' => true, 'entrada_uuid' => $uuid ?? (string) Str::uuid()];
    }

    public static function sinVigencia(): array
    {
        return [['sin membresía'], ['vencida'], ['cancelada'], ['programada']];
    }

    #[DataProvider('sinVigencia')]
    public function test_an_entry_without_current_membership_requires_explicit_payment_confirmation(string $estado): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        if ($estado !== 'sin membresía') {
            $this->membresia($cliente, match ($estado) {
                'vencida' => ['inicio' => today()->subDays(30), 'fin' => today()->subDay()],
                'cancelada' => ['cancelada' => true],
                default => ['inicio' => today()->addDay(), 'fin' => today()->addDays(30)],
            });
        }

        $this->actingAs($staff, 'web')->postJson(route('asistencia.store'), [
            'cliente_id' => $cliente->id, 'entrada_uuid' => (string) Str::uuid(), 'importe' => '0.00',
        ])->assertUnprocessable()->assertJsonValidationErrors('pago_entrada_confirmado');
        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }

    public function test_confirmed_daily_entry_charges_exactly_two_dollars_and_ignores_posted_amounts(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $uuid = strtoupper((string) Str::uuid());
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), $this->pago($cliente, $uuid) + ['importe' => '0.01'])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', 'Entrada registrada. Cobro de $2.00 registrado.');

        $asistencia = Asistencia::firstOrFail();
        $this->assertSame(strtolower($uuid), $asistencia->entrada_uuid);
        $pago = PagoEntrada::firstOrFail();
        $this->assertSame($asistencia->id, $pago->asistencia_id);
        $this->assertSame($staff->id, $pago->registrado_por);
        $this->assertSame('2.00', $pago->importe);
        $this->assertTrue($pago->pagado_en->isToday());
    }

    public function test_daily_payment_requires_a_valid_retry_identifier(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $this->actingAs($staff, 'web')->postJson(route('asistencia.store'), [
            'cliente_id' => $cliente->id, 'pago_entrada_confirmado' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('entrada_uuid');
        $this->postJson(route('asistencia.store'), $this->pago($cliente, 'no-es-uuid'))
            ->assertUnprocessable()->assertJsonValidationErrors('entrada_uuid');
        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }

    public function test_retries_do_not_charge_again_even_after_the_original_visit_has_ended(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $payload = $this->pago($cliente);
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('asistencia.store'), $payload)->assertSessionHasNoErrors();
        $this->postJson(route('asistencia.store'), $this->pago($cliente))->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id, 'entrada_uuid' => $payload['entrada_uuid']])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseCount('pagos_entrada', 1);
        $this->assertNotNull(Asistencia::firstOrFail()->fecha_salida);
    }

    public function test_retry_identifier_cannot_be_reused_for_another_member_or_operator(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $otroStaff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $otro = $this->cliente('otro-entrada@example.test');
        $payload = $this->pago($cliente);
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), $payload)->assertSessionHasNoErrors();
        $this->postJson(route('asistencia.store'), $this->pago($otro, $payload['entrada_uuid']))->assertForbidden();
        $this->actingAs($otroStaff, 'web')->postJson(route('asistencia.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseCount('pagos_entrada', 1);
    }

    public function test_current_membership_is_free_even_when_a_future_renewal_exists_and_no_legacy_payment_ledger_exists(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = $this->cliente();
        $this->membresia($cliente, ['fin' => today()]);
        $this->membresia($cliente, ['inicio' => today()->addDay(), 'fin' => today()->addDays(30)]);
        $uuid = (string) Str::uuid();

        $this->actingAs($admin, 'web')->get(route('asistencia.index'))->assertOk()
            ->assertViewHas('clientes', fn ($clientes) => $clientes->firstWhere('id', $cliente->id)->acceso_entrada['membresia_estado'] === 'Vigente'
                && $clientes->firstWhere('id', $cliente->id)->acceso_entrada['requiere_pago'] === false);
        $this->post(route('asistencia.store'), $this->pago($cliente, $uuid))->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id, 'entrada_uuid' => $uuid])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseCount('pagos_entrada', 0);
        $this->assertDatabaseCount('pagos_membresia', 0);
    }

    public function test_membership_last_calendar_day_is_included_when_the_database_stores_a_plain_date(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $membresia = $this->membresia($cliente);
        DB::table('membresias')->where('id', $membresia->id)->update(['fin' => today()->toDateString()]);

        $this->actingAs($staff, 'web')->post(route('asistencia.store'), ['cliente_id' => $cliente->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }

    public function test_payment_cannot_bypass_an_active_pause_or_an_inactive_account(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $membresia = $this->membresia($cliente);
        $pausa = PausaMembresia::create([
            'cliente_id' => $cliente->id, 'membresia_id' => $membresia->id, 'dias' => 3, 'motivo' => 'Reposo',
            'estado' => 'aprobada', 'inicio_pausa' => today(), 'fin_pausa_estimada' => today()->addDays(2),
        ]);
        $this->actingAs($staff, 'web')->postJson(route('asistencia.store'), $this->pago($cliente))
            ->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $pausa->update(['estado' => 'rechazada']);
        $cliente->update(['activo' => false]);
        $this->postJson(route('asistencia.store'), $this->pago($cliente))->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }

    public function test_ledger_failure_rolls_back_the_new_visit_and_orphan_closure(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $huerfana = Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subHours(15), 'tipo_acceso' => 'entrada']);
        $evento = 'eloquent.creating: '.PagoEntrada::class;
        Event::listen($evento, fn () => throw new RuntimeException('Fallo de cobro simulado'));
        try {
            app(RegistroAsistencia::class)->registrar($cliente->id, $staff->id, false, true, (string) Str::uuid());
            $this->fail('El fallo del registro del cobro debe cancelar toda la transacción.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo de cobro simulado', $exception->getMessage());
        } finally {
            Event::forget($evento);
        }
        $this->assertNull($huerfana->fresh()->fecha_salida);
        $this->assertDatabaseCount('asistencias', 1);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }

    public function test_exit_does_not_charge_and_remains_available_for_a_disabled_daily_visitor(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), $this->pago($cliente))->assertSessionHasNoErrors();
        $cliente->update(['activo' => false]);
        $this->post(route('asistencia.salida'), ['cliente_id' => $cliente->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull(Asistencia::firstOrFail()->fecha_salida);
        $this->assertDatabaseCount('pagos_entrada', 1);
        $this->postJson(route('asistencia.store'), $this->pago($cliente))->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
    }

    public function test_historical_visits_do_not_generate_retroactive_daily_charges(): void
    {
        $cliente = $this->cliente();
        $asistencia = Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subDays(2), 'tipo_acceso' => 'entrada']);
        $this->assertNull($asistencia->entrada_uuid);
        $this->assertNull($asistencia->pagoEntrada);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }

    public function test_csv_reports_only_the_charge_actually_linked_to_each_visit(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $historica = Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => now()->subDays(2),
            'fecha_salida' => now()->subDays(2)->addHour(), 'tipo_acceso' => 'entrada']);
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), $this->pago($cliente))->assertSessionHasNoErrors();
        $diaria = Asistencia::whereKeyNot($historica->id)->firstOrFail();

        $csv = $this->get(route('asistencia.exportar'))->assertOk()->streamedContent();
        $filas = array_map(fn ($fila) => str_getcsv($fila, ',', '"', ''), preg_split('/\r?\n/', trim($csv)));
        $cabecera = array_shift($filas);
        $this->assertSame('Cobro por entrada (USD)', $cabecera[7]);
        $porId = collect($filas)->keyBy(fn ($fila) => (int) $fila[0]);
        $this->assertSame('2.00', $porId[$diaria->id][7]);
        $this->assertSame('', $porId[$historica->id][7]);
    }

    public function test_trainers_and_clients_cannot_collect_daily_entry_payments(): void
    {
        $cliente = $this->cliente();
        $trainer = User::factory()->create(['rol' => 'Entrenador']);
        $this->actingAs($trainer, 'web')->postJson(route('asistencia.store'), $this->pago($cliente))->assertForbidden();
        auth('web')->logout();
        $this->actingAs($cliente, 'cliente')->postJson(route('asistencia.store'), $this->pago($cliente))->assertForbidden();
        $this->assertDatabaseCount('asistencias', 0);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }

    public function test_paid_visit_and_its_operator_cannot_be_deleted_and_destroy_the_ledger(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $this->actingAs($staff, 'web')->post(route('asistencia.store'), $this->pago($cliente))->assertSessionHasNoErrors();
        foreach ([Asistencia::firstOrFail(), $staff] as $modelo) {
            try {
                $modelo->delete();
                $this->fail('Un cobro debe conservar la visita y el personal que lo registró.');
            } catch (QueryException) {
                $this->assertDatabaseCount('pagos_entrada', 1);
                $this->assertDatabaseCount('asistencias', 1);
            }
        }
    }
}

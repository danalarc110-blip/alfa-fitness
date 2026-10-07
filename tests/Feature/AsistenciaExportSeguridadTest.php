<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AsistenciaExportSeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_neutralizes_formulas_preserves_text_and_omits_email(): void
    {
        $payloads = ['=1+1', '+1+1', '-1+1', '@SUM(1)', "\t=1+1", "\r=1+1", "\n=1+1", '  =1+1', 'Texto, "normal"'];
        $secretaria = User::factory()->create(['rol' => 'Secretaria', 'name' => '=1+1']);
        foreach ($payloads as $i => $payload) {
            $cliente = Cliente::create(['nombre' => $payload, 'correo' => "privado{$i}@example.test", 'activo' => true]);
            Asistencia::create(['cliente_id' => $cliente->id, 'registrado_por' => $secretaria->id,
                'salida_registrada_por' => $secretaria->id, 'fecha_hora' => '2026-10-01 10:00:00', 'fecha_salida' => '2026-10-01 11:00:00']);
        }

        $response = $this->actingAs($secretaria)->get(route('asistencia.exportar'))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringNotContainsString('@example.test', $csv);
        $rows = $this->parseCsv($csv);
        $this->assertSame(['ID', 'Cliente', 'Fecha Entrada', 'Fecha Salida', 'Duración', 'Registrado Por', 'Salida Por'], array_shift($rows));
        $this->assertCount(count($payloads), $rows);
        foreach (array_reverse($payloads) as $i => $payload) {
            $this->assertSame(str_starts_with($payload, 'Texto') ? $payload : "'".$payload, $rows[$i][1]);
            $this->assertSame("'=1+1", $rows[$i][5]);
            $this->assertSame("'=1+1", $rows[$i][6]);
        }
    }

    public function test_csv_applies_client_search_dates_and_state_without_mutating_history(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Ana', 'correo' => 'buscar@example.test', 'activo' => true]);
        foreach ([['2026-09-30 23:59:59', null], ['2026-10-01 00:00:00', null], ['2026-10-01 23:59:59', null], ['2026-10-02 00:00:00', null], ['2026-10-01 12:00:00', '2026-10-01 13:00:00']] as [$entrada, $salida]) {
            Asistencia::create(['cliente_id' => $cliente->id, 'fecha_hora' => $entrada, 'fecha_salida' => $salida]);
        }
        $other = Cliente::create(['nombre' => 'Otro', 'correo' => 'otro@example.test', 'activo' => true]);
        Asistencia::create(['cliente_id' => $other->id, 'fecha_hora' => '2026-10-01 13:00:00']);
        $params = ['q' => 'buscar@example.test', 'cliente_id' => $cliente->id, 'desde' => '2026-10-01', 'hasta' => '2026-10-01', 'estado' => 'abierta'];
        $response = $this->actingAs($secretaria)->get(route('asistencia.exportar', $params))->assertOk();
        $rows = $this->parseCsv($response->streamedContent());
        $this->assertCount(3, $rows);
        $this->assertSame('2026-10-01 23:59:59', $rows[1][2]);
        $this->assertSame('2026-10-01 00:00:00', $rows[2][2]);
        $params['estado'] = 'cerrada';
        $rows = $this->parseCsv($this->get(route('asistencia.exportar', $params))->assertOk()->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame('1h 0m', $rows[1][4]);
        $this->assertDatabaseCount('asistencias', 6);
        $this->assertSame(5, Asistencia::whereNull('fecha_salida')->count());
    }

    public function test_csv_rejects_reversed_dates_and_unauthorized_roles(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->getJson(route('asistencia.exportar', ['desde' => '2026-10-02', 'hasta' => '2026-10-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('hasta');
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']))->get(route('asistencia.exportar'))->assertOk();
        $this->actingAs(User::factory()->create(['rol' => 'Entrenador']))->get(route('asistencia.exportar'))->assertForbidden();
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria', 'activo' => false]))->getJson(route('asistencia.exportar'))->assertForbidden();
        auth('web')->logout();
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'cliente@example.test', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('asistencia.exportar'))->assertForbidden();
    }

    public function test_csv_streams_all_batches_in_stable_order(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'cliente@example.test', 'activo' => true]);
        $records = array_fill(0, 501, ['cliente_id' => $cliente->id, 'fecha_hora' => '2026-10-01 10:00:00', 'fecha_salida' => '2026-10-01 11:00:00']);
        DB::table('asistencias')->insert($records);
        DB::enableQueryLog();
        $response = $this->actingAs($secretaria)->get(route('asistencia.exportar'))->assertOk();
        $this->assertCount(0, array_filter(DB::getQueryLog(), fn ($query) => str_contains($query['query'], 'from "asistencias"')));
        $rows = $this->parseCsv($response->streamedContent());
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(502, $rows);
        $this->assertSame(range(501, 1), array_map(fn ($row) => (int) $row[0], array_slice($rows, 1)));
        $batchQueries = array_filter($queries, fn ($query) => str_contains($query['query'], 'from "asistencias"'));
        $this->assertCount(2, $batchQueries);
        foreach ($batchQueries as $query) {
            $this->assertStringContainsString('limit 500', $query['query']);
        }
    }

    private function parseCsv(string $csv): array
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, substr($csv, 3));
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }
}

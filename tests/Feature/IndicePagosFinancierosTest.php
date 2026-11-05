<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\SolicitudMembresia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IndicePagosFinancierosTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_date_queries_use_the_index_and_rollback_preserves_financial_records(): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'indice@example.test', 'activo' => true]);
        $solicitud = SolicitudMembresia::create([
            'cliente_id' => $cliente->id, 'plan_nombre' => 'Mensual', 'precio_acordado' => '25.00',
            'duracion_dias' => 30, 'estado' => 'activada',
        ]);
        $membresia = Membresia::create([
            'cliente_id' => $cliente->id, 'solicitud_id' => $solicitud->id, 'plan' => 'Mensual',
            'importe' => '25.00', 'inicio' => '2026-10-01', 'fin' => '2026-10-30',
        ]);
        $pago = PagoMembresia::create([
            'membresia_id' => $membresia->id, 'solicitud_id' => $solicitud->id,
            'importe' => '25.00', 'pagado_en' => '2026-10-01 10:00:00',
        ]);
        $consulta = 'SELECT SUM(importe) FROM pagos_membresia WHERE pagado_en BETWEEN ? AND ?';
        $fechas = ['2026-10-01 00:00:00', '2026-10-01 23:59:59'];
        if (DB::getDriverName() === 'sqlite') {
            $plan = implode(' ', array_column(DB::select('EXPLAIN QUERY PLAN '.$consulta, $fechas), 'detail'));
            $this->assertStringContainsString('USING INDEX pagos_membresia_pagado_en_idx', $plan);
        }
        $antes = DB::table('pagos_membresia')->orderBy('id')->get()->toJson();

        $migracion = require database_path('migrations/2026_11_04_100000_index_membership_payment_date.php');
        $migracion->down();
        $this->assertFalse(Schema::hasIndex('pagos_membresia', 'pagos_membresia_pagado_en_idx'));
        if (DB::getDriverName() === 'sqlite') {
            $sinIndice = implode(' ', array_column(DB::select('EXPLAIN QUERY PLAN '.$consulta.' -- sin indice', $fechas), 'detail'));
            $this->assertStringContainsString('SCAN pagos_membresia', $sinIndice);
        }
        $this->assertEquals(25.0, DB::scalar($consulta, $fechas));
        $this->assertSame($antes, DB::table('pagos_membresia')->orderBy('id')->get()->toJson());

        $migracion->up();
        $this->assertTrue(Schema::hasIndex('pagos_membresia', 'pagos_membresia_pagado_en_idx'));
        $this->assertSame($antes, DB::table('pagos_membresia')->orderBy('id')->get()->toJson());
        $this->assertSame('25.00', $pago->fresh()->importe);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use App\Services\AnaliticaFinancieraService;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReporteSnapshotDiarioTest extends TestCase
{
    private const LECTOR = 'reporte_snapshot_lector';

    private const ESCRITOR = 'reporte_snapshot_escritor';

    private ?string $archivoTemporal = null;

    private int $vendedorId;

    private int $productoId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'America/El_Salvador']);
        Carbon::setTestNow(Carbon::parse('2026-10-22 12:00:00', 'America/El_Salvador'));
        $archivo = tempnam(sys_get_temp_dir(), 'af_report_snapshot_');
        $this->assertNotFalse($archivo);
        $this->archivoTemporal = $archivo;
        $conexion = [
            'driver' => 'sqlite', 'url' => null, 'database' => $archivo, 'prefix' => '',
            'foreign_key_constraints' => true, 'busy_timeout' => 5000,
            'journal_mode' => 'WAL', 'transaction_mode' => 'DEFERRED',
        ];
        config(['database.default' => self::LECTOR, 'database.connections.'.self::LECTOR => $conexion, 'database.connections.'.self::ESCRITOR => $conexion]);
        $this->assertSame(0, Artisan::call('migrate', ['--database' => self::LECTOR, '--force' => true]), Artisan::output());
        $lector = DB::connection(self::LECTOR);
        $escritor = DB::connection(self::ESCRITOR);
        $this->assertNotSame($lector->getPdo(), $escritor->getPdo(), 'El escritor debe ser una conexión independiente.');
        $this->assertSame('wal', strtolower($lector->selectOne('PRAGMA journal_mode')->journal_mode));
        $this->vendedorId = User::factory()->create(['rol' => 'Secretaria'])->id;
        $this->productoId = Producto::create(['nombre' => 'Agua del escenario concurrente', 'precio' => '5.00', 'stock' => 10, 'activo' => true])->id;
    }

    protected function tearDown(): void
    {
        try {
            DB::purge(self::LECTOR);
            DB::purge(self::ESCRITOR);
            if ($this->archivoTemporal !== null) {
                foreach ([$this->archivoTemporal, $this->archivoTemporal.'-wal', $this->archivoTemporal.'-shm'] as $archivo) {
                    if (is_file($archivo)) {
                        unlink($archivo);
                    }
                }
            }
        } finally {
            Carbon::setTestNow();
            parent::tearDown();
        }
    }

    public function test_a_committed_concurrent_sale_does_not_split_the_report_snapshot(): void
    {
        $escritor = DB::connection(self::ESCRITOR);
        $this->crearVenta($escritor, '10.00');
        $intercalada = false;
        DB::listen(function (QueryExecuted $consulta) use ($escritor, &$intercalada): void {
            if (! $intercalada && $consulta->connectionName === self::LECTOR
                && str_starts_with($consulta->sql, 'select sum("total") as aggregate from "ventas"')) {
                $intercalada = true;
                // Ejecutar y confirmar por OTRO PDO justo después de leer el total.
                $this->crearVenta($escritor, '5.00');
            }
        });

        $datos = $this->reporte();
        $this->assertTrue($intercalada, 'El commit concurrente debe ejecutarse entre SUM y las lecturas posteriores.');
        $this->assertSame(0, $escritor->transactionLevel());
        $this->assertSame(2, $escritor->table('ventas')->count(), 'La segunda venta debe estar confirmada en la base compartida.');
        $this->assertSame([
            'ingresos' => 10.0, 'operaciones' => 1, 'serie' => 10.0, 'productos' => 10.0, 'conciliada' => true,
        ], [
            'ingresos' => $datos['kpis']['ingresos_ventas'],
            'operaciones' => $datos['kpis']['transacciones_ventas'],
            'serie' => array_sum(array_column($datos['serie_diaria'], 'ventas')),
            'productos' => $datos['conciliacion']['ingresos_productos'],
            'conciliada' => $datos['conciliacion']['correcta'],
        ], 'Un reporte debe mantener la misma vista de ventas durante todas sus consultas.');
        $siguiente = $this->reporte();
        $this->assertSame(15.0, $siguiente['kpis']['ingresos_ventas']);
        $this->assertSame(2, $siguiente['kpis']['transacciones_ventas']);
        $this->assertTrue($siguiente['conciliacion']['correcta']);
        $this->assertSame(0, DB::connection(self::LECTOR)->transactionLevel());
    }

    public function test_report_preserves_the_callers_transaction_and_does_not_commit_its_changes(): void
    {
        $lector = DB::connection(self::LECTOR);
        $lector->beginTransaction();
        try {
            $this->crearVenta($lector, '10.00');
            $datos = $this->reporte();
            $this->assertSame(10.0, $datos['kpis']['ingresos_ventas']);
            $this->assertSame(1, $lector->transactionLevel());
            $this->assertSame(0, DB::connection(self::ESCRITOR)->table('ventas')->count());
        } finally {
            $lector->rollBack();
        }
        $this->assertSame(0, $lector->table('ventas')->count());
    }

    public function test_invalid_report_releases_its_transaction_and_keeps_later_reports_usable(): void
    {
        try {
            app(AnaliticaFinancieraService::class)->obtenerAnalitica(today()->addDay(), today(), 1);
            $this->fail('El período invertido debe rechazarse.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('periodo', $error->errors());
        }
        $this->assertSame(0, DB::connection(self::LECTOR)->transactionLevel());
        $this->crearVenta(DB::connection(self::ESCRITOR), '5.00');
        $this->assertSame(5.0, $this->reporte()['kpis']['ingresos_ventas']);
        $this->assertSame(0, DB::connection(self::LECTOR)->transactionLevel());
    }

    private function crearVenta(ConnectionInterface $conexion, string $importe): void
    {
        $conexion->transaction(function () use ($conexion, $importe): void {
            $fecha = '2026-10-22 12:00:00';
            $ventaId = $conexion->table('ventas')->insertGetId([
                'venta_uuid' => (string) Str::uuid(), 'user_id' => $this->vendedorId,
                'total' => $importe, 'metodo_pago' => 'Efectivo', 'pago_simulado' => false,
                'created_at' => $fecha, 'updated_at' => $fecha,
            ]);
            $conexion->table('detalle_ventas')->insert([
                'venta_id' => $ventaId, 'producto_id' => $this->productoId, 'cantidad' => 1,
                'precio_unitario' => $importe, 'subtotal' => $importe, 'created_at' => $fecha, 'updated_at' => $fecha,
            ]);
        });
    }

    private function reporte(): array
    {
        return app(AnaliticaFinancieraService::class)->obtenerAnalitica(today(), today()->endOfDay(), 1);
    }
}

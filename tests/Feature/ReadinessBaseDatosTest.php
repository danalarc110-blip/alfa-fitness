<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class ReadinessBaseDatosTest extends TestCase
{
    public function test_health_check_executes_a_read_only_database_probe_and_returns_ok(): void
    {
        config(['app.debug' => false]);
        DB::shouldReceive('select')->once()->with('SELECT 1')->andReturn([(object) ['ready' => 1]]);

        $this->get('/up')->assertOk()->assertSee('Application up');
    }

    public function test_database_failure_marks_health_unavailable_without_exposing_connection_details(): void
    {
        config(['app.debug' => false]);
        $detallePrivado = 'host=db-private.invalid user=private-user password=PRIVATE-SECRET';
        DB::shouldReceive('select')->once()->with('SELECT 1')->andThrow(new RuntimeException($detallePrivado));
        Log::shouldReceive('error')->once()->withArgs(function (string $mensaje, array $contexto) use ($detallePrivado): bool {
            $excepcion = $contexto['exception'];
            $this->assertSame('No se pudo verificar la disponibilidad del servicio.', $mensaje);
            $this->assertNull($excepcion->getPrevious());
            $this->assertStringNotContainsString($detallePrivado, (string) $excepcion);

            return true;
        });

        $this->get('/up')->assertStatus(500)->assertSee('Application experiencing problems')
            ->assertDontSee($detallePrivado)->assertDontSee('db-private.invalid')->assertDontSee('PRIVATE-SECRET');
    }
}

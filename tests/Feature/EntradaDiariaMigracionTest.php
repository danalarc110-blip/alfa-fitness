<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EntradaDiariaMigracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_and_rollback_preserve_historical_attendance_without_inventing_charges(): void
    {
        $migration = require database_path('migrations/2026_11_05_100000_create_pagos_entrada_table.php');
        $migration->down();
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Socio histórico', 'correo' => 'historico.entrada@example.test', 'activo' => true]);
        $id = DB::table('asistencias')->insertGetId([
            'cliente_id' => $cliente->id, 'registrado_por' => $staff->id,
            'fecha_hora' => '2026-10-01 08:00:00', 'fecha_salida' => '2026-10-01 09:00:00',
            'tipo_acceso' => 'entrada',
        ]);

        $migration->up();
        $this->assertTrue(Schema::hasColumn('asistencias', 'entrada_uuid'));
        $this->assertDatabaseHas('asistencias', ['id' => $id, 'cliente_id' => $cliente->id, 'entrada_uuid' => null]);
        $this->assertDatabaseCount('pagos_entrada', 0);

        $migration->down();
        $this->assertFalse(Schema::hasColumn('asistencias', 'entrada_uuid'));
        $this->assertDatabaseHas('asistencias', ['id' => $id, 'fecha_hora' => '2026-10-01 08:00:00']);
        $migration->up();
        $this->assertDatabaseHas('asistencias', ['id' => $id, 'fecha_salida' => '2026-10-01 09:00:00', 'entrada_uuid' => null]);
        $this->assertDatabaseCount('pagos_entrada', 0);
    }
}

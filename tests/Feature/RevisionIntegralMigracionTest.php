<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RevisionIntegralMigracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_version_migration_preserves_existing_accounts_and_can_roll_back(): void
    {
        $migracion = require database_path('migrations/2026_11_05_120000_add_access_version_to_clientes.php');
        $migracion->down();
        $id = DB::table('clientes')->insertGetId([
            'nombre' => 'Socio existente', 'correo' => 'migracion@example.test',
            'password' => 'hash-existente-de-prueba', 'activo' => true,
        ]);
        $antes = (array) DB::table('clientes')->where('id', $id)->first();

        $migracion->up();

        $despues = (array) DB::table('clientes')->where('id', $id)->first();
        $this->assertSame(0, $despues['access_version']);
        unset($despues['access_version']);
        $this->assertSame($antes, $despues);

        $migracion->down();

        $this->assertFalse(Schema::hasColumn('clientes', 'access_version'));
        $this->assertSame($antes, (array) DB::table('clientes')->where('id', $id)->first());
    }
}

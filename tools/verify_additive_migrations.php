<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Always creates a disposable database. Never connects to the configured application database.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/../vendor/autoload.php';
$database = tempnam(sys_get_temp_dir(), 'alpha-reversible-');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null, 'mail.default' => 'array']);
DB::purge('sqlite');
$run = function (string $command, array $arguments): void {
    if (Artisan::call($command, $arguments) !== 0) {
        throw new RuntimeException('Falló el comando aislado '.$command);
    }
};
$run('migrate:fresh', ['--seed' => true, '--force' => true]);
$cliente = Cliente::create(['nombre' => 'Prueba de conservación', 'correo' => 'conservacion@example.test', 'password' => Str::random(32), 'activo' => true]);
$usuario = User::create(['name' => 'Prueba de conservación', 'email' => 'conservacion@example.test', 'password' => Str::random(32), 'rol' => 'Secretaria', 'activo' => true]);
$antesCliente = DB::table('clientes')->where('id', $cliente->id)->first(['id', 'nombre', 'correo', 'password', 'activo', 'created_at']);
$antesUsuario = DB::table('users')->where('id', $usuario->id)->first(['id', 'name', 'email', 'password', 'rol', 'activo', 'created_at']);
$nuevas = DB::table('migrations')->orderByDesc('id')->limit(6)->pluck('migration');
if ($nuevas->count() !== 6 || $nuevas->contains(fn ($name) => ! str_starts_with($name, '2026_10_04_'))) {
    throw new RuntimeException('La reversión solo puede afectar a las seis migraciones de esta auditoría.');
}
$run('migrate:rollback', ['--step' => 6, '--force' => true]);
$run('migrate', ['--force' => true]);
$despuesCliente = DB::table('clientes')->where('id', $cliente->id)->first(array_keys((array) $antesCliente));
$despuesUsuario = DB::table('users')->where('id', $usuario->id)->first(array_keys((array) $antesUsuario));
if ($antesCliente != $despuesCliente || $antesUsuario != $despuesUsuario) {
    throw new RuntimeException('Los campos preexistentes cambiaron durante la reversión.');
}
echo "PASS: seis migraciones nuevas revertidas y reaplicadas; cuentas, hashes y campos anteriores conservados.\n";
DB::disconnect('sqlite');
// This exact file was created above by tempnam; no directory or existing file is deleted.
if (! unlink($database)) {
    throw new RuntimeException('No se pudo retirar la base temporal de esta verificación.');
}

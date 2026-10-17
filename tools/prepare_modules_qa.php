<?php

// Disposable browser fixtures. No operations against the application's database.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/../vendor/autoload.php';
$database = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alpha-modules-qa-'.bin2hex(random_bytes(12)).'.sqlite';
$handle = fopen($database, 'x');
if (! $handle) {
    throw new RuntimeException('Could not create disposable QA database.');
}
fclose($handle);
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$database);
putenv('DB_URL=');
putenv('MAIL_MAILER=array');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null, 'mail.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
foreach (['migrate', 'db:seed'] as $command) {
    if (Illuminate\Support\Facades\Artisan::call($command, ['--force' => true]) !== 0) {
        throw new RuntimeException('QA fixtures failed: '.$command);
    }
}
$password = Illuminate\Support\Str::random(24).'!aA1';
foreach (['Administrador', 'Secretaria', 'Entrenador'] as $role) {
    $staff[$role] = App\Models\User::create(['name' => $role.' QA', 'email' => strtolower($role).'@example.test', 'password' => $password, 'rol' => $role, 'activo' => true]);
}
$cliente = App\Models\Cliente::create(['nombre' => 'Cliente vinculado QA', 'correo' => 'cliente-qa@example.test', 'password' => $password, 'activo' => true]);
$ajeno = App\Models\Cliente::create(['nombre' => 'Cliente ajeno QA', 'correo' => 'ajeno-qa@example.test', 'password' => $password, 'activo' => true]);
foreach (['Fuerza QA', 'Movilidad QA'] as $nombre) {
    App\Models\Rutina::create(['user_type' => 'cliente', 'user_id' => $cliente->id, 'nombre' => $nombre, 'objetivo' => 'Fuerza', 'nivel' => 'Principiante', 'dias_por_semana' => 2, 'asignado_por_id' => $staff['Entrenador']->id, 'asignado_por' => $staff['Entrenador']->name]);
}
App\Models\User::create(['name' => 'Entrenador inactivo QA', 'email' => 'inactivo-qa@example.test', 'password' => $password, 'rol' => 'Entrenador', 'activo' => false]);
App\Models\Producto::create(['nombre' => 'Oculto QA', 'precio' => 12, 'stock' => 5, 'activo' => false]);
$agotado = App\Models\Producto::create(['nombre' => 'Agotado QA', 'precio' => 3, 'stock' => 0, 'activo' => true, 'orden' => 0]);
$path = storage_path('app/private/modules-qa.json');
file_put_contents($path, json_encode(['database' => $database, 'password' => $password, 'url' => 'http://127.0.0.1:8031', 'cliente_id' => $cliente->id, 'ajeno_id' => $ajeno->id, 'entrenador_id' => $staff['Entrenador']->id, 'agotado_id' => $agotado->id], JSON_THROW_ON_ERROR));
echo "Disposable module fixtures prepared.\n";

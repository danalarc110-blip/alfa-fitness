<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// CLI-only fixtures for browser regression checks. Never reads or writes the application database.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/../vendor/autoload.php';
$database = tempnam(sys_get_temp_dir(), 'alpha-qa-');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null, 'mail.default' => 'array']);
DB::purge('sqlite');
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
$password = Str::random(24).'!aA1';
$cliente = Cliente::create(['nombre' => 'María · Prueba visual', 'correo' => 'qa-client@example.test', 'password' => $password, 'activo' => true]);
foreach (['Administrador', 'Secretaria', 'Entrenador'] as $role) {
    User::create(['name' => $role.' de prueba', 'email' => strtolower($role).'@example.test', 'password' => $password, 'rol' => $role, 'activo' => true]);
}
$path = storage_path('app/private/browser-qa.json');
file_put_contents($path, json_encode(['database' => $database, 'password' => $password, 'url' => 'http://127.0.0.1:8011'], JSON_THROW_ON_ERROR));
echo "Isolated browser fixtures prepared.\n";

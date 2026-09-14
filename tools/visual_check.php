<?php

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Local QA only: fixtures live in an in-memory database. No application data is changed.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array', 'app.url' => 'http://127.0.0.1:8000']);
DB::purge('sqlite');
if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('QA requires an in-memory database.');
}
Artisan::call('migrate', ['--force' => true]);
$admin = User::create(['name' => 'Administracion de prueba', 'email' => 'qa-admin@example.test', 'password' => Str::random(64), 'rol' => 'Administrador', 'activo' => true]);
$secretaria = User::create(['name' => 'Secretaria de prueba', 'email' => 'qa-secretaria@example.test', 'password' => Str::random(64), 'rol' => 'Secretaria', 'activo' => true]);
$cliente = Cliente::create(['nombre' => 'Cliente de prueba visual', 'correo' => 'cliente@example.test', 'password' => Str::random(64), 'activo' => true]);
Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Plan mensual de prueba', 'importe' => 25, 'inicio' => today(), 'fin' => today()->addDays(5)]);
Asistencia::create(['cliente_id' => $cliente->id, 'registrado_por' => $secretaria->id, 'fecha_hora' => now(), 'tipo_acceso' => 'entrada']);
Ejercicio::create(['nombre' => 'Sentadilla de prueba', 'grupo_muscular' => 'Piernas', 'activo' => true]);
$dir = storage_path('app/private/visual-check');
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
Auth::guard('web')->setUser($admin);
foreach (['dashboard' => 'admin', 'cuentas' => 'cuentas', 'productos' => 'productos', 'entrenadores' => 'entrenadores'] as $path => $file) {
    $request = Request::create('http://127.0.0.1:8000/'.$path);
    $response = $kernel->handle($request);
    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('QA render failed: '.$path);
    }
    file_put_contents($dir.'/'.$file.'.html', $response->getContent());
    $kernel->terminate($request, $response);
}
Auth::guard('web')->setUser($secretaria);
foreach (['asistencia' => 'asistencia', 'membresias' => 'membresias'] as $path => $file) {
    $request = Request::create('http://127.0.0.1:8000/'.$path);
    $response = $kernel->handle($request);
    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('QA render failed: '.$path);
    }
    file_put_contents($dir.'/'.$file.'.html', $response->getContent());
    $kernel->terminate($request, $response);
}
echo "QA pages generated using in-memory fixtures.\n";

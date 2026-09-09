<?php

// Local QA only: fixtures live in an in-memory database. No application data is changed.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array', 'app.url' => 'http://127.0.0.1:8000']);
Illuminate\Support\Facades\DB::purge('sqlite');
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== ':memory:') throw new RuntimeException('QA requires an in-memory database.');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$admin = App\Models\User::create(['name' => 'Administracion de prueba', 'email' => 'qa-admin@example.test', 'password' => Illuminate\Support\Str::random(64), 'rol' => 'Administrador', 'activo' => true]);
$secretaria = App\Models\User::create(['name' => 'Secretaria de prueba', 'email' => 'qa-secretaria@example.test', 'password' => Illuminate\Support\Str::random(64), 'rol' => 'Secretaria', 'activo' => true]);
$cliente = App\Models\Cliente::create(['nombre' => 'Cliente de prueba visual', 'correo' => 'cliente@example.test', 'password' => Illuminate\Support\Str::random(64), 'activo' => true]);
App\Models\Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Plan mensual de prueba', 'importe' => 25, 'inicio' => today(), 'fin' => today()->addDays(5)]);
App\Models\Asistencia::create(['cliente_id' => $cliente->id, 'registrado_por' => $secretaria->id, 'fecha_hora' => now(), 'tipo_acceso' => 'entrada']);
App\Models\Ejercicio::create(['nombre' => 'Sentadilla de prueba', 'grupo_muscular' => 'Piernas', 'activo' => true]);
$dir = public_path('.qa-alpha');
if (!is_dir($dir)) mkdir($dir, 0755, true);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
Illuminate\Support\Facades\Auth::guard('web')->setUser($admin);
foreach (['dashboard' => 'admin', 'cuentas' => 'cuentas', 'productos' => 'productos', 'entrenadores' => 'entrenadores'] as $path => $file) {
    $request = Illuminate\Http\Request::create('http://127.0.0.1:8000/'.$path);
    $response = $kernel->handle($request);
    if ($response->getStatusCode() !== 200) throw new RuntimeException('QA render failed: '.$path);
    file_put_contents($dir.'/'.$file.'.html', $response->getContent());
    $kernel->terminate($request, $response);
}
Illuminate\Support\Facades\Auth::guard('web')->setUser($secretaria);
foreach (['asistencia' => 'asistencia', 'membresias' => 'membresias'] as $path => $file) {
    $request = Illuminate\Http\Request::create('http://127.0.0.1:8000/'.$path);
    $response = $kernel->handle($request);
    if ($response->getStatusCode() !== 200) throw new RuntimeException('QA render failed: '.$path);
    file_put_contents($dir.'/'.$file.'.html', $response->getContent());
    $kernel->terminate($request, $response);
}
echo "QA pages generated using in-memory fixtures.\n";

<?php

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\Producto;
use App\Models\Rutina;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Disposable data for before/after screenshots. Never touches the application database.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';
$database = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alpha-review-'.bin2hex(random_bytes(12)).'.sqlite';
$handle = fopen($database, 'x');
if (! $handle) {
    throw new RuntimeException('Unable to create disposable review database.');
}
fclose($handle);
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$database);
putenv('DB_URL=');
putenv('MAIL_MAILER=array');
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null, 'mail.default' => 'array']);
DB::purge('sqlite');
foreach (['migrate', 'db:seed'] as $command) {
    if (Artisan::call($command, ['--force' => true]) !== 0) {
        throw new RuntimeException('Unable to prepare isolated review data.');
    }
}
$password = Str::random(24).'!aA1';
foreach (['Administrador', 'Secretaria', 'Entrenador'] as $role) {
    $staff[$role] = User::create(['name' => $role.' de prueba', 'email' => strtolower($role).'@example.test', 'password' => $password, 'rol' => $role, 'activo' => true]);
}
$cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'correo' => 'cliente@example.test', 'password' => $password, 'activo' => true]);
Rutina::create(['user_type' => 'cliente', 'user_id' => $cliente->id, 'nombre' => 'Fuerza y movilidad', 'objetivo' => 'Fuerza', 'nivel' => 'Principiante', 'dias_por_semana' => 3, 'asignado_por_id' => $staff['Entrenador']->id, 'asignado_por' => $staff['Entrenador']->name]);
$producto = Producto::where('activo', true)->firstOrFail();
for ($i = 0; $i < 45; $i++) {
    $date = now()->subDays($i)->setTime(8, 0);
    if ($date->isFuture()) {
        $date = now()->subMinute();
    }
    if ($i % 3 === 1) {
        continue;
    }
    $cantidad = 1 + ($i % 4);
    $total = number_format((float) $producto->precio * $cantidad, 2, '.', '');
    $venta = Venta::create(['venta_uuid' => Str::uuid(), 'user_id' => $staff['Secretaria']->id, 'cliente_id' => $cliente->id, 'total' => $total, 'metodo_pago' => 'Efectivo', 'created_at' => $date]);
    $venta->created_at = $date;
    $venta->save();
    $venta->detalles()->create(['producto_id' => $producto->id, 'cantidad' => $cantidad, 'precio_unitario' => $producto->precio, 'subtotal' => $total]);
    if ($i % 3 === 0) {
        $solicitud = SolicitudMembresia::create(['cliente_id' => $cliente->id, 'plan_nombre' => 'Mensual', 'precio_acordado' => 25, 'duracion_dias' => 30, 'estado' => 'activada']);
        $membresia = Membresia::create(['cliente_id' => $cliente->id, 'solicitud_id' => $solicitud->id, 'plan' => 'Mensual', 'importe' => 25, 'inicio' => $date->toDateString(), 'fin' => $date->copy()->addDays(29)->toDateString(), 'cancelada' => false, 'activada_por' => $staff['Secretaria']->id]);
        PagoMembresia::create(['membresia_id' => $membresia->id, 'solicitud_id' => $solicitud->id, 'registrado_por' => $staff['Secretaria']->id, 'importe' => 25, 'pagado_en' => $date]);
    }
}
file_put_contents(storage_path('app/private/review-qa.json'), json_encode(['database' => $database, 'password' => $password, 'url' => 'http://127.0.0.1:8041', 'mes' => now()->format('Y-m'), 'today' => today()->toDateString()], JSON_THROW_ON_ERROR));
echo "Isolated review fixtures ready.\n";

<?php

use App\Services\AnaliticaFinancieraService;
use App\Services\ReportePdfService;
use Illuminate\Contracts\Console\Kernel;

// Render only against the isolated browser fixture; never the configured app database.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/../vendor/autoload.php';
$root = dirname(__DIR__);
$fixture = json_decode(file_get_contents($root.'/storage/app/private/review-qa.json'), true, flags: JSON_THROW_ON_ERROR);
$database = $fixture['database'];
if (strtolower(realpath(dirname($database))) !== strtolower(realpath(sys_get_temp_dir())) || ! str_starts_with(basename($database), 'alpha-review-')) {
    throw new RuntimeException('Disposable fixture required.');
}
putenv('APP_CONFIG_CACHE='.sys_get_temp_dir().'/alpha-report-qa-'.bin2hex(random_bytes(12)).'.php');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$database);
putenv('DB_URL=');
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $database || config('database.connections.sqlite.url')) {
    throw new RuntimeException('Disposable connection required.');
}
$service = app(AnaliticaFinancieraService::class);
$period = $service->resolverPeriodo('hoy', null, null);
$data = $service->obtenerAnalitica($period['desde'], $period['hasta'], $period['dias']);
$response = app(ReportePdfService::class)->descargar($data, 'reporte.pdf');
file_put_contents($root.'/outputs/revision-2026-11-04/entrada-diaria/reporte-tres-ingresos.pdf', $response->getContent());
echo "Disposable financial report rendered.\n";

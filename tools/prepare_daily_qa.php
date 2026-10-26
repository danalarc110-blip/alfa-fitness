<?php

// Disposable fixtures and report preview; the application database is never used.
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\AnaliticaFinancieraService;
use App\Services\ReportePdfService;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
try {
    require __DIR__.'/prepare_modules_qa.php';

    $admin = User::where('email', 'administrador@example.test')->firstOrFail();
    $producto = Producto::create(['nombre' => 'Agua de prueba diaria', 'precio' => '2.50', 'stock' => 50, 'activo' => true]);
    foreach ([['25.00', '25.00', false], ['10.00', '8.00', false], ['100.00', '100.00', true]] as [$total, $detalle, $simulado]) {
        $venta = Venta::create([
            'user_id' => $admin->id, 'total' => $total, 'metodo_pago' => 'Efectivo', 'pago_simulado' => $simulado,
        ]);
        $venta->detalles()->create([
            'producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => $detalle, 'subtotal' => $detalle,
        ]);
    }
    $service = app(AnaliticaFinancieraService::class);
    $periodo = $service->resolverPeriodo('personalizado', today()->subDays(6)->toDateString(), today()->toDateString());
    $datos = $service->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);
    $directory = storage_path('app/private/revision-diaria');
    if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
        throw new RuntimeException('Cannot create private QA output directory.');
    }
    file_put_contents($directory.'/reporte-qa.pdf', app(ReportePdfService::class)->descargar($datos, 'reporte-qa.pdf')->getContent());
    echo "Daily report preview prepared using synthetic data only.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}

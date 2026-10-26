<?php

use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Models\PlanMembresia;

// This helper extends the disposable fixtures; it never uses the application's DB.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/prepare_modules_qa.php';

$fixtures = json_decode(file_get_contents(storage_path('app/private/modules-qa.json')), true, flags: JSON_THROW_ON_ERROR);
$ejercicios = Ejercicio::where('activo', true)->orderBy('id')->limit(2)->get();
for ($i = 0; $i < 8; $i++) {
    PersonalRecord::create([
        'cliente_id' => $fixtures['cliente_id'], 'ejercicio_id' => $ejercicios[$i % 2]->id,
        'peso_kg' => 20 + $i * 2, 'repeticiones' => 5, 'notas' => 'Serie de prueba móvil',
    ]);
}
PlanMembresia::create([
    'nombre' => 'Plan móvil de prueba', 'precio' => '12.00', 'duracion_dias' => 30,
    'condiciones' => 'Datos de demostración', 'activo' => true,
]);
$fixtures['url'] = 'http://127.0.0.1:8034';
$fixtures['ejercicio_id'] = $ejercicios[0]->id;
file_put_contents(storage_path('app/private/mobile-qa.json'), json_encode($fixtures, JSON_THROW_ON_ERROR));
echo "Mobile browser fixtures prepared in a disposable database.\n";

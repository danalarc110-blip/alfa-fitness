<?php

use App\Support\RenderBootstrap;

// This runs before Laravel: an absent database must never be silently replaced by an empty one.
require dirname(__DIR__).'/vendor/autoload.php';

try {
    $result = RenderBootstrap::runFromEnvironment();
    fwrite(STDOUT, $result === 'imported' ? "Datos iniciales importados y verificados.\n" : "Base persistente verificada; se conservan los datos existentes.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'No se pudo preparar la base persistente: '.$error->getMessage()."\n");
    exit(1);
}

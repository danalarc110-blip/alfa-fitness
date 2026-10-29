<?php

return [
    'version' => '2026-10-28',
    'demo' => (bool) env('PRIVACY_DEMO', true),
    'responsible_name' => env('PRIVACY_RESPONSIBLE_NAME') ?: 'Equipo académico Alpha Fitness',
    'contact_email' => env('PRIVACY_CONTACT_EMAIL') ?: 'privacidad@alpha-fitness.example',
    'retention' => env('PRIVACY_RETENTION') ?: 'En la demostración, se prevé eliminar los datos de prueba al finalizar la evaluación académica, como máximo 90 días después de su cierre. Las copias de prueba se eliminarán dentro de los 30 días siguientes. Esta eliminación requiere una actuación del equipo responsable; no es automática.',
];

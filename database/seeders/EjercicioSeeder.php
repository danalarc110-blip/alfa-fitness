<?php

namespace Database\Seeders;

use App\Models\Ejercicio;
use Illuminate\Database\Seeder;

class EjercicioSeeder extends Seeder
{
    public function run(): void
    {
        $ejercicios = [
            // Ejercicio con imagenes ya puestas en public/images/ejercicios
            ['nombre' => 'Press de banca', 'grupo_muscular' => 'Pecho', 'subgrupo' => null,
                'imagen' => 'press banca.png', 'imagen_musculos' => 'press banca musculos que entrena.png'],

            // Resto: sin imagen todavia. Ruta queda lista por convencion:
            // public/images/ejercicios/{nombre en minuscula}.png
            // public/images/ejercicios/{nombre en minuscula} musculos que entrena.png
            ['nombre' => 'Press inclinado con mancuernas', 'grupo_muscular' => 'Pecho', 'subgrupo' => 'Pecho superior',
                'imagen' => 'press de anca inclinado_mancuernas.png',
                'imagen_musculos' => 'musculos entrenados press de anca inclinado_mancuernas.png'],
            ['nombre' => 'Aperturas en máquina',           'grupo_muscular' => 'Pecho', 'subgrupo' => null],
            ['nombre' => 'Press plano con mancuernas',     'grupo_muscular' => 'Pecho', 'subgrupo' => null],
            ['nombre' => 'Cruces en polea',                'grupo_muscular' => 'Pecho', 'subgrupo' => null],
            ['nombre' => 'Press declinado',                'grupo_muscular' => 'Pecho', 'subgrupo' => 'Pecho inferior'],
            ['nombre' => 'Pullover en polea',               'grupo_muscular' => 'Pecho', 'subgrupo' => null],
            ['nombre' => 'Flexiones',                       'grupo_muscular' => 'Pecho', 'subgrupo' => null],
            ['nombre' => 'Fondos en paralelas',             'grupo_muscular' => 'Triceps', 'subgrupo' => null],
            ['nombre' => 'Extensión de tríceps en polea',   'grupo_muscular' => 'Triceps', 'subgrupo' => null],
            ['nombre' => 'Press francés',                   'grupo_muscular' => 'Triceps', 'subgrupo' => null],
        ];

        foreach ($ejercicios as $e) {
            Ejercicio::updateOrCreate(
                ['nombre' => $e['nombre']],
                [
                    'grupo_muscular' => $e['grupo_muscular'],
                    'subgrupo' => $e['subgrupo'],
                    'imagen' => $e['imagen'] ?? mb_strtolower($e['nombre']).'.png',
                    'imagen_musculos' => $e['imagen_musculos'] ?? mb_strtolower($e['nombre']).' musculos que entrena.png',
                    'activo' => true,
                ]
            );
        }
    }
}

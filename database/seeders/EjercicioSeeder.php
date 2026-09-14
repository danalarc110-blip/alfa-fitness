<?php

namespace Database\Seeders;

use App\Models\Ejercicio;
use Illuminate\Database\Seeder;

class EjercicioSeeder extends Seeder
{
    public function run(): void
    {
        $ejercicios = [
            [
                'nombre' => 'Press de banca',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Pecho medio',
                'imagen' => 'press banca.webp',
                'imagen_musculos' => 'press banca musculos que entrena.png',
            ],
            [
                'nombre' => 'Press inclinado con mancuernas',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Pecho superior',
                'imagen' => 'press de banca inclinado con mancuernas.webp',
                'imagen_musculos' => 'musculos entrenados press de anca inclinado_mancuernas.png',
            ],
            [
                'nombre' => 'Aperturas en máquina',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Pecho medio',
                'imagen' => 'aperturas en máquina.webp',
                'imagen_musculos' => 'aperturas en máquina musculos que entrena.png',
            ],
            [
                'nombre' => 'Press plano con mancuernas',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Pecho medio',
                'imagen' => 'press plano con mancuernas.webp',
                'imagen_musculos' => 'press plano con mancuernas musculos que entrena.png',
            ],
            [
                'nombre' => 'Cruces en polea',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Aislamiento',
                'imagen' => 'Cruces en polea.webp',
                'imagen_musculos' => 'cruces en polea musculos que entrena.png',
            ],
            [
                'nombre' => 'Press declinado',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Pecho inferior',
                'imagen' => 'Press declinado.webp',
                'imagen_musculos' => 'press declinado musculos que entrena.png',
            ],
            [
                'nombre' => 'Pullover en polea',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Expansión torácica',
                'imagen' => 'Pullover en polea.webp',
                'imagen_musculos' => 'pullover en polea musculos que entrena.png',
            ],
            [
                'nombre' => 'Flexiones',
                'grupo_muscular' => 'Pecho',
                'subgrupo' => 'Calistenia',
                'imagen' => 'Flexiones.webp',
                'imagen_musculos' => 'flexiones musculos que entrena.png',
            ],
            [
                'nombre' => 'Fondos en paralelas',
                'grupo_muscular' => 'Triceps',
                'subgrupo' => 'Fuerza funcional',
                'imagen' => 'Fondos en paralelas.webp',
                'imagen_musculos' => 'fondos en paralelas musculos que entrena.png',
            ],
            [
                'nombre' => 'Extensión de tríceps en polea',
                'grupo_muscular' => 'Triceps',
                'subgrupo' => 'Aislamiento tríceps',
                'imagen' => 'Extensión de tríceps en polea.webp',
                'imagen_musculos' => 'extensión de tríceps en polea musculos que entrena.png',
            ],
            [
                'nombre' => 'Press francés',
                'grupo_muscular' => 'Triceps',
                'subgrupo' => 'Cabeza larga',
                'imagen' => 'Press francés.webp',
                'imagen_musculos' => 'press francés musculos que entrena.png',
            ],
        ];

        foreach ($ejercicios as $e) {
            Ejercicio::updateOrCreate(
                ['nombre' => $e['nombre']],
                [
                    'grupo_muscular' => $e['grupo_muscular'],
                    'subgrupo' => $e['subgrupo'],
                    'imagen' => $e['imagen'],
                    'imagen_musculos' => $e['imagen_musculos'],
                    'activo' => true,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            ['nombre' => 'Agua mineral 600 ml', 'precio' => 1.00, 'categoria' => 'Bebidas', 'stock' => 30],
            ['nombre' => 'Bebida isotónica', 'precio' => 1.75, 'categoria' => 'Bebidas', 'stock' => 24],
            ['nombre' => 'Barra de proteína', 'precio' => 2.25, 'categoria' => 'Snacks', 'stock' => 18],
            ['nombre' => 'Proteína whey 2 lb', 'precio' => 35.00, 'categoria' => 'Suplementos', 'stock' => 10],
            ['nombre' => 'Creatina monohidratada 300 g', 'precio' => 25.00, 'categoria' => 'Suplementos', 'stock' => 8],
        ];

        foreach ($catalogo as $indice => $datos) {
            if (Producto::count() >= 5) {
                break;
            }

            Producto::firstOrCreate(
                ['nombre' => $datos['nombre']],
                $datos + ['activo' => true, 'orden' => $indice + 1],
            );
        }
    }
}

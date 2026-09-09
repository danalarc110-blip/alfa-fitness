<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Solo catalogo publico. Nunca se crean cuentas ni contrasenas conocidas.
        $this->call(EjercicioSeeder::class);
    }
}

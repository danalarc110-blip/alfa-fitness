<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Solo catalogos publicos. Nunca se crean cuentas ni contrasenas conocidas.
        $this->call(EjercicioSeeder::class);
        $this->call(ProductoSeeder::class);
    }
}

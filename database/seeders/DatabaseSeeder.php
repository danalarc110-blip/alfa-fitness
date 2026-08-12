<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario administrador (empleado)
        User::updateOrCreate(
            ['email' => 'admin@alphafitness.com'],
            [
                'name' => 'Administrador',
                'rol' => 'Administrador',
                'activo' => true,
                'password' => bcrypt('admin123'),
            ]
        );

        // Usuario secretaria (empleado)
        // Por ahora todos los roles tienen los mismos permisos: no hay
        // restricciones distintas entre Administrador y Secretaria todavía.
        User::updateOrCreate(
            ['email' => 'secretaria@alphafitness.com'],
            [
                'name' => 'Secretaria',
                'rol' => 'Secretaria',
                'activo' => true,
                'password' => bcrypt('secretaria123'),
            ]
        );

        // Catálogo de ejercicios
        $this->call(EjercicioSeeder::class);
    }
}

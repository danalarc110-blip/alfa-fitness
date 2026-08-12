<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejercicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('grupo_muscular');   // Pecho, Espalda, Piernas, Hombros, Biceps, Triceps, Abdomen
            $table->string('subgrupo')->nullable(); // Ej: "Pecho superior", "Pecho inferior"
            $table->string('imagen')->nullable();   // nombre de archivo dentro de images/ejercicios
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicios');
    }
};

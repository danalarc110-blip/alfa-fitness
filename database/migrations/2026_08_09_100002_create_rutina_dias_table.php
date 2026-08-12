<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutina_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rutina_id')->constrained('rutinas')->cascadeOnDelete();

            $table->unsignedTinyInteger('orden');       // 1, 2, 3... controla el orden de las pestañas
            $table->string('titulo');                   // "Pecho & Tríceps", "Piernas (Enfoque)"...
            $table->unsignedSmallInteger('duracion_estimada_min')->nullable(); // duración mínima estimada
            $table->unsignedSmallInteger('duracion_estimada_max')->nullable(); // duración máxima estimada

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutina_dias');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutina_ejercicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rutina_dia_id')->constrained('rutina_dias')->cascadeOnDelete();
            $table->foreignId('ejercicio_id')->constrained('ejercicios')->cascadeOnDelete();

            $table->unsignedTinyInteger('orden');           // posición dentro del día
            $table->unsignedTinyInteger('series')->default(3);
            $table->string('repeticiones')->default('8-10'); // rango tipo "8-10" o valor fijo "12"
            $table->decimal('peso', 6, 2)->nullable();        // null = "—" (sin peso / peso corporal)
            $table->unsignedSmallInteger('descanso_segundos')->default(60);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutina_ejercicios');
    }
};

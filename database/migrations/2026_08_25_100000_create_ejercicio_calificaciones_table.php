<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejercicio_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejercicio_id')->constrained('ejercicios')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type'); // 'web' o 'cliente'
            $table->unsignedTinyInteger('estrellas'); // 1 a 5
            $table->timestamps();

            $table->unique(['ejercicio_id', 'user_id', 'user_type'], 'ejercicio_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicio_calificaciones');
    }
};

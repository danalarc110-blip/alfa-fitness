<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrenador_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrenador_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->unsignedTinyInteger('estrellas');
            $table->timestamps();
            $table->unique(['entrenador_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrenador_calificaciones');
    }
};

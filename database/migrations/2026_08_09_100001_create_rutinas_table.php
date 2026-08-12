<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutinas', function (Blueprint $table) {
            $table->id();

            // Dueño de la rutina: puede ser un empleado (users) o un cliente (clientes).
            // Se guarda el guard en vez de usar una FK directa, siguiendo el mismo
            // patrón que ya usa ConfiguracionController para distinguir ambos tipos.
            $table->unsignedBigInteger('user_id');
            $table->string('user_type'); // 'web' | 'cliente'

            $table->string('nombre');
            $table->string('objetivo');           // Ganar masa muscular, Perder grasa, etc.
            $table->string('nivel');              // Principiante, Intermedio, Avanzado
            $table->unsignedTinyInteger('dias_por_semana')->default(1);
            $table->boolean('activa')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutinas');
    }
};

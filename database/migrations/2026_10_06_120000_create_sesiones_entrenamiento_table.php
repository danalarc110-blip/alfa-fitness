<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_entrenamiento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type'); // 'cliente' o 'web'
            $table->foreignId('rutina_id')->nullable()->constrained('rutinas')->nullOnDelete();
            $table->string('rutina_nombre');
            $table->foreignId('dia_id')->nullable()->constrained('rutina_dias')->nullOnDelete();
            $table->string('dia_titulo')->default('Entrenamiento');
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->unsignedInteger('duracion_segundos')->default(0);
            $table->unsignedInteger('series_completadas')->default(0);
            $table->unsignedInteger('total_series')->default(0);
            $table->string('estado')->default('completado');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_entrenamiento');
    }
};

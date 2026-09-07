<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_hora')->useCurrent();
            $table->string('tipo_acceso', 30)->default('entrada');
            $table->timestamps();

            $table->index(['cliente_id', 'fecha_hora']);
            $table->index('fecha_hora');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};

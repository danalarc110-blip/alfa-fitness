<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('entrenador_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('sesiones_entrenador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('entrenador_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('estado', 20)->default('programada');
            $table->string('notas', 500)->nullable();
            $table->timestamps();
            $table->index(['entrenador_id', 'fecha_inicio', 'estado'], 'sesiones_entrenador_agenda_idx');
            $table->index(['cliente_id', 'fecha_inicio'], 'sesiones_cliente_agenda_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_entrenador');
        Schema::table('clientes', fn (Blueprint $table) => $table->dropConstrainedForeignId('entrenador_id'));
    }
};

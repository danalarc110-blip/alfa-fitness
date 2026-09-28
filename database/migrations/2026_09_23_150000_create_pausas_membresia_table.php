<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausas_membresia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membresia_id')->constrained('membresias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->unsignedInteger('dias');
            $table->string('motivo', 255);
            $table->string('estado', 30)->default('pendiente'); // pendiente, aprobada, finalizada, rechazada, reanudada_anticipada
            $table->date('inicio_pausa');
            $table->date('fin_pausa_estimada');
            $table->date('fecha_reanudacion')->nullable();
            $table->foreignId('aprobada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['membresia_id', 'estado']);
            $table->index(['cliente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausas_membresia');
    }
};

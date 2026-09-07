<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('ejercicio_id')->constrained('ejercicios')->cascadeOnDelete();
            $table->decimal('peso_kg', 6, 2);
            $table->unsignedTinyInteger('repeticiones')->default(1);
            $table->boolean('verificado')->default(false);
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_verificacion')->nullable();
            $table->string('notas')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'ejercicio_id', 'created_at'], 'pr_cliente_ejercicio_fecha_idx');
            $table->index(['ejercicio_id', 'verificado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};

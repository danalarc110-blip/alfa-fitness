<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->index(['cliente_id', 'fecha_hora'], 'idx_asistencias_cliente_fecha');
            $table->index(['fecha_salida', 'fecha_hora'], 'idx_asistencias_salida_fecha');
        });

        Schema::table('personal_records', function (Blueprint $table) {
            $table->index(['cliente_id', 'ejercicio_id'], 'idx_pr_cliente_ejercicio');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('idx_asistencias_cliente_fecha');
            $table->dropIndex('idx_asistencias_salida_fecha');
        });

        Schema::table('personal_records', function (Blueprint $table) {
            $table->dropIndex('idx_pr_cliente_ejercicio');
        });
    }
};

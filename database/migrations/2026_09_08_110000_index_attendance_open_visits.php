<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', fn (Blueprint $table) => $table->index(['fecha_salida', 'cliente_id'], 'asistencias_abiertas_cliente_idx'));
    }

    public function down(): void
    {
        Schema::table('asistencias', fn (Blueprint $table) => $table->dropIndex('asistencias_abiertas_cliente_idx'));
    }
};

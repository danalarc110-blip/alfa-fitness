<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ventas', 'asistencias'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->timestamp('anulada_en')->nullable();
                $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
                $table->string('motivo_anulacion', 255)->nullable();
                $table->index(['anulada_en', $tabla === 'ventas' ? 'created_at' : 'fecha_hora'], $tabla.'_vigentes_idx');
            });
        }
        Schema::create('historial_correcciones', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 32);
            $table->unsignedBigInteger('registro_id');
            $table->string('accion', 32);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guard', 20);
            $table->string('motivo', 255);
            $table->json('antes');
            $table->json('despues');
            $table->timestamp('created_at');
            $table->index(['tabla', 'registro_id', 'created_at'], 'correcciones_registro_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_correcciones');
        foreach (['ventas', 'asistencias'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropIndex($tabla.'_vigentes_idx');
                $table->dropConstrainedForeignId('anulada_por');
                $table->dropColumn(['anulada_en', 'motivo_anulacion']);
            });
        }
    }
};

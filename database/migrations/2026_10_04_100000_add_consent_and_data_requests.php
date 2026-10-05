<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->timestamp('legal_aceptado_en')->nullable();
            $table->string('legal_version', 32)->nullable();
            $table->boolean('legal_requerido')->default(false);
        });
        Schema::table('pagos_membresia', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'tarjeta'])->nullable();
        });
        Schema::create('solicitudes_datos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo');
            $table->string('tipo', 32);
            $table->text('detalle');
            $table->string('estado', 32)->default('pendiente');
            $table->text('notas_internas')->nullable();
            $table->timestamp('resuelta_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_datos');
        Schema::table('pagos_membresia', fn (Blueprint $table) => $table->dropColumn('metodo_pago'));
        Schema::table('clientes', fn (Blueprint $table) => $table->dropColumn(['legal_aceptado_en', 'legal_version', 'legal_requerido']));
    }
};

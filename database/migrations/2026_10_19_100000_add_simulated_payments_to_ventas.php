<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('pago_simulado')->default(false)->index();
            $table->decimal('efectivo_recibido', 12, 2)->nullable();
            $table->decimal('cambio', 12, 2)->nullable();
            $table->string('referencia_pago', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['pago_simulado']);
            $table->dropColumn(['pago_simulado', 'efectivo_recibido', 'cambio', 'referencia_pago']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->uuid('entrada_uuid')->nullable()->unique();
        });

        Schema::create('pagos_entrada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->unique()->constrained('asistencias')->restrictOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->decimal('importe', 10, 2);
            $table->timestamp('pagado_en')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_entrada');
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropUnique('asistencias_entrada_uuid_unique');
            $table->dropColumn('entrada_uuid');
        });
    }
};

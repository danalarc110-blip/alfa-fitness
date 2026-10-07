<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los nombres históricos no identifican de forma inequívoca a un entrenador.
        Schema::table('rutinas', function (Blueprint $table) {
            $table->foreignId('asignado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['asignado_por_id', 'user_type', 'user_id'], 'rutinas_asignador_propietario_idx');
        });
        Schema::table('sesiones_entrenamiento', function (Blueprint $table) {
            $table->uuid('sesion_uuid')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('sesiones_entrenamiento', function (Blueprint $table) {
            $table->dropUnique(['sesion_uuid']);
            $table->dropColumn('sesion_uuid');
        });
        Schema::table('rutinas', function (Blueprint $table) {
            $table->dropIndex('rutinas_asignador_propietario_idx');
            $table->dropConstrainedForeignId('asignado_por_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            $table->string('asignado_por')->nullable()->after('activa');
        });
    }

    public function down(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            $table->dropColumn('asignado_por');
        });
    }
};

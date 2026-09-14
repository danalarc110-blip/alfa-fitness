<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'clientes'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->json('apariencia')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['users', 'clientes'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->dropColumn('apariencia'));
        }
    }
};

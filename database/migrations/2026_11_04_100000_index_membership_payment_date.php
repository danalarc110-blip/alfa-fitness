<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_membresia', function (Blueprint $table) {
            $table->index('pagado_en', 'pagos_membresia_pagado_en_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pagos_membresia', function (Blueprint $table) {
            $table->dropIndex('pagos_membresia_pagado_en_idx');
        });
    }
};

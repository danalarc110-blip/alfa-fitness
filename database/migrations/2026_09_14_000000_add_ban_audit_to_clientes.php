<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->timestamp('baneado_en')->nullable()->after('activo');
            $table->foreignId('baneado_por')->nullable()->after('baneado_en')->constrained('users')->nullOnDelete();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropConstrainedForeignId('baneado_por');
            $table->dropColumn('baneado_en');
        });
    }
};

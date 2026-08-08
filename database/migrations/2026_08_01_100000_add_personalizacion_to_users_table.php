<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('color_acento')->default('#facc15')->after('activo');
            $table->string('avatar_piel')->default('claro')->after('color_acento');
            $table->string('avatar_cabello')->default('corto')->after('avatar_piel');
            $table->string('avatar_barba')->default('ninguna')->after('avatar_cabello');
            $table->string('avatar_atuendo')->default('basica')->after('avatar_barba');
            $table->string('avatar_color_atuendo')->default('#1f2937')->after('avatar_atuendo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'color_acento', 'avatar_piel', 'avatar_cabello',
                'avatar_barba', 'avatar_atuendo', 'avatar_color_atuendo',
            ]);
        });
    }
};

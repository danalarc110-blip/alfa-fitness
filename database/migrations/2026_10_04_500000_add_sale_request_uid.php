<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', fn (Blueprint $table) => $table->uuid('request_uid')->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_request_uid_unique');
            $table->dropColumn('request_uid');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'clientes'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('privacy_notice_version', 40)->nullable();
                $table->timestamp('privacy_notice_read_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'clientes'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['privacy_notice_version', 'privacy_notice_read_at']);
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Old tokens have no provider identity; expire them rather than trust an ambiguous token.
        DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_password_reset_tokens');
    }
};

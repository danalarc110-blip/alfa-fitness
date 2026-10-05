<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versiones_legales', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->unique();
            $table->char('hash_contenido', 64);
            $table->json('documentos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versiones_legales');
    }
};

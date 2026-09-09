<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 100);
            $table->decimal('importe', 10, 2);
            $table->date('inicio');
            $table->date('fin');
            $table->boolean('cancelada')->default(false);
            $table->timestamps();
            $table->index(['cliente_id', 'cancelada', 'fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membresias');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->decimal('total', 10, 2);
            $table->string('metodo_pago', 50)->default('Efectivo');
            $table->string('notas', 255)->nullable();
            $table->timestamps();

            $table->index(['created_at']);
        });

        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->index(['venta_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
        Schema::dropIfExists('ventas');
    }
};

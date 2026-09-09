<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $administradores = DB::table('users')->where('rol', 'Administrador')->count();
        if ($administradores > 1) {
            throw new RuntimeException("Se detectaron {$administradores} cuentas Administrador. Resuelva el conflicto antes de ejecutar esta migracion; ninguna cuenta fue modificada.");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_establecida')->default(true)->after('password');
            $table->string('admin_key', 20)->nullable()->unique()->after('rol');
        });

        DB::table('users')->where('rol', 'Recepcionista')->update(['rol' => 'Secretaria']);
        DB::table('users')->where('rol', 'Administrador')->update(['admin_key' => 'unico']);
        Schema::table('users', fn (Blueprint $table) => $table->string('rol')->default('Secretaria')->change());

        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('salida_registrada_por')->nullable()->after('registrado_por')->constrained('users')->nullOnDelete();
            $table->index(['cliente_id', 'fecha_hora', 'fecha_salida'], 'asistencias_historial_idx');
        });

        Schema::create('planes_membresia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->decimal('precio', 10, 2);
            $table->unsignedSmallInteger('duracion_dias');
            $table->text('condiciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('solicitudes_membresia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('planes_membresia')->nullOnDelete();
            $table->string('plan_nombre', 100);
            $table->decimal('precio_acordado', 10, 2);
            $table->unsignedSmallInteger('duracion_dias');
            $table->text('condiciones')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('resuelta_en')->nullable();
            $table->foreignId('resuelta_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
            $table->index(['cliente_id', 'estado']);
        });

        Schema::table('membresias', function (Blueprint $table) {
            $table->foreignId('solicitud_id')->nullable()->unique()->after('cliente_id')->constrained('solicitudes_membresia')->nullOnDelete();
            $table->foreignId('activada_por')->nullable()->after('cancelada')->constrained('users')->nullOnDelete();
        });

        Schema::create('pagos_membresia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membresia_id')->unique()->constrained('membresias')->restrictOnDelete();
            $table->foreignId('solicitud_id')->unique()->constrained('solicitudes_membresia')->restrictOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('importe', 10, 2);
            $table->timestamp('pagado_en');
            $table->string('referencia', 100)->nullable();
            $table->timestamps();
        });

        $ahora = now();
        DB::table('planes_membresia')->insert([
            ['nombre' => 'Mensual', 'precio' => 25, 'duracion_dias' => 30, 'condiciones' => 'Acceso al gimnasio durante 30 dias.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Trimestral', 'precio' => 65, 'duracion_dias' => 90, 'condiciones' => 'Acceso al gimnasio durante 90 dias.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Anual', 'precio' => 220, 'duracion_dias' => 365, 'condiciones' => 'Acceso al gimnasio durante 365 dias.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_membresia');
        Schema::table('membresias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activada_por');
            $table->dropConstrainedForeignId('solicitud_id');
        });
        Schema::dropIfExists('solicitudes_membresia');
        Schema::dropIfExists('planes_membresia');
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('asistencias_historial_idx');
            $table->dropConstrainedForeignId('salida_registrada_por');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['admin_key']);
            $table->dropColumn(['password_establecida', 'admin_key']);
        });
    }
};

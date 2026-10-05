<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\CorreccionHistorial;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistorialOperativoTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $correo = 'historial@example.test'): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente del historial', 'correo' => $correo, 'password' => 'ClaveSegura!2026', 'activo' => true]);
    }

    private function venta(User $secretaria, ?Cliente $cliente = null): array
    {
        $producto = Producto::create(['nombre' => 'Producto historial', 'precio' => 5, 'stock' => 10, 'activo' => true]);
        $this->actingAs($secretaria)->post(route('ventas.store'), [
            'cliente_id' => $cliente?->id, 'metodo_pago' => 'Efectivo', 'notas' => 'Nota original',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3]],
        ])->assertSessionHasNoErrors();

        return [Venta::firstOrFail(), $producto];
    }

    private function asistencia(Cliente $cliente, User $secretaria, array $extra = []): Asistencia
    {
        return Asistencia::create(array_merge([
            'cliente_id' => $cliente->id, 'registrado_por' => $secretaria->id,
            'salida_registrada_por' => $secretaria->id, 'fecha_hora' => today()->setTime(8, 0),
            'fecha_salida' => today()->setTime(9, 0), 'tipo_acceso' => 'entrada',
        ], $extra));
    }

    public function test_nota_venta_se_corrige_sin_tocar_importes_producto_cliente_ni_pago(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        [$venta, $producto] = $this->venta($secretaria, $cliente);
        $detalle = $venta->detalles()->firstOrFail();
        $this->get(route('gestion.historial.ventas.edit', $venta))->assertOk();
        $this->put(route('gestion.historial.ventas.update', $venta), [
            'notas' => 'Nota corregida', 'motivo' => 'Corregir referencia de entrega',
            'total' => 0, 'metodo_pago' => 'Tarjeta', 'user_id' => 999, 'cliente_id' => 999,
            'items' => [['producto_id' => 999, 'cantidad' => 500]],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $venta->refresh();
        $this->assertSame('Nota corregida', $venta->notas);
        $this->assertSame('15.00', $venta->total);
        $this->assertSame('Efectivo', $venta->metodo_pago);
        $this->assertSame($secretaria->id, $venta->user_id);
        $this->assertSame($cliente->id, $venta->cliente_id);
        $this->assertSame(3, $detalle->fresh()->cantidad);
        $this->assertSame(7, $producto->fresh()->stock);
        $registro = DB::table('historial_correcciones')->first();
        $this->assertSame('ventas', $registro->tabla);
        $this->assertSame($secretaria->id, $registro->actor_id);
        $this->assertSame('web', $registro->guard);
        $this->assertSame(['notas' => 'Nota original'], json_decode($registro->antes, true));
        $this->assertSame(['notas' => 'Nota corregida'], json_decode($registro->despues, true));
        $this->assertStringNotContainsString($cliente->correo, $registro->antes.$registro->despues);
        $this->get(route('gestion.historial.ventas.index', ['q' => 'Nota corregida']))->assertOk()
            ->assertViewHas('registros', fn ($registros) => $registros->total() === 1);
    }

    public function test_anular_venta_exige_admin_motivo_y_confirmacion_y_restituye_una_vez(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        [$venta, $producto] = $this->venta($secretaria);
        $this->delete(route('gestion.historial.ventas.anular', $venta), ['motivo' => 'Duplicada', 'confirmacion' => 1])->assertForbidden();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->delete(route('gestion.historial.ventas.anular', $venta), [])->assertSessionHasErrors(['motivo', 'confirmacion']);
        $this->assertSame(7, $producto->fresh()->stock);
        $this->assertNull($venta->fresh()->anulada_en);
        $this->delete(route('gestion.historial.ventas.anular', $venta), ['motivo' => 'Venta duplicada por error', 'confirmacion' => 1])
            ->assertRedirect(route('gestion.historial.ventas.index'))->assertSessionHasNoErrors();
        $this->assertSame(10, $producto->fresh()->stock);
        $anulada = Venta::withoutGlobalScope('vigentes')->findOrFail($venta->id);
        $this->assertNotNull($anulada->anulada_en);
        $this->assertSame($admin->id, $anulada->anulada_por);
        $this->assertSame('15.00', $anulada->total);
        $this->assertSame(3, $anulada->detalles()->firstOrFail()->cantidad);
        $this->assertSame(0, Venta::count());
        $this->assertSame(1, Venta::withoutGlobalScope('vigentes')->count());
        $this->delete(route('gestion.historial.ventas.anular', $venta->id), ['motivo' => 'Reintento', 'confirmacion' => 1])->assertSessionHasNoErrors();
        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertDatabaseCount('historial_correcciones', 1);
        $this->get(route('ventas.index'))->assertOk()->assertViewHas('hoyConteo', 0)->assertViewHas('hoyTotal', 0);
        $this->get(route('ventas.comprobante', $venta->id))->assertNotFound();
        $this->get(route('gestion.historial.ventas.edit', $venta->id))->assertOk()->assertSee('Venta duplicada por error');
        $this->get(route('gestion.historial.ventas.index', ['estado' => 'anulada']))->assertOk()
            ->assertViewHas('registros', fn ($registros) => $registros->total() === 1);
    }

    public function test_anulacion_suma_detalles_legacy_repetidos_y_bloquea_correccion_de_anulada(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $producto = Producto::create(['nombre' => 'Producto legacy', 'precio' => 4, 'stock' => 5, 'activo' => false]);
        $venta = Venta::create(['user_id' => $admin->id, 'total' => 20, 'metodo_pago' => 'Tarjeta']);
        foreach ([2, 3] as $cantidad) {
            $venta->detalles()->create(['producto_id' => $producto->id, 'cantidad' => $cantidad, 'precio_unitario' => 4, 'subtotal' => $cantidad * 4]);
        }
        $this->actingAs($admin)->delete(route('gestion.historial.ventas.anular', $venta), ['motivo' => 'Carga errónea', 'confirmacion' => 1])->assertSessionHasNoErrors();
        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(2, DB::table('detalle_ventas')->where('venta_id', $venta->id)->count());
        $this->put(route('gestion.historial.ventas.update', $venta->id), ['notas' => 'No permitido', 'motivo' => 'Intento'])->assertSessionHasErrors('venta');
        $this->assertDatabaseCount('historial_correcciones', 1);
    }

    public function test_venta_sin_detalles_no_se_anula_ni_fabrica_restitucion(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $venta = Venta::create(['user_id' => $admin->id, 'total' => 20, 'metodo_pago' => 'Efectivo']);
        $this->actingAs($admin)->delete(route('gestion.historial.ventas.anular', $venta), ['motivo' => 'Datos incompletos', 'confirmacion' => 1])->assertSessionHasErrors('venta');
        $this->assertNull($venta->fresh()->anulada_en);
        $this->assertDatabaseCount('historial_correcciones', 0);
        $this->assertSame(1, Venta::count());
    }

    public function test_secretaria_corrige_horario_sin_cambiar_cliente_y_operadores(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $otra = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $asistencia = $this->asistencia($cliente, $secretaria);
        Cache::put('aforo_en_vivo', 99);
        $this->actingAs($otra)->get(route('gestion.historial.asistencias.edit', $asistencia))->assertOk();
        $this->put(route('gestion.historial.asistencias.update', $asistencia), [
            'fecha_hora' => today()->setTime(7, 0)->format('Y-m-d\TH:i'),
            'fecha_salida' => today()->setTime(8, 0)->format('Y-m-d\TH:i'),
            'motivo' => 'Corregir captura de hora', 'cliente_id' => 999,
            'registrado_por' => $otra->id, 'salida_registrada_por' => $otra->id, 'tipo_acceso' => 'otro',
        ])->assertSessionHasNoErrors();
        $asistencia->refresh();
        $this->assertSame('07:00', $asistencia->fecha_hora->format('H:i'));
        $this->assertSame('08:00', $asistencia->fecha_salida->format('H:i'));
        $this->assertSame($cliente->id, $asistencia->cliente_id);
        $this->assertSame($secretaria->id, $asistencia->registrado_por);
        $this->assertSame($secretaria->id, $asistencia->salida_registrada_por);
        $this->assertSame('entrada', $asistencia->tipo_acceso);
        $this->assertNull(Cache::get('aforo_en_vivo'));
        $registro = DB::table('historial_correcciones')->first();
        $this->assertSame($otra->id, $registro->actor_id);
        $this->assertSame('horario_corregido', $registro->accion);
        $this->assertSame(['fecha_hora' => today()->setTime(8, 0)->toDateTimeString(), 'fecha_salida' => today()->setTime(9, 0)->toDateTimeString()], json_decode($registro->antes, true));
        $this->assertStringNotContainsString($cliente->correo, $registro->antes.$registro->despues);
    }

    public function test_correccion_rechaza_solapamientos_dos_abiertas_y_salida_anterior(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $cerrada = $this->asistencia($cliente, $secretaria);
        $abierta = $this->asistencia($cliente, $secretaria, ['fecha_hora' => today()->setTime(10, 0), 'fecha_salida' => null]);
        $url = route('gestion.historial.asistencias.update', $cerrada);
        $this->actingAs($secretaria)->put($url, [
            'fecha_hora' => today()->setTime(8, 0)->format('Y-m-d\TH:i'),
            'fecha_salida' => today()->setTime(11, 0)->format('Y-m-d\TH:i'), 'motivo' => 'Intento superpuesto',
        ])->assertSessionHasErrors('fecha_hora');
        $this->put($url, ['fecha_hora' => today()->setTime(8, 0)->format('Y-m-d\TH:i'), 'fecha_salida' => null, 'motivo' => 'Segunda abierta'])
            ->assertSessionHasErrors('fecha_hora');
        $this->put($url, ['fecha_hora' => today()->setTime(8, 0)->format('Y-m-d\TH:i'), 'fecha_salida' => today()->setTime(7, 0)->format('Y-m-d\TH:i'), 'motivo' => 'Salida anterior'])
            ->assertSessionHasErrors('fecha_salida');
        $this->assertSame('09:00', $cerrada->fresh()->fecha_salida->format('H:i'));
        $this->assertNull($abierta->fresh()->fecha_salida);
        $this->assertDatabaseCount('historial_correcciones', 0);
        $this->put($url, ['fecha_hora' => today()->setTime(8, 0)->format('Y-m-d\TH:i'), 'fecha_salida' => today()->setTime(10, 0)->format('Y-m-d\TH:i'), 'motivo' => 'Hora contigua válida'])
            ->assertSessionHasNoErrors();
        $this->assertSame('10:00', $cerrada->fresh()->fecha_salida->format('H:i'));
    }

    public function test_anular_asistencia_conserva_horas_excluye_aforo_y_permite_nueva_entrada(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $asistencia = $this->asistencia($cliente, $secretaria, ['fecha_hora' => now()->subHour(), 'fecha_salida' => null]);
        $horaOriginal = $asistencia->fecha_hora->toDateTimeString();
        Cache::put('aforo_en_vivo', 99);
        $this->actingAs($secretaria)->delete(route('gestion.historial.asistencias.anular', $asistencia), ['motivo' => 'Entrada registrada por error', 'confirmacion' => 1])
            ->assertRedirect(route('gestion.historial.asistencias.index'))->assertSessionHasNoErrors();
        $anulada = Asistencia::withoutGlobalScope('vigentes')->findOrFail($asistencia->id);
        $this->assertSame($horaOriginal, $anulada->fecha_hora->toDateTimeString());
        $this->assertNull($anulada->fecha_salida);
        $this->assertNotNull($anulada->anulada_en);
        $this->assertSame(0, Asistencia::whereNull('fecha_salida')->count());
        $this->assertNull(Cache::get('aforo_en_vivo'));
        $this->get(route('asistencia.index'))->assertOk()->assertViewHas('dentro', 0);
        $this->delete(route('gestion.historial.asistencias.anular', $asistencia->id), ['motivo' => 'Reintento', 'confirmacion' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('historial_correcciones', 1);
        $this->post(route('asistencia.store'), ['cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->assertSame(1, Asistencia::count());
        $this->assertSame(2, Asistencia::withoutGlobalScope('vigentes')->count());
        $this->get(route('gestion.historial.asistencias.index', ['q' => 'Cliente del historial', 'estado' => 'anulada']))->assertOk()
            ->assertViewHas('registros', fn ($registros) => $registros->total() === 1);
    }

    public function test_cliente_o_entrenador_no_consulta_ni_muta_historial_y_no_hereda_admin(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->cliente();
        [$venta] = $this->venta($admin, $cliente);
        $asistencia = $this->asistencia($cliente, $admin);
        foreach ([[$entrenador, 'web'], [$cliente, 'cliente']] as [$actor, $guard]) {
            Auth::guard('web')->logout();
            Auth::guard('cliente')->logout();
            $this->actingAs($actor, $guard);
            foreach ([['ventas', $venta->id], ['asistencias', $asistencia->id]] as [$tipo, $id]) {
                $this->get(route("gestion.historial.{$tipo}.index"))->assertForbidden();
                $this->get(route("gestion.historial.{$tipo}.edit", $id))->assertForbidden();
                $this->put(route("gestion.historial.{$tipo}.update", $id), [])->assertForbidden();
                $this->delete(route("gestion.historial.{$tipo}.anular", $id), [])->assertForbidden();
            }
        }
        $this->actingAs($admin, 'web')->actingAs($cliente, 'cliente');
        $this->get(route('gestion.historial.ventas.index'))->assertForbidden();
        $this->delete(route('gestion.historial.ventas.anular', $venta), ['motivo' => 'No permitido', 'confirmacion' => 1])->assertForbidden();
        $this->assertNull($venta->fresh()->anulada_en);
        $this->assertDatabaseCount('historial_correcciones', 0);
    }

    public function test_fallo_de_bitacora_revierte_anulacion_y_stock_atomicamente(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        [$venta, $producto] = $this->venta($admin);
        // Falla real de BD aislada: no se simula la venta, el stock ni el servicio.
        DB::statement("CREATE TRIGGER rechazar_bitacora BEFORE INSERT ON historial_correcciones BEGIN SELECT RAISE(ABORT, 'fallo controlado'); END");
        try {
            app(CorreccionHistorial::class)->anularVenta($venta->id, 'Error de carga', $admin);
            $this->fail('La bitácora debía rechazar la operación.');
        } catch (QueryException) {
            $this->assertSame(7, $producto->fresh()->stock);
            $this->assertNull($venta->fresh()->anulada_en);
            $this->assertDatabaseCount('historial_correcciones', 0);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Correcciones explícitas con bitácora atómica y sin borrados físicos. */
class CorreccionHistorial
{
    private function autorizar(User $actor, bool $soloAdmin = false): void
    {
        abort_unless(! auth('cliente')->check() && $actor->activo && in_array($actor->rol, $soloAdmin ? ['Administrador'] : ['Administrador', 'Secretaria'], true), 403);
    }

    public function editarNotaVenta(int $id, ?string $nota, string $motivo, User $actor): void
    {
        $this->autorizar($actor);
        DB::transaction(function () use ($id, $nota, $motivo, $actor) {
            $venta = Venta::withoutGlobalScope('vigentes')->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($venta->anulada_en !== null) {
                throw ValidationException::withMessages(['venta' => 'Una venta anulada no puede editarse.']);
            }
            $antes = ['notas' => $venta->notas];
            $venta->update(['notas' => $nota]);
            $this->registrar('ventas', $id, 'nota_corregida', $motivo, $actor, $antes, ['notas' => $venta->notas]);
        }, 3);
    }

    public function anularVenta(int $id, string $motivo, User $actor): bool
    {
        $this->autorizar($actor, true);

        return DB::transaction(function () use ($id, $motivo, $actor) {
            // El TPV también bloquea productos por ID antes de crear la venta.
            $productoIds = DB::table('detalle_ventas')->where('venta_id', $id)->distinct()->orderBy('producto_id')->pluck('producto_id')->all();
            $productos = Producto::whereIn('id', $productoIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $venta = Venta::withoutGlobalScope('vigentes')->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($venta->anulada_en !== null) {
                return false;
            }
            $detalles = $venta->detalles()->orderBy('producto_id')->lockForUpdate()->get();
            if ($detalles->isEmpty() || array_diff($detalles->pluck('producto_id')->unique()->all(), $productoIds)) {
                throw ValidationException::withMessages(['venta' => 'El detalle de la venta no permite restituir inventario con certeza. Recarga y revisa el historial.']);
            }
            $cantidades = [];
            foreach ($detalles as $detalle) {
                if (! $productos->has($detalle->producto_id) || $detalle->cantidad < 1) {
                    throw ValidationException::withMessages(['venta' => 'Falta un producto o una cantidad válida en el detalle. No se anuló ni modificó inventario.']);
                }
                $cantidades[$detalle->producto_id] = ($cantidades[$detalle->producto_id] ?? 0) + $detalle->cantidad;
            }
            foreach ($cantidades as $productoId => $cantidad) {
                if ($productos[$productoId]->stock + $cantidad > 4294967295) {
                    throw ValidationException::withMessages(['venta' => 'La restitución supera el máximo de inventario. Revisa el stock antes de anular.']);
                }
            }
            $antes = ['anulada_en' => null];
            foreach ($cantidades as $productoId => $cantidad) {
                $productos[$productoId]->increment('stock', $cantidad);
            }
            $venta->forceFill(['anulada_en' => now(), 'anulada_por' => $actor->id, 'motivo_anulacion' => $motivo])->save();
            $this->registrar('ventas', $id, 'anulada', $motivo, $actor, $antes, ['anulada_en' => $venta->anulada_en->toDateTimeString(), 'inventario_restituido' => $cantidades]);

            return true;
        }, 3);
    }

    public function editarAsistencia(int $id, array $horario, string $motivo, User $actor): void
    {
        $this->autorizar($actor);
        DB::transaction(function () use ($id, $horario, $motivo, $actor) {
            $referencia = Asistencia::withoutGlobalScope('vigentes')->findOrFail($id);
            Cliente::whereKey($referencia->cliente_id)->lockForUpdate()->firstOrFail();
            $asistencia = Asistencia::withoutGlobalScope('vigentes')->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($asistencia->anulada_en !== null || $asistencia->cliente_id !== $referencia->cliente_id) {
                throw ValidationException::withMessages(['asistencia' => 'La asistencia cambió o está anulada. Recarga el historial.']);
            }
            $entrada = Carbon::parse($horario['fecha_hora'])->setTimezone(config('app.timezone'));
            $salida = empty($horario['fecha_salida']) ? null : Carbon::parse($horario['fecha_salida'])->setTimezone(config('app.timezone'));
            if ($salida && $salida->lt($entrada)) {
                throw ValidationException::withMessages(['fecha_salida' => 'La salida debe ser igual o posterior a la entrada.']);
            }
            $solapada = Asistencia::where('cliente_id', $asistencia->cliente_id)->where('id', '!=', $id)
                ->where(fn ($query) => $query->whereNull('fecha_salida')->orWhere('fecha_salida', '>', $entrada->toDateTimeString()))
                ->when($salida, fn ($query) => $query->where('fecha_hora', '<', $salida->toDateTimeString()))
                ->exists();
            if ($solapada) {
                throw ValidationException::withMessages(['fecha_hora' => 'El horario se solapa con otra visita o dejaría dos entradas abiertas del cliente.']);
            }
            $antes = ['fecha_hora' => $asistencia->fecha_hora->toDateTimeString(), 'fecha_salida' => $asistencia->fecha_salida?->toDateTimeString()];
            $asistencia->update(['fecha_hora' => $entrada, 'fecha_salida' => $salida]);
            $this->registrar('asistencias', $id, 'horario_corregido', $motivo, $actor, $antes,
                ['fecha_hora' => $entrada->toDateTimeString(), 'fecha_salida' => $salida?->toDateTimeString()]);
        }, 3);
        Cache::forget('aforo_en_vivo');
    }

    public function anularAsistencia(int $id, string $motivo, User $actor): bool
    {
        $this->autorizar($actor);
        $cambio = DB::transaction(function () use ($id, $motivo, $actor) {
            $referencia = Asistencia::withoutGlobalScope('vigentes')->findOrFail($id);
            Cliente::whereKey($referencia->cliente_id)->lockForUpdate()->firstOrFail();
            $asistencia = Asistencia::withoutGlobalScope('vigentes')->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($asistencia->anulada_en !== null) {
                return false;
            }
            $asistencia->forceFill(['anulada_en' => now(), 'anulada_por' => $actor->id, 'motivo_anulacion' => $motivo])->save();
            $this->registrar('asistencias', $id, 'anulada', $motivo, $actor, ['anulada_en' => null], ['anulada_en' => $asistencia->anulada_en->toDateTimeString()]);

            return true;
        }, 3);
        Cache::forget('aforo_en_vivo');

        return $cambio;
    }

    private function registrar(string $tabla, int $id, string $accion, string $motivo, User $actor, array $antes, array $despues): void
    {
        DB::table('historial_correcciones')->insert([
            'tabla' => $tabla, 'registro_id' => $id, 'accion' => $accion,
            'actor_id' => $actor->id, 'guard' => 'web', 'motivo' => $motivo,
            'antes' => json_encode($antes, JSON_THROW_ON_ERROR),
            'despues' => json_encode($despues, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}

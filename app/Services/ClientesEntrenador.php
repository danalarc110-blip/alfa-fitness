<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Rutina;
use Illuminate\Database\Eloquent\Builder;

/** La asignación de una rutina es la única fuente de vinculación cliente-entrenador. */
class ClientesEntrenador
{
    private function asignaciones(): Builder
    {
        return Rutina::query()->where('user_type', 'cliente');
    }

    public function rutinas(int $entrenadorId): Builder
    {
        return $this->asignaciones()->where('asignado_por_id', $entrenadorId);
    }

    public function clientes(int $entrenadorId): Builder
    {
        return Cliente::query()->whereIn('id', $this->rutinas($entrenadorId)->select('user_id'));
    }

    public function vinculado(int $entrenadorId, int $clienteId): bool
    {
        return $this->rutinas($entrenadorId)->where('user_id', $clienteId)->exists();
    }

    public function entrenadoresDelCliente(int $clienteId): array
    {
        return $this->asignaciones()->where('user_id', $clienteId)
            ->whereNotNull('asignado_por_id')->distinct()->pluck('asignado_por_id')
            ->map(fn ($id) => (int) $id)->all();
    }
}

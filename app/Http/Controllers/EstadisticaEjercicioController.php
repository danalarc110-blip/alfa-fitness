<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EstadisticaEjercicioController extends Controller
{
    public function index(Request $request): View
    {
        $cliente = auth('cliente')->user();
        abort_unless($cliente && $cliente->activo, 403);
        Gate::forUser($cliente)->authorize('progreso');

        $reglasHasta = ['nullable', 'date_format:Y-m-d'];
        if ($request->filled('desde')) {
            $reglasHasta[] = 'after_or_equal:desde';
        }

        $filtros = $request->validate([
            'ejercicio_id' => ['nullable', 'integer', Rule::exists('ejercicios', 'id')],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => $reglasHasta,
        ], [
            'ejercicio_id.integer' => 'Selecciona un ejercicio válido.',
            'ejercicio_id.exists' => 'El ejercicio seleccionado no existe.',
            'desde.date_format' => 'La fecha inicial debe tener el formato año-mes-día.',
            'hasta.date_format' => 'La fecha final debe tener el formato año-mes-día.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        // All aggregates start with the authenticated client; URL IDs cannot change ownership.
        $registros = DB::table('personal_records as pr')
            ->join('ejercicios as e', 'e.id', '=', 'pr.ejercicio_id')
            ->where('pr.cliente_id', $cliente->id)
            ->when($filtros['ejercicio_id'] ?? null, fn ($q, $id) => $q->where('e.id', $id))
            ->when($filtros['desde'] ?? null, fn ($q, $fecha) => $q->whereDate('pr.created_at', '>=', $fecha))
            ->when($filtros['hasta'] ?? null, fn ($q, $fecha) => $q->whereDate('pr.created_at', '<=', $fecha));

        $resumen = (clone $registros)->selectRaw(
            'COUNT(pr.id) as registros, COUNT(DISTINCT e.id) as ejercicios, COALESCE(SUM(pr.peso_kg * pr.repeticiones), 0) as volumen'
        )->first();

        // Fixed expressions only: both MySQL and SQLite support CASE and the decimal division.
        $estadisticas = (clone $registros)
            ->select('e.id', 'e.nombre', 'e.grupo_muscular', 'e.activo')
            ->selectRaw('COUNT(pr.id) as registros, MAX(pr.peso_kg) as peso_maximo, SUM(pr.peso_kg * pr.repeticiones) as volumen')
            ->selectRaw('MAX(pr.peso_kg * pr.repeticiones) as mejor_volumen')
            ->selectRaw('MAX(pr.peso_kg * (1 + (CASE WHEN pr.repeticiones > 30 THEN 30 ELSE pr.repeticiones END) / 30.0)) as estimacion_1rm')
            ->selectRaw('MAX(pr.created_at) as ultimo_registro')
            ->groupBy('e.id', 'e.nombre', 'e.grupo_muscular', 'e.activo')
            ->orderBy('e.nombre')->orderBy('e.id')
            ->paginate(15)->withQueryString();

        $ejercicios = Ejercicio::query()
            ->where(function ($query) use ($cliente) {
                $query->where('activo', true)
                    ->orWhereHas('personalRecords', fn ($pr) => $pr->where('cliente_id', $cliente->id));
            })->orderBy('nombre')->get(['id', 'nombre', 'activo']);

        return view('progreso.estadisticas', [
            'guard' => 'cliente',
            'nombre' => $cliente->nombre,
            'rolEtiqueta' => 'Miembro',
            'avatarUrl' => $cliente->avatar_url,
            'filtros' => $filtros,
            'resumen' => $resumen,
            'estadisticas' => $estadisticas,
            'ejercicios' => $ejercicios,
        ]);
    }
}

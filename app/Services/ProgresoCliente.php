<?php

namespace App\Services;

use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use App\Support\RangoAtleta;

final class ProgresoCliente
{
    public function resumen(int $clienteId, ?int $ejercicioId, int $porPagina = 20): array
    {
        $propios = PersonalRecord::where('cliente_id', $clienteId);
        $ejercicios = Ejercicio::where(fn ($q) => $q->where('activo', true)
            ->orWhereIn('id', (clone $propios)->select('ejercicio_id')))->orderBy('nombre')->get();
        $query = (clone $propios)->with('ejercicio')->when($ejercicioId, fn ($q) => $q->where('ejercicio_id', $ejercicioId));
        $cronologico = (clone $query)->latest()->orderByDesc('id');
        $ejercicioGraficoId = $ejercicioId ?? (clone $cronologico)->value('ejercicio_id');
        $ultimos = $ejercicioGraficoId
            ? (clone $cronologico)->where('ejercicio_id', $ejercicioGraficoId)->limit(8)->get()->reverse()->values()
            : collect();
        $mejores = (clone $query)->whereNotExists(function ($sub) {
            $sub->selectRaw('1')->from('personal_records as mejor')
                ->whereColumn('mejor.cliente_id', 'personal_records.cliente_id')
                ->whereColumn('mejor.ejercicio_id', 'personal_records.ejercicio_id')
                ->whereRaw('(mejor.peso_kg * mejor.repeticiones > personal_records.peso_kg * personal_records.repeticiones OR (mejor.peso_kg * mejor.repeticiones = personal_records.peso_kg * personal_records.repeticiones AND mejor.id > personal_records.id))');
        })->orderByRaw('peso_kg * repeticiones DESC')->limit(4)->get();

        return [
            'ejercicios' => $ejercicios,
            'ejerciciosRegistro' => $ejercicios->where('activo', true),
            'ejercicioFiltro' => $ejercicioId,
            'ejercicioGrafico' => $ejercicios->firstWhere('id', $ejercicioGraficoId),
            'records' => $cronologico->paginate($porPagina)->withQueryString(),
            'mejoresMarcas' => $mejores,
            'totalEjercicios' => (clone $query)->distinct()->count('ejercicio_id'),
            'totalVolumenKg' => (float) (clone $query)->selectRaw('COALESCE(SUM(peso_kg * repeticiones), 0) as total')->value('total'),
            'maximoRecord' => (clone $query)->orderByDesc('peso_kg')->orderByDesc('id')->first(),
            'ultimosLevantamientos' => $ultimos,
            'rango' => RangoAtleta::paraRegistros((clone $propios)->count()),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RankingController extends Controller
{


    public function index(Request $request): View
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $ejercicioId = $request->integer('ejercicio_id') ?: null;
        $ejercicios = Ejercicio::where('activo', true)->orderBy('nombre')->get();

        $ranking = PersonalRecord::with(['cliente', 'ejercicio'])
            ->whereHas('cliente', fn ($q) => $q->where('activo', true))
            ->when($ejercicioId, fn ($q) => $q->where('ejercicio_id', $ejercicioId))
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')->from('personal_records as mejor')
                    ->whereColumn('mejor.cliente_id', 'personal_records.cliente_id')
                    ->whereColumn('mejor.ejercicio_id', 'personal_records.ejercicio_id')
                    ->whereRaw('(mejor.peso_kg * mejor.repeticiones > personal_records.peso_kg * personal_records.repeticiones OR (mejor.peso_kg * mejor.repeticiones = personal_records.peso_kg * personal_records.repeticiones AND mejor.id > personal_records.id))');
            })->orderByRaw('peso_kg * repeticiones DESC')->orderByDesc('id')->limit(25)->get();

        $misPosiciones = $guard === 'cliente'
            ? $ranking->filter(fn (PersonalRecord $record) => $record->cliente_id === $user->id)->values()
            : collect();

        return view('rankings.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'ejercicios' => $ejercicios,
            'ejercicioSeleccionado' => $ejercicioId,
            'ranking' => $ranking,
            'misPosiciones' => $misPosiciones,
        ]);
    }
}

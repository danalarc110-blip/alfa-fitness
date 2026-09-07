<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RankingController extends Controller
{
    private function actual(): array
    {
        if (Auth::guard('web')->check()) {
            return ['guard' => 'web', 'user' => Auth::guard('web')->user()];
        }

        return ['guard' => 'cliente', 'user' => Auth::guard('cliente')->user()];
    }

    private function nombreActual(string $guard, $user): string
    {
        return $guard === 'web' ? $user->name : $user->nombre;
    }

    public function index(Request $request): View
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $ejercicioId = $request->integer('ejercicio_id') ?: null;
        $ejercicios = Ejercicio::where('activo', true)->orderBy('nombre')->get();

        $records = PersonalRecord::with(['cliente', 'ejercicio'])
            ->when($ejercicioId, fn ($query) => $query->where('ejercicio_id', $ejercicioId))
            ->get();

        $ranking = $records
            ->groupBy(fn (PersonalRecord $record) => $record->cliente_id.'-'.$record->ejercicio_id)
            ->map(fn ($grupo) => $grupo->sortByDesc(fn (PersonalRecord $record) => $record->volumen)->first())
            ->sortByDesc(fn (PersonalRecord $record) => $record->volumen)
            ->values()
            ->take(25);

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

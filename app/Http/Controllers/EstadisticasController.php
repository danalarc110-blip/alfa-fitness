<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PersonalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EstadisticasController extends Controller
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

        $clienteId = $guard === 'cliente'
            ? $user->id
            : (int) ($request->integer('cliente_id') ?: Cliente::where('activo', true)->orderBy('nombre')->value('id'));

        $clientes = $guard === 'web'
            ? Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        $records = PersonalRecord::with(['cliente', 'ejercicio'])
            ->when($clienteId, fn ($query) => $query->where('cliente_id', $clienteId))
            ->oldest()
            ->get();

        $mejorPeso = $records->sortByDesc(fn (PersonalRecord $record) => (float) $record->peso_kg)->first();
        $mejorVolumen = $records->sortByDesc(fn (PersonalRecord $record) => $record->volumen)->first();
        $ultimos = $records->sortByDesc('created_at')->take(8)->values();

        $porEjercicio = $records
            ->groupBy('ejercicio_id')
            ->map(function ($grupo) {
                $mejor = $grupo->sortByDesc(fn (PersonalRecord $record) => $record->volumen)->first();
                $primero = $grupo->sortBy('created_at')->first();

                return [
                    'ejercicio' => $mejor->ejercicio,
                    'registros' => $grupo->count(),
                    'mejor' => $mejor,
                    'primero' => $primero,
                    'diferencia_kg' => (float) $mejor->peso_kg - (float) $primero->peso_kg,
                ];
            })
            ->sortByDesc(fn ($item) => $item['mejor']->volumen)
            ->values();

        return view('estadisticas.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'clientes' => $clientes,
            'clienteSeleccionado' => $clienteId,
            'records' => $records,
            'mejorPeso' => $mejorPeso,
            'mejorVolumen' => $mejorVolumen,
            'ultimos' => $ultimos,
            'porEjercicio' => $porEjercicio,
        ]);
    }
}

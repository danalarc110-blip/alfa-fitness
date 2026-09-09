<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PersonalRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProgresoController extends Controller
{


    public function index(Request $request): View
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        abort_unless($guard === 'cliente', 403);
        $clienteId = $user->id;
        $clientes = collect();

        $ejercicios = Ejercicio::where('activo', true)->orderBy('nombre')->get();

        $query = PersonalRecord::with(['cliente', 'ejercicio'])
            ->where('cliente_id', $clienteId)
            ->latest();
        $records = (clone $query)->paginate(20)->withQueryString();

        $mejoresMarcas = (clone $query)->whereNotExists(function ($sub) {
            $sub->selectRaw('1')->from('personal_records as mejor')
                ->whereColumn('mejor.cliente_id', 'personal_records.cliente_id')
                ->whereColumn('mejor.ejercicio_id', 'personal_records.ejercicio_id')
                ->whereRaw('(mejor.peso_kg * mejor.repeticiones > personal_records.peso_kg * personal_records.repeticiones OR (mejor.peso_kg * mejor.repeticiones = personal_records.peso_kg * personal_records.repeticiones AND mejor.id > personal_records.id))');
        })->reorder()->orderByRaw('peso_kg * repeticiones DESC')->limit(4)->get();

        return view('progreso.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'clientes' => $clientes,
            'clienteSeleccionado' => $clienteId,
            'ejercicios' => $ejercicios,
            'records' => $records,
            'mejoresMarcas' => $mejoresMarcas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'cliente', 403);

        $rules = [
            'ejercicio_id' => ['required', 'exists:ejercicios,id'],
            'peso_kg' => ['required', 'numeric', 'min:0.5', 'max:999'],
            'repeticiones' => ['required', 'integer', 'min:1', 'max:100'],
            'notas' => ['nullable', 'string', 'max:255'],
        ];

        $data = $request->validate($rules);
        $data['cliente_id'] = $user->id;

        PersonalRecord::create($data);

        return redirect()
            ->route('progreso.index')
            ->with('status', 'Registro guardado.');
    }

    public function destroy(PersonalRecord $personalRecord): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        abort_unless($guard === 'cliente' && $personalRecord->cliente_id === $user->id, 403);

        $clienteId = $personalRecord->cliente_id;
        $personalRecord->delete();

        return redirect()
            ->route('progreso.index')
            ->with('status', 'Registro eliminado.');
    }
}

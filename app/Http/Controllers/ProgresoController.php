<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PersonalRecord;
use App\Services\ClientesEntrenador;
use App\Services\ProgresoCliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgresoController extends Controller
{
    public function index(Request $request): View
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        abort_unless($guard === 'cliente', 403);
        $filtros = $request->validate(['ejercicio_id' => ['nullable', 'integer', 'exists:ejercicios,id']]);
        $ejercicioId = isset($filtros['ejercicio_id']) ? (int) $filtros['ejercicio_id'] : null;

        return view('progreso.index', app(ProgresoCliente::class)->resumen($user->id, $ejercicioId) + [
            'guard' => $guard,
            'nombre' => $user->nombre,
            'esPropietario' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'cliente', 403);

        $rules = [
            'ejercicio_id' => ['required', 'integer', Rule::exists('ejercicios', 'id')->where('activo', true)],
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

        $personalRecord->delete();

        return redirect()
            ->route('progreso.index')
            ->with('status', 'Registro eliminado.');
    }

    /**
     * Permite a un entrenador o administrador consultar el progreso de un cliente asignado.
     */
    public function clienteProgreso(Request $request, Cliente $cliente): View
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'web', 403);
        abort_unless(in_array($user->rol, ['Administrador', 'Entrenador'], true), 403);

        if ($user->rol === 'Entrenador') {
            $asignado = app(ClientesEntrenador::class)->vinculado($user->id, $cliente->id);
            abort_unless($asignado, 403, 'No tienes asignado a este cliente.');
        }

        $filtros = $request->validate(['ejercicio_id' => ['nullable', 'integer', 'exists:ejercicios,id']]);
        $ejercicioId = isset($filtros['ejercicio_id']) ? (int) $filtros['ejercicio_id'] : null;

        return view('progreso.entrenador', app(ProgresoCliente::class)->resumen($cliente->id, $ejercicioId, 15) + [
            'guard' => $guard,
            'nombre' => $user->name,
            'esPropietario' => false,
            'cliente' => $cliente,
        ]);
    }
}

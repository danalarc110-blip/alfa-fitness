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

        $ejercicios = Ejercicio::where('activo', true)->orderBy('nombre')->get();

        $records = PersonalRecord::with(['cliente', 'ejercicio'])
            ->when($clienteId, fn ($query) => $query->where('cliente_id', $clienteId))
            ->latest()
            ->get();

        $mejoresMarcas = $records
            ->sortByDesc(fn (PersonalRecord $record) => $record->volumen)
            ->unique('ejercicio_id')
            ->take(4)
            ->values();

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

        $rules = [
            'ejercicio_id' => ['required', 'exists:ejercicios,id'],
            'peso_kg' => ['required', 'numeric', 'min:0.5', 'max:999'],
            'repeticiones' => ['required', 'integer', 'min:1', 'max:100'],
            'notas' => ['nullable', 'string', 'max:255'],
        ];

        if ($guard === 'web') {
            $rules['cliente_id'] = ['required', 'exists:clientes,id'];
        }

        $data = $request->validate($rules);
        $data['cliente_id'] = $guard === 'cliente' ? $user->id : $data['cliente_id'];

        PersonalRecord::create($data);

        return redirect()
            ->route('progreso.index', $guard === 'web' ? ['cliente_id' => $data['cliente_id']] : [])
            ->with('status', 'Registro guardado.');
    }

    public function destroy(PersonalRecord $personalRecord): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        abort_unless($guard === 'web' || $personalRecord->cliente_id === $user->id, 403);

        $clienteId = $personalRecord->cliente_id;
        $personalRecord->delete();

        return redirect()
            ->route('progreso.index', $guard === 'web' ? ['cliente_id' => $clienteId] : [])
            ->with('status', 'Registro eliminado.');
    }
}

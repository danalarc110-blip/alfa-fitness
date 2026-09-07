<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AsistenciaController extends Controller
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

        $busqueda = $request->string('q')->toString();
        $clientes = Cliente::where('activo', true)
            ->when($busqueda, fn ($query) => $query->where(function ($subquery) use ($busqueda) {
                $subquery
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%");
            }))
            ->with(['asistencias' => fn ($query) => $query->whereNull('fecha_salida')->latest('fecha_hora')])
            ->orderBy('nombre')
            ->get();

        $asistencias = Asistencia::with(['cliente', 'registrador'])
            ->whereBetween('fecha_hora', [now()->startOfMonth(), now()->endOfMonth()])
            ->latest('fecha_hora')
            ->get();

        $hoy = Asistencia::whereDate('fecha_hora', now()->toDateString())->count();
        $dentro = Asistencia::whereNull('fecha_salida')->count();

        return view('asistencia.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'clientes' => $clientes,
            'busqueda' => $busqueda,
            'asistencias' => $asistencias,
            'hoy' => $hoy,
            'dentro' => $dentro,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
        ]);

        $abierta = Asistencia::where('cliente_id', $data['cliente_id'])
            ->whereNull('fecha_salida')
            ->latest('fecha_hora')
            ->first();

        if ($abierta) {
            return back()->withErrors(['cliente_id' => 'Este cliente ya tiene una entrada abierta. Registra la salida antes de abrir otra.']);
        }

        Asistencia::create([
            'cliente_id' => $data['cliente_id'],
            'registrado_por' => Auth::guard('web')->id(),
            'fecha_hora' => now(),
            'tipo_acceso' => 'entrada',
        ]);

        return back()->with('status', 'Entrada registrada.');
    }

    public function salida(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
        ]);

        $asistencia = Asistencia::where('cliente_id', $data['cliente_id'])
            ->whereNull('fecha_salida')
            ->latest('fecha_hora')
            ->first();

        if (! $asistencia) {
            return back()->withErrors(['cliente_id' => 'Este cliente no tiene una entrada abierta.']);
        }

        $asistencia->update(['fecha_salida' => now()]);

        return back()->with('status', 'Salida registrada.');
    }
}

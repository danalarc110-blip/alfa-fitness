<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CuentasController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('administrar');
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $q = $filtros['q'] ?? '';
        $cuentas = Cliente::query()
            ->select(['id', 'nombre', 'correo', 'activo', 'created_at'])
            ->when($q, fn ($query) => $query->where(fn ($search) => $search
                ->where('nombre', 'like', "%{$q}%")
                ->orWhere('correo', 'like', "%{$q}%")))
            ->latest('created_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('cuentas.index', compact('cuentas', 'q'));
    }

    public function banear(Cliente $cliente)
    {
        Gate::authorize('administrar');
        $this->cambiarAcceso($cliente, false);

        return back()->with('status', 'Usuario baneado. Su historial se conserva y su sesión quedará cerrada.');
    }

    public function restaurar(Cliente $cliente)
    {
        Gate::authorize('administrar');
        $this->cambiarAcceso($cliente, true);

        return back()->with('status', 'Acceso restaurado. El usuario ya puede iniciar sesión.');
    }

    private function cambiarAcceso(Cliente $cliente, bool $activo): void
    {
        DB::transaction(function () use ($cliente, $activo) {
            $cuenta = Cliente::query()
                ->whereKey($cliente->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $cuenta->activo = $activo;
            $cuenta->baneado_en = $activo ? null : now();
            $cuenta->baneado_por = $activo ? null : auth('web')->id();
            $cuenta->save();
        });
    }
}

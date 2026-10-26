<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

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
        $ahora = now();
        $adminId = ! auth('cliente')->check() ? auth('web')->id() : null;

        DB::transaction(function () use ($cliente, $activo, $ahora, $adminId) {
            $cuenta = Cliente::query()
                ->whereKey($cliente->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $cuenta->activo = $activo;
            $cuenta->baneado_en = $activo ? null : $ahora;
            $cuenta->baneado_por = $activo ? null : $adminId;
            // Also revoke on restoration: accounts banned before access versioning may still have old sessions.
            $cuenta->version_acceso++;
            $cuenta->remember_token = Str::random(60);
            $cuenta->save();

            if (! $activo) {
                $cuenta->asistencias()
                    ->whereNull('fecha_salida')
                    ->update([
                        'fecha_salida' => $ahora,
                        'salida_registrada_por' => $adminId,
                    ]);
            }
        });

        if (! $activo) {
            Cache::forget('aforo_en_vivo');
        }
    }
}

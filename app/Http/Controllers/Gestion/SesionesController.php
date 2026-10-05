<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\SesionEntrenador;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SesionesController extends Controller
{
    private function acceso(?SesionEntrenador $sesion = null): User
    {
        $empleado = auth('web')->user();
        abort_unless(! auth('cliente')->check() && $empleado?->activo && in_array($empleado->rol, ['Administrador', 'Secretaria', 'Entrenador'], true), 403);
        if ($sesion && $empleado->rol === 'Entrenador') {
            abort_unless($sesion->entrenador_id === $empleado->id, 403);
        }

        return $empleado;
    }

    public function index(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        if ($guard !== 'cliente') {
            $this->acceso();
        }
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['programada', 'completada', 'cancelada'])],
            'desde' => ['nullable', 'date'],
        ]);
        $q = $filtros['q'] ?? '';
        $estado = $filtros['estado'] ?? '';
        $desde = $filtros['desde'] ?? '';
        $sesiones = SesionEntrenador::with(['cliente:id,nombre', 'entrenador:id,name'])
            ->when($guard === 'cliente', fn ($query) => $query->where('cliente_id', $user->id))
            ->when($guard === 'web' && $user->rol === 'Entrenador', fn ($query) => $query->where('entrenador_id', $user->id))
            ->when($q, fn ($query) => $query->where(fn ($query) => $query->whereHas('cliente', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))->orWhereHas('entrenador', fn ($query) => $query->where('name', 'like', "%{$q}%"))))
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($desde, fn ($query) => $query->whereDate('fecha_inicio', '>=', $desde))
            ->orderByDesc('fecha_inicio')->paginate(15)->withQueryString();
        $puedeEditar = $guard === 'web';

        return view('gestion.sesiones.index', compact('sesiones', 'q', 'estado', 'desde', 'puedeEditar'));
    }

    public function create()
    {
        $empleado = $this->acceso();

        return $this->formulario(new SesionEntrenador(['entrenador_id' => $empleado->rol === 'Entrenador' ? $empleado->id : null, 'estado' => 'programada']));
    }

    public function store(Request $request)
    {
        $empleado = $this->acceso();
        $data = $this->validar($request, $empleado);
        $this->guardar($data, $empleado);

        return redirect()->route('gestion.sesiones.index')->with('status', 'Sesión programada.');
    }

    public function edit(SesionEntrenador $sesion)
    {
        $this->acceso($sesion);

        return $this->formulario($sesion);
    }

    public function update(Request $request, SesionEntrenador $sesion)
    {
        $empleado = $this->acceso($sesion);
        $data = $this->validar($request, $empleado);
        $this->guardar($data, $empleado, $sesion);

        return redirect()->route('gestion.sesiones.index')->with('status', 'Sesión actualizada.');
    }

    public function destroy(SesionEntrenador $sesion)
    {
        $empleado = $this->acceso($sesion);
        DB::transaction(function () use ($sesion) {
            User::whereKey($sesion->entrenador_id)->lockForUpdate()->firstOrFail();
            Cliente::whereKey($sesion->cliente_id)->lockForUpdate()->firstOrFail();
            $actual = SesionEntrenador::whereKey($sesion->id)->lockForUpdate()->firstOrFail();
            $this->acceso($actual);
            if ($actual->entrenador_id !== $sesion->entrenador_id || $actual->cliente_id !== $sesion->cliente_id) {
                throw ValidationException::withMessages(['sesion' => 'La sesión cambió mientras la revisabas. Recarga la agenda e intenta de nuevo.']);
            }
            $actual->update(['estado' => 'cancelada']);
        });

        return back()->with('status', 'Sesión cancelada. Se conservó su historial.');
    }

    private function formulario(SesionEntrenador $sesion)
    {
        $empleado = $this->acceso($sesion->exists ? $sesion : null);
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $entrenadores = User::where('rol', 'Entrenador')->where('activo', true)
            ->when($empleado->rol === 'Entrenador', fn ($query) => $query->whereKey($empleado->id))
            ->orderBy('name')->get(['id', 'name']);

        return view('gestion.sesiones.form', compact('sesion', 'clientes', 'entrenadores'));
    }

    private function validar(Request $request, User $empleado): array
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'entrenador_id' => ['required', 'integer', Rule::exists('users', 'id')->where('rol', 'Entrenador')->where('activo', true)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'estado' => ['required', Rule::in(['programada', 'completada', 'cancelada'])],
            'notas' => ['nullable', 'string', 'max:500'],
        ], [
            'cliente_id.exists' => 'Selecciona un cliente activo.',
            'entrenador_id.exists' => 'Selecciona un entrenador activo.',
            'fecha_fin.after' => 'La hora de finalización debe ser posterior al inicio.',
        ]);
        if ($empleado->rol === 'Entrenador') {
            abort_unless((int) $data['entrenador_id'] === $empleado->id, 403);
        }
        // datetime-local usa "T"; normalizar también los parámetros de consultas
        // evita comparaciones textuales distintas entre SQLite y MySQL.
        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            $data[$campo] = Carbon::parse($data[$campo])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }

        return $data;
    }

    private function guardar(array $data, User $empleado, ?SesionEntrenador $sesion = null): void
    {
        DB::transaction(function () use ($data, $empleado, $sesion) {
            // Orden global: entrenadores por ID, clientes por ID y sesión.
            $entrenadorIds = array_values(array_unique(array_filter([(int) $data['entrenador_id'], $sesion?->entrenador_id])));
            sort($entrenadorIds);
            $entrenadores = User::whereIn('id', $entrenadorIds)->orderBy('id')->lockForUpdate()->get();
            $clienteIds = array_values(array_unique(array_filter([(int) $data['cliente_id'], $sesion?->cliente_id])));
            sort($clienteIds);
            $clientes = Cliente::whereIn('id', $clienteIds)->orderBy('id')->lockForUpdate()->get();
            $entrenador = $entrenadores->firstWhere('id', (int) $data['entrenador_id']);
            $cliente = $clientes->firstWhere('id', (int) $data['cliente_id']);
            if (! $entrenador?->activo || $entrenador->rol !== 'Entrenador' || ! $cliente?->activo) {
                throw ValidationException::withMessages(['sesion' => 'El cliente o entrenador ya no está activo. Recarga la agenda.']);
            }
            if ($sesion) {
                $actual = SesionEntrenador::whereKey($sesion->id)->lockForUpdate()->firstOrFail();
                $this->acceso($actual);
                if ($actual->entrenador_id !== $sesion->entrenador_id || $actual->cliente_id !== $sesion->cliente_id) {
                    throw ValidationException::withMessages(['sesion' => 'La sesión cambió mientras la editabas. Recarga la agenda.']);
                }
            }
            $solapada = SesionEntrenador::where('estado', '!=', 'cancelada')
                ->where(fn ($query) => $query->where('entrenador_id', $data['entrenador_id'])->orWhere('cliente_id', $data['cliente_id']))
                ->when($sesion, fn ($query) => $query->where('id', '!=', $sesion->id))
                ->where('fecha_inicio', '<', $data['fecha_fin'])->where('fecha_fin', '>', $data['fecha_inicio'])
                ->exists();
            if ($data['estado'] !== 'cancelada' && $solapada) {
                throw ValidationException::withMessages(['fecha_inicio' => 'El cliente o entrenador ya tiene una sesión en ese horario.']);
            }
            if ($sesion) {
                $actual->update($data);
            } else {
                SesionEntrenador::create($data + ['registrado_por' => $empleado->id]);
            }
        }, 3);
    }
}

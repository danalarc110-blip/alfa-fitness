<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CuentasController extends Controller
{
    public function index(Request $request)
    {
        $personal = Gate::allows('administrar') && $request->query('tipo') === 'personal';
        $query = $personal ? User::query() : Cliente::query();
        $campoNombre = $personal ? 'name' : 'nombre';
        $campoCorreo = $personal ? 'email' : 'correo';
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])]]);
        $q = $filtros['q'] ?? '';
        $estado = $filtros['estado'] ?? '';
        $cuentas = $query
            ->when($q, fn ($b) => $b->where(fn ($s) => $s->where($campoNombre, 'like', "%{$q}%")->orWhere($campoCorreo, 'like', "%{$q}%")))
            ->when($estado, fn ($b) => $b->where('activo', $estado === 'activo'))
            ->orderBy($campoNombre)->paginate(15)->withQueryString();
        return view('cuentas.index', compact('personal', 'cuentas', 'q', 'estado', 'campoNombre', 'campoCorreo'));
    }

    /** Clients self-register. This endpoint creates staff and never accepts a password or Administrator role. */
    public function store(Request $request)
    {
        Gate::authorize('administrar');
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'rol' => ['required', Rule::in(Acceso::ROLES_ASIGNABLES)],
        ]);
        $usuario = User::create([
            'name' => $data['nombre'], 'email' => $data['correo'],
            'password' => Str::random(64), 'password_establecida' => false,
            'rol' => $data['rol'], 'activo' => true,
        ]);
        $resultado = Password::broker('users')->sendResetLink(['email' => $usuario->email]);
        $mensaje = $resultado === Password::RESET_LINK_SENT
            ? 'Cuenta creada. Se envio un enlace privado para establecer la contrasena.'
            : 'Cuenta creada, pero no se pudo enviar la invitacion. Revise la configuracion de correo.';
        return back()->with('status', $mensaje);
    }

    public function update(Request $request, string $tipo, int $id)
    {
        abort_unless(in_array($tipo, ['personal', 'cliente'], true), 404);
        Gate::authorize('administrar');
        $cuentaActual = ($tipo === 'personal' ? User::query() : Cliente::query())->findOrFail($id);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'activo' => ['required', 'boolean'],
            'rol' => $tipo === 'personal'
                ? ['required', Rule::in($cuentaActual->rol === 'Administrador' ? ['Administrador'] : Acceso::ROLES_ASIGNABLES)]
                : ['prohibited'],
        ]);
        DB::transaction(function () use ($tipo, $id, $data) {
            if ($tipo === 'personal') User::where('rol', 'Administrador')->lockForUpdate()->get();
            $cuenta = ($tipo === 'personal' ? User::query() : Cliente::query())->lockForUpdate()->findOrFail($id);
            if ($tipo === 'personal' && $cuenta->rol === 'Administrador' && (! $data['activo'] || $data['rol'] !== 'Administrador')) {
                throw ValidationException::withMessages(['rol' => 'El administrador unico no puede desactivarse ni cambiar de rol.']);
            }
            $cuenta->update($tipo === 'personal'
                ? ['name' => $data['nombre'], 'activo' => $data['activo'], 'rol' => $data['rol']]
                : ['nombre' => $data['nombre'], 'activo' => $data['activo']]);
        });
        return back()->with('status', 'Cuenta actualizada. Se conserva su historial.');
    }

    public function reenviarInvitacion(User $usuario)
    {
        Gate::authorize('administrar');
        abort_if($usuario->password_establecida, 422, 'Esta cuenta ya establecio su contrasena.');
        $resultado = Password::broker('users')->sendResetLink(['email' => $usuario->email]);
        if ($resultado === Password::RESET_LINK_SENT) return back()->with('status', 'Invitacion reenviada.');
        return back()->withErrors(['correo' => 'No se pudo enviar. Revise la configuracion de correo.']);
    }
}

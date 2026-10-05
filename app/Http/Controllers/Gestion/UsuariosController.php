<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Invitaciones;
use App\Support\Acceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsuariosController extends Controller
{
    private function acceso(): void
    {
        abort_unless(! auth('cliente')->check() && Acceso::permite(auth('web')->user(), 'administrar'), 403);
    }

    public function index(Request $request)
    {
        $this->acceso();
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $filtros['q'] ?? '';
        $usuarios = User::query()->when($q, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('gestion.usuarios.index', compact('usuarios', 'q'));
    }

    public function create()
    {
        $this->acceso();

        return view('gestion.usuarios.form', ['usuario' => new User]);
    }

    public function store(Request $request)
    {
        $this->acceso();
        $data = $this->validar($request);
        $usuario = User::create($data + ['password' => Str::random(64), 'password_establecida' => false, 'activo' => true]);
        $enviado = app(Invitaciones::class)->enviar($usuario);

        return redirect()->route('gestion.usuarios.index')->with('status', $enviado
            ? 'Empleado creado. Se envió su enlace privado para establecer la contraseña.'
            : 'Empleado creado. No se pudo enviar la invitación; revisa la configuración del correo y usa Reenviar invitación.');
    }

    public function edit(User $usuario)
    {
        $this->acceso();
        abort_if($usuario->rol === 'Administrador', 403);

        return view('gestion.usuarios.form', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        $this->acceso();
        abort_if($usuario->rol === 'Administrador', 403);
        $data = $this->validar($request, $usuario);
        DB::transaction(function () use ($usuario, $data) {
            $bloqueado = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            abort_if($bloqueado->rol === 'Administrador', 403);
            $bloqueado->update($data);
        });

        return redirect()->route('gestion.usuarios.index')->with('status', 'Empleado actualizado.');
    }

    public function destroy(User $usuario)
    {
        $this->acceso();
        abort_if($usuario->rol === 'Administrador', 403);
        $usuario->update(['activo' => false]);

        return back()->with('status', 'Empleado desactivado. Se conservó su historial.');
    }

    public function reactivar(User $usuario)
    {
        $this->acceso();
        abort_if($usuario->rol === 'Administrador', 403);
        $usuario->update(['activo' => true]);

        return back()->with('status', 'Empleado reactivado.');
    }

    public function invitar(User $usuario)
    {
        $this->acceso();
        abort_if($usuario->rol === 'Administrador', 403);
        $enviado = app(Invitaciones::class)->enviar($usuario);

        return back()->with('status', $enviado ? 'Invitación enviada.' : 'No se pudo enviar. Comprueba que la cuenta esté activa, pendiente de establecer contraseña y que el correo esté configurado.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'rol' => ['required', Rule::in(Acceso::ROLES_ASIGNABLES)],
        ], ['email.unique' => 'Ya existe un empleado con este correo.', 'rol.in' => 'Selecciona Secretaria o Entrenador.']);
    }
}

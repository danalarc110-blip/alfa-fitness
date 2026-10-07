<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImagenSegura;
use App\Services\Invitaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EntrenadorController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'rol' => ['nullable', 'string', Rule::in(['Todos', 'Entrenador', 'Secretaria'])],
        ]);
        $busqueda = $filtros['q'] ?? '';
        $filtroRol = $filtros['rol'] ?? 'Todos';
        $esAdmin = auth('web')->user()?->rol === 'Administrador';

        $query = User::query()->orderBy('name');
        if ($esAdmin) {
            if ($filtroRol && $filtroRol !== 'Todos') {
                $query->where('rol', $filtroRol);
            } else {
                $query->whereIn('rol', ['Entrenador', 'Secretaria']);
            }
        } else {
            $query->where('rol', 'Entrenador')->where('activo', true);
        }

        $query->when($busqueda, fn ($builder) => $builder->where(function ($search) use ($busqueda, $esAdmin) {
            $search->where('name', 'like', "%{$busqueda}%");
            if ($esAdmin) {
                $search->orWhere('email', 'like', "%{$busqueda}%");
            }
        }));

        return view('entrenadores.index', [
            'entrenadores' => $query->paginate(9)->withQueryString(),
            'busqueda' => $busqueda,
            'filtroRol' => $filtroRol,
            'esAdmin' => $esAdmin,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Administrador', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['nullable', 'string', Rule::in(['Entrenador', 'Secretaria'])],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
        ]);
        $rolAsignado = $data['rol'] ?? 'Entrenador';
        $nuevo = null;
        $usuario = null;
        try {
            $personal = DB::transaction(function () use ($data, $request, $rolAsignado, &$nuevo, &$usuario) {
                $usuario = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Str::random(64),
                    'password_establecida' => false,
                    'rol' => $rolAsignado,
                    'activo' => true,
                ]);
                if ($request->hasFile('avatar')) {
                    $nuevo = app(ImagenSegura::class)->guardar($request->file('avatar'), 'avatars', 'web_'.$usuario->id);
                    $usuario->update(['avatar' => $nuevo]);
                }

                return $usuario;
            });
        } catch (\Throwable $error) {
            if ($nuevo) {
                app(ImagenSegura::class)->eliminar($nuevo, 'avatars', 'web_'.$usuario->id);
            }
            throw $error;
        }
        $resultado = app(Invitaciones::class)->enviar($personal);

        return back()->with('status', $resultado
            ? "Personal ({$rolAsignado}) creado exitosamente. Se envió el enlace privado para establecer la contraseña."
            : "Personal ({$rolAsignado}) creado; revise el correo para poder enviar la invitación.");
    }

    public function update(Request $request, User $entrenador)
    {
        abort_unless(auth('web')->user()?->rol === 'Administrador' && in_array($entrenador->rol, ['Entrenador', 'Secretaria'], true), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($entrenador->id)],
            'rol' => ['nullable', 'string', Rule::in(['Entrenador', 'Secretaria'])],
            'activo' => ['required', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
            'eliminar_avatar' => ['nullable', 'boolean'],
        ]);
        $rolNuevo = $data['rol'] ?? $entrenador->rol;
        $anterior = $entrenador->avatar;
        $nuevo = null;
        if ($request->hasFile('avatar')) {
            $nuevo = app(ImagenSegura::class)->guardar($request->file('avatar'), 'avatars', 'web_'.$entrenador->id);
        }
        try {
            DB::transaction(function () use ($entrenador, $data, $rolNuevo, $nuevo, $request, &$anterior) {
                $entrenador = User::whereKey($entrenador->id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($entrenador->rol, ['Entrenador', 'Secretaria'], true), 403);
                $anterior = $entrenador->avatar;
                $entrenador->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'rol' => $rolNuevo,
                    'activo' => $data['activo'],
                    'avatar' => $nuevo ?: ($request->boolean('eliminar_avatar') ? null : $anterior),
                ]);
            });
        } catch (\Throwable $e) {
            if ($nuevo) {
                app(ImagenSegura::class)->eliminar($nuevo, 'avatars', 'web_'.$entrenador->id);
            }
            throw $e;
        }
        if ($nuevo || $request->boolean('eliminar_avatar')) {
            app(ImagenSegura::class)->eliminar($anterior, 'avatars', 'web_'.$entrenador->id);
        }

        return back()->with('status', 'Personal actualizado.');
    }
}

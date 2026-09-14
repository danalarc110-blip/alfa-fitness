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
        ]);
        $busqueda = $filtros['q'] ?? '';
        $esAdmin = auth('web')->user()?->rol === 'Administrador';
        $query = User::where('rol', 'Entrenador')->orderBy('name');
        if (! $esAdmin) {
            $query->where('activo', true);
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
            'esAdmin' => $esAdmin,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Administrador', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
        ]);
        $nuevo = null;
        $usuario = null;
        try {
            $entrenador = DB::transaction(function () use ($data, $request, &$nuevo, &$usuario) {
                $usuario = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::random(64), 'password_establecida' => false, 'rol' => 'Entrenador', 'activo' => true]);
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
        $resultado = app(Invitaciones::class)->enviar($entrenador);

        return back()->with('status', $resultado ? 'Entrenador creado. Se envio su enlace privado para establecer la contrasena.' : 'Entrenador creado; revise el correo para poder enviar la invitacion.');
    }

    public function update(Request $request, User $entrenador)
    {
        abort_unless(auth('web')->user()?->rol === 'Administrador' && $entrenador->rol === 'Entrenador', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($entrenador->id)],
            'activo' => ['required', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
            'eliminar_avatar' => ['nullable', 'boolean'],
        ]);
        $anterior = $entrenador->avatar;
        $nuevo = null;
        if ($request->hasFile('avatar')) {
            $nuevo = app(ImagenSegura::class)->guardar($request->file('avatar'), 'avatars', 'web_'.$entrenador->id);
        }
        try {
            DB::transaction(function () use ($entrenador, $data, $nuevo, $request, &$anterior) {
                $entrenador = User::whereKey($entrenador->id)->lockForUpdate()->firstOrFail();
                abort_unless($entrenador->rol === 'Entrenador', 403);
                $anterior = $entrenador->avatar;
                $entrenador->update(['name' => $data['name'], 'email' => $data['email'], 'activo' => $data['activo'], 'avatar' => $nuevo ?: ($request->boolean('eliminar_avatar') ? null : $anterior)]);
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

        return back()->with('status', 'Entrenador actualizado.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImagenSegura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EntrenadorController extends Controller
{
    public function index()
    {
        $query = User::where('rol', 'Entrenador')->orderBy('name');
        if (auth('web')->user()?->rol !== 'Administrador') $query->where('activo', true);
        return view('entrenadores.index', ['entrenadores' => $query->paginate(12)]);
    }

    public function store(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Administrador', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
        ]);
        $entrenador = DB::transaction(function () use ($data, $request) {
            $usuario = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::random(64), 'password_establecida' => false, 'rol' => 'Entrenador', 'activo' => true]);
            if ($request->hasFile('avatar')) {
                $usuario->update(['avatar' => app(ImagenSegura::class)->guardar($request->file('avatar'), 'avatars', 'web_'.$usuario->id)]);
            }
            return $usuario;
        });
        $resultado = Password::broker('users')->sendResetLink(['email' => $entrenador->email]);
        return back()->with('status', $resultado === Password::RESET_LINK_SENT ? 'Entrenador creado. Se envio su enlace privado para establecer la contrasena.' : 'Entrenador creado; revise el correo para poder enviar la invitacion.');
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
        if ($request->hasFile('avatar')) $nuevo = app(ImagenSegura::class)->guardar($request->file('avatar'), 'avatars', 'web_'.$entrenador->id);
        try {
            $entrenador->update(['name' => $data['name'], 'email' => $data['email'], 'activo' => $data['activo'], 'avatar' => $nuevo ?: ($request->boolean('eliminar_avatar') ? null : $anterior)]);
        } catch (\Throwable $e) {
            if ($nuevo) app(ImagenSegura::class)->eliminar($nuevo, 'avatars', 'web_'.$entrenador->id);
            throw $e;
        }
        if ($nuevo || $request->boolean('eliminar_avatar')) app(ImagenSegura::class)->eliminar($anterior, 'avatars', 'web_'.$entrenador->id);
        return back()->with('status', 'Entrenador actualizado.');
    }
}

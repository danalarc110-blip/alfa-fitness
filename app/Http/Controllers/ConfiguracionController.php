<?php

namespace App\Http\Controllers;

use App\Services\ImagenSegura;
use App\Support\Apariencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class ConfiguracionController extends Controller
{
    /**
     * Devuelve ['guard' => 'web'|'cliente', 'user' => modelo autenticado].
     * Así el resto del controlador no necesita saber si es empleado o cliente.
     */
    public function show()
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        return view('perfil', [
            'guard' => $guard,
            'usuarioActual' => $user,
            'nombre' => $guard === 'web' ? $user->name : $user->nombre,
            'correo' => $guard === 'web' ? $user->email : $user->correo,
            'esGoogle' => $guard === 'cliente' && empty($user->password),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'miembroDesde' => $this->miembroDesde($user),
            'avatarUrl' => $user->avatar_url,
        ]);
    }

    /**
     * Formatea la fecha de registro como "Ene 2025", sin depender del locale del servidor.
     */
    private function miembroDesde($user): ?string
    {
        if (! $user->created_at) {
            return null;
        }

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return $meses[$user->created_at->month - 1].' '.$user->created_at->year;
    }

    public function actualizarPerfil(Request $request): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        $campoNombre = $guard === 'web' ? 'name' : 'nombre';

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
        ]);

        $user->update([$campoNombre => $data['nombre']]);

        return back()->with('status', 'Perfil actualizado.');
    }

    public function actualizarPassword(Request $request): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        if ($guard === 'cliente' && empty($user->password)) {
            return back()->withErrors(['password_actual' => 'Tu cuenta usa Google, no tiene contraseña para cambiar.']);
        }

        $data = $request->validate([
            'password_actual' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'password_actual.required' => 'Ingresa tu contraseña actual.',
            'password.min' => 'La nueva contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'La confirmación no coincide.',
        ]);

        if (! Hash::check($data['password_actual'], $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $user->forceFill(['password' => bcrypt($data['password']), 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('status', 'Contraseña actualizada.');
    }

    public function actualizarApariencia(Request $request)
    {
        ['user' => $user] = $this->actual();
        $rules = ['mode' => ['required', Rule::in(['light', 'dark', 'custom'])], 'colors' => ['required', 'array:primary,accent,background,surface,text']];
        foreach (array_keys(Apariencia::PALETAS['light']) as $key) {
            $rules['colors.'.$key] = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/D'];
        }
        $data = $request->validate($rules);
        Apariencia::validar($data['colors']);
        $user->update(['apariencia' => $data]);

        return $request->expectsJson()
            ? response()->json(['appearance' => $data])
            : back()->with('status', 'Apariencia guardada en tu cuenta.');
    }

    public function vincularGoogle(Request $request): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'cliente' && $user->password && ! $user->google_id, 403);
        $request->validate(['password_actual' => ['required', 'string', 'current_password:cliente']]);
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return back()->withErrors(['google' => 'Google no está disponible en este momento.']);
        }
        $request->session()->put('google_link', ['id' => $user->id, 'expires' => now()->addMinutes(5)->timestamp]);

        return Socialite::driver('google')->redirect();
    }

    public function actualizarPersonalizacion(Request $request): RedirectResponse
    {
        ['user' => $user] = $this->actual();

        $data = $request->validate([
            'color_acento' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'avatar_piel' => ['required', Rule::in(['claro', 'medio', 'oscuro'])],
            'avatar_cabello' => ['required', Rule::in(['corto', 'largo', 'rizado', 'calvo'])],
            'avatar_barba' => ['required', Rule::in(['ninguna', 'candado', 'completa'])],
            'avatar_atuendo' => ['required', Rule::in(['basica', 'deportiva', 'formal'])],
            'avatar_color_atuendo' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $user->update($data);

        return back()->with('status', 'Personalización guardada.');
    }

    public function actualizarAvatar(Request $request): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ], [
            'avatar.required' => 'Selecciona una imagen.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.mimes' => 'Formatos permitidos: JPG, PNG y WebP.',
            'avatar.max' => 'La imagen no debe superar los 2MB.',
        ]);

        $anterior = $user->avatar;
        $imagenes = app(ImagenSegura::class);
        $prefijo = $guard.'_'.$user->id;
        $nombreArchivo = $imagenes->guardar($request->file('avatar'), 'avatars', $prefijo);
        try {
            DB::transaction(function () use ($user, $nombreArchivo, &$anterior) {
                $cuenta = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $anterior = $cuenta->avatar;
                $cuenta->update(['avatar' => $nombreArchivo]);
            });
        } catch (\Throwable $error) {
            $imagenes->eliminar($nombreArchivo, 'avatars', $prefijo);
            throw $error;
        }
        // Delete only a previous avatar owned by this account, after persisting the replacement.
        $imagenes->eliminar($anterior, 'avatars', $prefijo);

        return back()->with('status', 'Avatar actualizado.');
    }
}

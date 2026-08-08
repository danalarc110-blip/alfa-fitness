<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ConfiguracionController extends Controller
{
    /**
     * Devuelve ['guard' => 'web'|'cliente', 'user' => modelo autenticado].
     * Así el resto del controlador no necesita saber si es empleado o cliente.
     */
    private function actual(): array
    {
        if (Auth::guard('web')->check()) {
            return ['guard' => 'web', 'user' => Auth::guard('web')->user()];
        }

        return ['guard' => 'cliente', 'user' => Auth::guard('cliente')->user()];
    }

    public function show()
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        return view('configuracion', [
            'guard' => $guard,
            'usuarioActual' => $user,
            'nombre' => $guard === 'web' ? $user->name : $user->nombre,
            'correo' => $guard === 'web' ? $user->email : $user->correo,
            'esGoogle' => $guard === 'cliente' && ! empty($user->google_id),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'miembroDesde' => $this->miembroDesde($user),
            'avatarUrl' => $user->avatar ? asset('images/avatars/'.$user->avatar) : null,
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

        if ($guard === 'cliente' && ! empty($user->google_id)) {
            return back()->withErrors(['password_actual' => 'Tu cuenta usa Google, no tiene contraseña para cambiar.']);
        }

        $data = $request->validate([
            'password_actual' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_actual.required' => 'Ingresa tu contraseña actual.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide.',
        ]);

        if (! Hash::check($data['password_actual'], $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['password' => bcrypt($data['password'])]);

        return back()->with('status', 'Contraseña actualizada.');
    }

    public function actualizarPersonalizacion(Request $request): RedirectResponse
    {
        ['user' => $user] = $this->actual();

        $data = $request->validate([
            'color_acento' => ['required', 'string', 'max:20'],
            'avatar_piel' => ['required', Rule::in(['claro', 'medio', 'oscuro'])],
            'avatar_cabello' => ['required', Rule::in(['corto', 'largo', 'rizado', 'calvo'])],
            'avatar_barba' => ['required', Rule::in(['ninguna', 'candado', 'completa'])],
            'avatar_atuendo' => ['required', Rule::in(['basica', 'deportiva', 'formal'])],
            'avatar_color_atuendo' => ['required', 'string', 'max:20'],
        ]);

        $user->update($data);

        return back()->with('status', 'Personalización guardada.');
    }

    public function actualizarAvatar(Request $request): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'avatar.required' => 'Selecciona una imagen.',
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.mimes' => 'Formatos permitidos: JPG y PNG.',
            'avatar.max' => 'La imagen no debe superar los 2MB.',
        ]);

        $carpeta = public_path('images/avatars');
        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        // Borra el avatar anterior para no dejar archivos huérfanos
        if ($user->avatar && file_exists($carpeta.'/'.$user->avatar)) {
            @unlink($carpeta.'/'.$user->avatar);
        }

        $archivo = $request->file('avatar');
        $nombreArchivo = $guard.'_'.$user->id.'_'.time().'.'.$archivo->getClientOriginalExtension();
        $archivo->move($carpeta, $nombreArchivo);

        $user->update(['avatar' => $nombreArchivo]);

        return back()->with('status', 'Avatar actualizado.');
    }
}

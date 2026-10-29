<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AvisoPrivacidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function reconocerPrivacidad(Request $request): RedirectResponse
    {
        $request->validate([
            'privacy_read' => ['accepted'],
            'privacy_version' => ['required', Rule::in([config('privacy.version')])],
        ], [
            'privacy_read.accepted' => 'Confirma que has leído la política de privacidad.',
            'privacy_version.in' => 'La política se actualizó. Recarga la página y revisa la versión vigente.',
        ]);

        $request->session()->put('privacy_notice', [
            'version' => config('privacy.version'),
            'accepted_at' => now()->toDateTimeString(),
        ]);

        return redirect()->route('login');
    }

    /**
     * Muestra el formulario de login.
     * Si ya hay una sesión activa (empleado o cliente), lo mandamos
     * directo a su panel en vez de pedirle iniciar sesión de nuevo.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        if (Auth::guard('cliente')->check()) {
            return redirect()->route('cliente.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Procesa el intento de inicio de sesión.
     * Solo mantiene el acceso entre sesiones si se solicita explícitamente.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (! Auth::guard('web')->attempt(array_merge($credentials, ['activo' => true, 'password_establecida' => true]), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros o la cuenta está inactiva.',
            ]);
        }

        Auth::guard('cliente')->logout();
        AvisoPrivacidad::registrar($request, Auth::guard('web')->user());
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * "Salir": abandona el panel pero mantiene la sesión activa,
     * así al volver no hace falta iniciar sesión otra vez.
     */
    public function salir(): RedirectResponse
    {
        return redirect()->route('informacion');
    }

    /**
     * "Cerrar sesión": cierra la sesión por completo (invalida la
     * sesión y el token de "recordarme").
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('cliente')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Muestra el formulario de login.
     * Si ya hay una sesión activa (empleado o cliente), lo mandamos
     * directo a su panel en vez de pedirle iniciar sesión de nuevo.
     */
    public function showLoginForm(): \Illuminate\View\View|RedirectResponse
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
     * Siempre se recuerda al usuario para que no tenga que volver
     * a iniciar sesión la próxima vez que entre.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

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

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\AvisoPrivacidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class ClienteLoginController extends Controller
{
    /**
     * Registra un cliente nuevo con correo + contraseña propia.
     */
    public function registrar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'string', 'email', 'max:255', 'unique:clientes,correo'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $cliente = Cliente::create([
            'nombre' => $data['nombre'],
            'correo' => $data['correo'],
            'password' => bcrypt($data['password']),
            'activo' => true,
        ]);

        Auth::guard('cliente')->login($cliente, false);
        AvisoPrivacidad::registrar($request, $cliente);

        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->intended(route('cliente.dashboard'));
    }

    /**
     * Procesa el login del cliente con correo + contraseña propia.
     * Solo mantiene el acceso entre sesiones si se solicita explícitamente.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'correo' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (! Auth::guard('cliente')->attempt(array_merge($credentials, ['activo' => true]), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'correo' => 'Las credenciales no coinciden con nuestros registros o la cuenta está inactiva.',
            ]);
        }

        AvisoPrivacidad::registrar($request, Auth::guard('cliente')->user());
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->intended(route('cliente.dashboard'));
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
     * Cierra la sesión local; no modifica la sesión del proveedor Google.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('cliente')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirige a Google para iniciar el flujo de OAuth.
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $request->session()->forget('google_link');
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->withErrors(['correo' => 'Google no está disponible. Ingresa con tu correo y contraseña.']);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Google regresa aquí después de que el cliente autoriza el acceso.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $vinculacion = $request->session()->pull('google_link');
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            // OAuth exceptions can contain access tokens; do not log their payload.
            Log::warning('Fallo de autenticacion Google.', ['exception' => get_class($e)]);

            return redirect()->route('login')->withErrors(['correo' => 'No se pudo completar el acceso con Google. Intenta nuevamente.']);
        }
        if (! $googleUser->getId() || ! $googleUser->getEmail() || ($googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false) !== true) {
            return redirect()->route('login')->withErrors(['correo' => 'Google no proporcionó un correo verificado.']);
        }

        // The provider subject is stable even if its email address changes.
        $cliente = Cliente::where('google_id', $googleUser->getId())->first()
            ?? Cliente::where('correo', $googleUser->getEmail())->first();
        $vinculacionAutorizada = $vinculacion && $cliente
            && $vinculacion['id'] === $cliente->id
            && $vinculacion['expires'] >= now()->timestamp
            && Auth::guard('cliente')->id() === $cliente->id
            && strcasecmp($cliente->correo, $googleUser->getEmail()) === 0;
        if (($vinculacion && ! $vinculacionAutorizada) || ($cliente && ! $cliente->google_id && ! $vinculacionAutorizada)) {
            return redirect()->route($vinculacion ? 'configuracion' : 'login')->withErrors(['correo' => 'Para vincular una cuenta existente, inicia sesión con tu contraseña y autoriza Google desde Ajustes. Usa la misma dirección de correo.']);
        }

        if ($cliente) {
            if ($cliente->google_id && $cliente->google_id !== $googleUser->getId()) {
                return redirect()->route('login')->withErrors(['correo' => 'Esta cuenta está vinculada a otra identidad de Google.']);
            }
            if (! $cliente->activo) {
                return redirect()->route('login')->withErrors([
                    'correo' => 'Tu cuenta ha sido desactivada. Comunícate con recepción.',
                ]);
            }

            $cliente->update(['google_id' => $googleUser->getId()]);
        } else {
            $cliente = Cliente::create([
                'nombre' => $googleUser->getName() ?: 'Cliente Google',
                'correo' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'activo' => true,
            ]);
        }

        AvisoPrivacidad::registrar($request, $cliente);
        Auth::guard('cliente')->login($cliente, false);

        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->intended(route('cliente.dashboard'));
    }
}

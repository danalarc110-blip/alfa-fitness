<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class ClienteLoginController extends Controller
{
    /**
     * Registra un cliente nuevo con correo + contraseña propia.
     * Antes de esto no existía ninguna forma de crear un cliente
     * sin pasar por Google, así que el login local nunca podía funcionar.
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
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $cliente = Cliente::create([
            'nombre' => $data['nombre'],
            'correo' => $data['correo'],
            'password' => bcrypt($data['password']),
            'activo' => true,
        ]);

        Auth::guard('cliente')->login($cliente, false);

        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->intended(route('cliente.dashboard'));
    }

    /**
     * Procesa el login del cliente con correo + contraseña propia.
     * Siempre se recuerda al cliente para que no tenga que volver
     * a iniciar sesión la próxima vez que entre.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'correo'   => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'correo.required'   => 'El correo electrónico es obligatorio.',
            'correo.email'      => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (! Auth::guard('cliente')->attempt(array_merge($credentials, ['activo' => true]), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'correo' => 'Las credenciales no coinciden con nuestros registros o la cuenta está inactiva.',
            ]);
        }

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
     * "Cerrar sesión": cierra la sesión por completo. Si el cliente
     * entró con Google, además lo saca de su sesión de Google para
     * que no vuelva a entrar automáticamente con esa cuenta.
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
    public function redirectToGoogle(): RedirectResponse
    {
        if (!config('services.google.client_id') || !config('services.google.client_secret')) return redirect()->route('login')->withErrors(['correo' => 'Google no está disponible. Ingresa con tu correo y contraseña.']);
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google regresa aquí después de que el cliente autoriza el acceso.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->withErrors(['correo' => 'No se pudo completar el acceso con Google. Intenta nuevamente.']);
        }
        if (!$googleUser->getEmail() || !($googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false)) return redirect()->route('login')->withErrors(['correo' => 'Google no proporcionó un correo verificado.']);

        // Si ya existe un cliente con ese correo (registrado antes con
        // contraseña propia), le vinculamos el google_id sin sobreescribir sus datos personalizados.
        $cliente = Cliente::where('correo', $googleUser->getEmail())->first();

        if ($cliente) {
            if (! $cliente->activo) {
                return redirect()->route('login')->withErrors([
                    'correo' => 'Tu cuenta ha sido desactivada. Comunícate con recepción.',
                ]);
            }

            $datosActualizar = ['google_id' => $googleUser->getId()];
            if (empty($cliente->avatar)) {
                $datosActualizar['avatar'] = $googleUser->getAvatar();
            }
            $cliente->update($datosActualizar);
        } else {
            $cliente = Cliente::create([
                'nombre'    => $googleUser->getName() ?: 'Cliente Google',
                'correo'    => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
                'activo'    => true,
            ]);
        }

        Auth::guard('cliente')->login($cliente, false);

        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->intended(route('cliente.dashboard'));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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

        Auth::guard('cliente')->login($cliente, true);

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

        if (! Auth::guard('cliente')->attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'correo' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

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
        $cliente = Auth::guard('cliente')->user();
        $eraGoogle = $cliente && ! empty($cliente->google_id);

        Auth::guard('cliente')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($eraGoogle) {
            $continuar = urlencode(route('login'));

            return redirect()->away("https://accounts.google.com/Logout?continue=https://appengine.google.com/_ah/logout?continue={$continuar}");
        }

        return redirect()->route('login');
    }

    /**
     * Redirige a Google para iniciar el flujo de OAuth.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google regresa aquí después de que el cliente autoriza el acceso.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        // Si ya existe un cliente con ese correo (registrado antes con
        // contraseña propia), le vinculamos el google_id en vez de duplicar.
        $cliente = Cliente::where('correo', $googleUser->getEmail())->first()
            ?? new Cliente();

        $cliente->fill([
            'nombre' => $googleUser->getName(),
            'correo' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'activo' => true,
        ])->save();

        Auth::guard('cliente')->login($cliente, true);

        return redirect()->intended(route('cliente.dashboard'));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Rules\PasswordSinTruncamiento;
use App\Services\ConsentimientoLegal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols(), new PasswordSinTruncamiento],
            'aceptacion_legal' => ['required', 'accepted'],
            'legal_version' => ['required', 'string', Rule::in([config('legal.version')])],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'correo.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'aceptacion_legal.required' => 'Debes leer y aceptar los documentos del registro.',
            'aceptacion_legal.accepted' => 'Debes leer y aceptar los documentos del registro.',
            'legal_version.in' => 'Los documentos cambiaron. Vuelve a leerlos antes de aceptar.',
        ]);

        try {
            $cliente = DB::transaction(function () use ($data) {
                $cliente = new Cliente([
                    'nombre' => $data['nombre'], 'correo' => $data['correo'],
                    'password' => $data['password'], 'activo' => true,
                ]);

                return app(ConsentimientoLegal::class)->aceptar($cliente);
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['correo' => 'Ya existe una cuenta con ese correo.']);
        }
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
            $request->session()->put('google_registro_pendiente', [
                'nombre' => $googleUser->getName() ?: 'Cliente Google',
                'correo' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'expires' => now()->addMinutes(5)->timestamp,
            ]);

            return redirect()->route('cliente.google.consentimiento');
        }

        Auth::guard('cliente')->login($cliente, false);

        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->intended(route('cliente.dashboard'));
    }

    public function consentimientoGoogle(Request $request)
    {
        $pendiente = $request->session()->get('google_registro_pendiente');
        if (! $pendiente || $pendiente['expires'] < now()->timestamp) {
            $request->session()->forget('google_registro_pendiente');

            return redirect()->route('login')->withErrors(['correo' => 'El registro con Google expiró. Intenta nuevamente.']);
        }

        return response()->view('legal.google-consentimiento')->header('Cache-Control', 'no-store, private');
    }

    public function aceptarGoogle(Request $request): RedirectResponse
    {
        $request->validate(['aceptacion_legal' => ['required', 'accepted'], 'legal_version' => ['required', 'string', Rule::in([config('legal.version')])]], [
            'aceptacion_legal.required' => 'Debes leer y aceptar los documentos del registro.',
            'aceptacion_legal.accepted' => 'Debes leer y aceptar los documentos del registro.',
            'legal_version.in' => 'Los documentos cambiaron. Vuelve a leerlos antes de aceptar.',
        ]);
        $pendiente = $request->session()->pull('google_registro_pendiente');
        if (! $pendiente || $pendiente['expires'] < now()->timestamp) {
            return redirect()->route('login')->withErrors(['correo' => 'El registro con Google expiró. Intenta nuevamente.']);
        }
        // A concurrent registration never silently links or authenticates an existing account.
        if (Cliente::where('correo', $pendiente['correo'])->orWhere('google_id', $pendiente['google_id'])->exists()) {
            return redirect()->route('login')->withErrors(['correo' => 'La cuenta ya existe. Inicia sesión nuevamente.']);
        }
        try {
            $cliente = DB::transaction(function () use ($pendiente) {
                $cliente = new Cliente(collect($pendiente)->only(['nombre', 'correo', 'google_id'])->all());
                $cliente->activo = true;

                return app(ConsentimientoLegal::class)->aceptar($cliente);
            });
        } catch (UniqueConstraintViolationException $exception) {
            return redirect()->route('login')->withErrors(['correo' => 'La cuenta ya existe. Inicia sesión nuevamente.']);
        }
        Auth::guard('cliente')->login($cliente, false);
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->intended(route('cliente.dashboard'));
    }
}

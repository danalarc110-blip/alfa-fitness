<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditoriaSeguridad;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DosFactoresController extends Controller
{
    public function configurar()
    {
        Gate::authorize('administrar');

        return response()->view('auth.dos-factores')->header('Cache-Control', 'private, no-store');
    }

    private function confirmarPassword(Request $request): void
    {
        $request->validate(['password_actual' => ['required', 'string']]);
        if (! Hash::check($request->input('password_actual'), $request->user()->password)) {
            throw ValidationException::withMessages(['password_actual' => 'La contraseña actual no es correcta.']);
        }
    }

    public function preparar(Request $request, Totp $totp)
    {
        Gate::authorize('administrar');
        $this->confirmarPassword($request);
        if ($request->user()->two_factor_confirmed_at) {
            return back()->withErrors(['codigo' => 'La verificación ya está activa. Desactívala antes de configurar otro dispositivo.']);
        }
        $request->session()->put('two_factor_setup', ['id' => $request->user()->id, 'secret' => $totp->secret(), 'expires' => now()->addMinutes(10)->timestamp]);
        $request->session()->regenerate();

        return back();
    }

    public function activar(Request $request, Totp $totp)
    {
        Gate::authorize('administrar');
        $this->confirmarPassword($request);
        $request->validate(['codigo' => ['required', 'string', 'regex:/^[0-9]{6}$/D']]);
        $setup = $request->session()->get('two_factor_setup');
        $step = $setup && $setup['id'] === $request->user()->id && $setup['expires'] >= now()->timestamp
            ? $totp->matchingStep($setup['secret'], $request->input('codigo')) : null;
        if ($step === null) {
            throw ValidationException::withMessages(['codigo' => 'Código incorrecto o configuración expirada.']);
        }
        $codes = array_map(fn () => bin2hex(random_bytes(16)), range(1, 8));
        DB::transaction(function () use ($request, $setup, $step, $codes) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_if($user->two_factor_confirmed_at, 409);
            $user->forceFill(['two_factor_secret' => $setup['secret'], 'two_factor_confirmed_at' => now(), 'two_factor_last_step' => $step, 'remember_token' => Str::random(60), 'two_factor_recovery_codes' => array_map(fn ($code) => hash('sha256', $code), $codes)])->save();
        });
        $request->session()->forget('two_factor_setup');
        $this->marcarVerificado($request, $request->user()->fresh());
        $request->session()->flash('two_factor_recovery_plain', $codes);

        return back()->with('status', 'Verificación en dos pasos activada. Guarda los códigos de recuperación fuera de este equipo.');
    }

    public function desactivar(Request $request, Totp $totp)
    {
        Gate::authorize('administrar');
        $this->confirmarPassword($request);
        $request->validate(['codigo' => ['required', 'string', 'max:32']]);
        $this->consumirCodigo($request->user()->id, $request->input('codigo', ''), $totp);
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_last_step' => null, 'two_factor_recovery_codes' => null])->save();

        return back()->with('status', 'Verificación en dos pasos desactivada.');
    }

    public function desafio(Request $request)
    {
        if (! $request->session()->has('two_factor_login')) {
            return redirect()->route('login');
        }

        return response()->view('auth.desafio-dos-factores')->header('Cache-Control', 'private, no-store');
    }

    public function verificar(Request $request, Totp $totp)
    {
        $request->validate(['codigo' => ['required', 'string', 'max:32']]);
        $login = $request->session()->get('two_factor_login');
        $user = $login ? User::find($login['id']) : null;
        if (! $user || ! $user->activo || $user->rol !== 'Administrador' || ! $user->two_factor_confirmed_at || $login['expires'] < now()->timestamp || ! hash_equals($login['password_hash'], hash('sha256', $user->password))) {
            $request->session()->forget('two_factor_login');

            return redirect()->route('login')->withErrors(['email' => 'El acceso expiró. Ingresa nuevamente.']);
        }
        $this->consumirCodigo($user->id, $request->input('codigo'), $totp, $login['password_hash']);
        $request->session()->forget('two_factor_login');
        Auth::guard('cliente')->logout();
        Auth::guard('web')->login($user, false);
        $this->marcarVerificado($request, $user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function marcarVerificado(Request $request, User $user): void
    {
        $request->session()->put('two_factor_verified', ['id' => $user->id, 'confirmed_at' => $user->two_factor_confirmed_at->timestamp, 'password_hash' => hash('sha256', $user->password), 'factor_hash' => hash('sha256', (string) $user->two_factor_secret)]);
    }

    private function consumirCodigo(int $id, string $code, Totp $totp, ?string $passwordHash = null): void
    {
        DB::transaction(function () use ($id, $code, $totp, $passwordHash) {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($user->activo && $user->rol === 'Administrador' && $user->two_factor_confirmed_at, 403);
            if ($passwordHash !== null && ! hash_equals($passwordHash, hash('sha256', $user->password))) {
                throw ValidationException::withMessages(['codigo' => 'El acceso expiró. Ingresa nuevamente.']);
            }
            $step = $user->two_factor_secret ? $totp->matchingStep($user->two_factor_secret, $code, $user->two_factor_last_step) : null;
            if ($step !== null) {
                $user->forceFill(['two_factor_last_step' => $step])->save();

                return;
            }
            $hash = hash('sha256', strtolower(trim($code)));
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $stored) {
                if (hash_equals($stored, $hash)) {
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                    return;
                }
            }
            app(AuditoriaSeguridad::class)->dosFactoresFallido($id);
            throw ValidationException::withMessages(['codigo' => 'Código incorrecto o ya utilizado.']);
        });
    }
}

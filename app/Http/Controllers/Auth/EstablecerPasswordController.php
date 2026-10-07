<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class EstablecerPasswordController extends Controller
{
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);
        $email = $request->string('email')->trim()->toString();

        // Accounts in different providers can legitimately share an address.
        foreach (['users' => 'email', 'clientes' => 'correo'] as $broker => $campo) {
            try {
                Password::broker($broker)->sendResetLink([$campo => $email, 'activo' => true]);
            } catch (\Throwable $error) {
                // Transport errors may contain credentials and private reset URLs.
                Log::warning('No se pudo enviar un enlace de recuperación.', ['exception' => get_class($error)]);
            }
        }

        return back()->with('status', 'Si tu correo coincide con una cuenta activa en el sistema, recibirás un enlace seguro para restablecer tu contraseña.');
    }

    public function show(Request $request, string $token)
    {
        return view('auth.establecer-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
        ]);

        $userReset = Password::broker('users')->reset([...$data, 'activo' => true], function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'password_establecida' => true,
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($user));
        });

        if ($userReset === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Contraseña establecida exitosamente. Ya puedes iniciar sesión.');
        }

        $clienteData = [
            'token' => $data['token'],
            'correo' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
            'activo' => true,
        ];

        $clienteReset = Password::broker('clientes')->reset($clienteData, function (Cliente $cliente, string $password) {
            $cliente->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($cliente));
        });

        if ($clienteReset === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Contraseña restablecida exitosamente. Ya puedes iniciar sesión.');
        }

        return back()->withErrors(['email' => 'El enlace para restablecer la contraseña no es válido o ha expirado. Solicita uno nuevo.']);
    }
}

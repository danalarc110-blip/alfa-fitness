<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class EstablecerPasswordController extends Controller
{
    public function show(Request $request, string $token)
    {
        return view('auth.establecer-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        $estado = Password::broker('users')->reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'password_establecida' => true, 'remember_token' => Str::random(60)])->save();
        });
        return $estado === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Contrasena establecida. Ya puedes iniciar sesion.')
            : back()->withErrors(['email' => __($estado)]);
    }
}

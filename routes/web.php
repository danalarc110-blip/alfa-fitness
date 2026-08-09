<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ClienteLoginController;
use App\Http\Controllers\ConfiguracionController;
use Illuminate\Support\Facades\Route;

// Página raíz: redirige al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Login (empleados)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/salir', [LoginController::class, 'salir'])->name('salir');

// Login (clientes)
Route::post('/cliente/login', [ClienteLoginController::class, 'login'])->name('cliente.login.submit');
Route::post('/cliente/registro', [ClienteLoginController::class, 'registrar'])->name('cliente.registro');
Route::post('/cliente/logout', [ClienteLoginController::class, 'logout'])->name('cliente.logout');
Route::post('/cliente/salir', [ClienteLoginController::class, 'salir'])->name('cliente.salir');

// Google (clientes)
Route::get('/cliente/google', [ClienteLoginController::class, 'redirectToGoogle'])->name('cliente.google');
Route::get('/cliente/google/callback', [ClienteLoginController::class, 'handleGoogleCallback'])->name('cliente.google.callback');

// Información del gimnasio (pública, no requiere login)
Route::get('/informacion', function () {
    return view('informacion');
})->name('informacion');

// Área protegida de empleados
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        return view('dashboard', [
            'guard' => 'web',
            'nombre' => $user->name,
            'rolEtiqueta' => $user->rol,
            'avatarUrl' => $user->avatar ? asset('images/avatars/'.$user->avatar) : null,
        ]);
    })->name('dashboard');
});

// Área protegida de clientes
Route::middleware('auth:cliente')->group(function () {
    Route::get('/cliente/dashboard', function () {
        return view('cliente.dashboard');
    })->name('cliente.dashboard');
});

// Configuración (empleados o clientes, cualquiera que esté logueado)
Route::middleware('auth:web,cliente')->group(function () {
    Route::get('/configuracion', [ConfiguracionController::class, 'show'])->name('configuracion');
    Route::post('/configuracion/perfil', [ConfiguracionController::class, 'actualizarPerfil'])->name('configuracion.perfil');
    Route::post('/configuracion/password', [ConfiguracionController::class, 'actualizarPassword'])->name('configuracion.password');
    Route::post('/configuracion/personalizacion', [ConfiguracionController::class, 'actualizarPersonalizacion'])->name('configuracion.personalizacion');
    Route::post('/configuracion/avatar', [ConfiguracionController::class, 'actualizarAvatar'])->name('configuracion.avatar');
});

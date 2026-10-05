<?php

use App\Http\Controllers\Gestion\ClientesController;
use App\Http\Controllers\Gestion\PlanesController;
use App\Http\Controllers\Gestion\SesionesController;
use App\Http\Controllers\Gestion\UsuariosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:cliente,web', 'auth.session'])->prefix('gestion')->name('gestion.')->group(function () {
    Route::resource('usuarios', UsuariosController::class)->except('show');
    Route::post('usuarios/{usuario}/invitar', [UsuariosController::class, 'invitar'])->middleware('throttle:5,1')->name('usuarios.invitar');
    Route::patch('usuarios/{usuario}/reactivar', [UsuariosController::class, 'reactivar'])->name('usuarios.reactivar');
    Route::resource('clientes', ClientesController::class)->except('show');
    Route::patch('clientes/{cliente}/reactivar', [ClientesController::class, 'reactivar'])->name('clientes.reactivar');
    Route::resource('planes', PlanesController::class)->parameters(['planes' => 'plan'])->except('show');
    Route::patch('planes/{plan}/reactivar', [PlanesController::class, 'reactivar'])->name('planes.reactivar');
    Route::resource('sesiones', SesionesController::class)->parameters(['sesiones' => 'sesion'])->except('show');
});

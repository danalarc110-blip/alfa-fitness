<?php

use App\Http\Controllers\Gestion\HistorialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:cliente,web', 'auth.session'])->prefix('gestion/historial')->name('gestion.historial.')->group(function () {
    Route::get('/ventas', [HistorialController::class, 'ventas'])->name('ventas.index');
    Route::get('/ventas/{venta}/editar', [HistorialController::class, 'editarVenta'])->whereNumber('venta')->name('ventas.edit');
    Route::put('/ventas/{venta}', [HistorialController::class, 'actualizarVenta'])->whereNumber('venta')->name('ventas.update');
    Route::delete('/ventas/{venta}', [HistorialController::class, 'anularVenta'])->whereNumber('venta')->name('ventas.anular');
    Route::get('/asistencias', [HistorialController::class, 'asistencias'])->name('asistencias.index');
    Route::get('/asistencias/{asistencia}/editar', [HistorialController::class, 'editarAsistencia'])->whereNumber('asistencia')->name('asistencias.edit');
    Route::put('/asistencias/{asistencia}', [HistorialController::class, 'actualizarAsistencia'])->whereNumber('asistencia')->name('asistencias.update');
    Route::delete('/asistencias/{asistencia}', [HistorialController::class, 'anularAsistencia'])->whereNumber('asistencia')->name('asistencias.anular');
});

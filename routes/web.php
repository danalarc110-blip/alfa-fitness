<?php

use App\Http\Controllers\AnaliticaFinancieraController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\Auth\ClienteLoginController;
use App\Http\Controllers\Auth\EstablecerPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuentasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\EntrenadorController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\PlanMembresiaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProgresoController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\VentaController;
use App\Models\PlanMembresia;
use Illuminate\Support\Facades\Route;

// Página raíz: redirige al login
Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/privacidad', 'privacidad')->name('privacidad');

// Login (empleados)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/salir', [LoginController::class, 'salir'])->name('salir');

// Login (clientes)
Route::post('/cliente/login', [ClienteLoginController::class, 'login'])->middleware('throttle:5,1')->name('cliente.login.submit');
Route::post('/cliente/registro', [ClienteLoginController::class, 'registrar'])->middleware('throttle:5,1')->name('cliente.registro');
// Recuperación de contraseña
Route::get('/olvide-mi-contrasena', [EstablecerPasswordController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/olvide-mi-contrasena', [EstablecerPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
Route::get('/establecer-contrasena/{token}', [EstablecerPasswordController::class, 'show'])->name('password.reset');
Route::post('/establecer-contrasena', [EstablecerPasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
Route::post('/cliente/logout', [ClienteLoginController::class, 'logout'])->name('cliente.logout');
Route::post('/cliente/salir', [ClienteLoginController::class, 'salir'])->name('cliente.salir');

// Google (clientes)
Route::get('/cliente/google', [ClienteLoginController::class, 'redirectToGoogle'])->middleware('throttle:20,1')->name('cliente.google');
Route::get('/cliente/google/callback', [ClienteLoginController::class, 'handleGoogleCallback'])->middleware('throttle:20,1')->name('cliente.google.callback');

// Información del gimnasio (pública, no requiere login)
Route::get('/informacion', function () {
    $planes = PlanMembresia::where('activo', true)->orderBy('precio')->get();

    return view('informacion', ['planes' => $planes]);
})->name('informacion');

// Área protegida de empleados
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'auth.session'])->name('dashboard');
Route::get('/cliente/dashboard', [DashboardController::class, 'index'])->middleware(['auth:cliente', 'auth.session'])->name('cliente.dashboard');
Route::get('/mi-asistencia', [AsistenciaController::class, 'miAsistencia'])->middleware(['auth:cliente', 'auth.session'])->name('cliente.asistencia');

Route::middleware(['auth:cliente,web', 'auth.session'])->group(function () {
    Route::get('/cuentas', [CuentasController::class, 'index'])->middleware('can:administrar')->name('cuentas.index');
    Route::patch('/cuentas/{cliente}/banear', [CuentasController::class, 'banear'])->middleware('can:administrar')->name('cuentas.banear');
    Route::patch('/cuentas/{cliente}/restaurar', [CuentasController::class, 'restaurar'])->middleware('can:administrar')->name('cuentas.restaurar');

    Route::post('/configuracion/apariencia', [ConfiguracionController::class, 'actualizarApariencia'])->name('configuracion.apariencia');
    Route::get('/configuracion', [ConfiguracionController::class, 'show'])->name('configuracion');
    Route::post('/configuracion/perfil', [ConfiguracionController::class, 'actualizarPerfil'])->name('configuracion.perfil');
    Route::post('/configuracion/password', [ConfiguracionController::class, 'actualizarPassword'])->middleware('throttle:5,1')->name('configuracion.password');
    Route::post('/configuracion/personalizacion', [ConfiguracionController::class, 'actualizarPersonalizacion'])->name('configuracion.personalizacion');
    Route::post('/configuracion/avatar', [ConfiguracionController::class, 'actualizarAvatar'])->name('configuracion.avatar');
    Route::post('/configuracion/google', [ConfiguracionController::class, 'vincularGoogle'])->middleware('throttle:5,1')->name('configuracion.google');

    Route::get('/membresias', [MembresiaController::class, 'index'])->middleware('can:membresias')->name('membresias.index');
    Route::post('/membresias/planes', [PlanMembresiaController::class, 'store'])->middleware('can:administrar')->name('planes.store');
    Route::put('/membresias/planes/{plan}', [PlanMembresiaController::class, 'update'])->middleware('can:administrar')->name('planes.update');
    Route::patch('/membresias/planes/{plan}/toggle', [PlanMembresiaController::class, 'toggle'])->middleware('can:administrar')->name('planes.toggle');
    Route::post('/membresias/solicitudes', [MembresiaController::class, 'solicitar'])->middleware('can:progreso')->name('membresias.solicitar');
    Route::patch('/membresias/solicitudes/{solicitud}/activar', [MembresiaController::class, 'activar'])->middleware(['can:operaciones', 'throttle:operaciones-financieras'])->name('membresias.activar');
    Route::patch('/membresias/solicitudes/{solicitud}/cancelar', [MembresiaController::class, 'cancelarSolicitud'])->name('membresias.solicitudes.cancelar');
    Route::patch('/membresias/{membresia}/cancelar', [MembresiaController::class, 'cancelar'])->middleware('can:operaciones')->name('membresias.cancelar');
    Route::post('/membresias/{membresia}/pausa', [MembresiaController::class, 'solicitarPausa'])->name('membresias.pausa.solicitar');
    Route::patch('/membresias/pausas/{pausa}/aprobar', [MembresiaController::class, 'aprobarPausa'])->middleware('can:operaciones')->name('membresias.pausa.aprobar');
    Route::patch('/membresias/pausas/{pausa}/rechazar', [MembresiaController::class, 'rechazarPausa'])->middleware('can:operaciones')->name('membresias.pausa.rechazar');
    Route::patch('/membresias/{membresia}/reanudar', [MembresiaController::class, 'reanudar'])->name('membresias.reanudar');

    Route::get('/asistencia', [AsistenciaController::class, 'index'])->middleware('can:asistencia')->name('asistencia.index');
    Route::get('/asistencia/exportar', [AsistenciaController::class, 'exportar'])->middleware(['can:asistencia', 'throttle:exportaciones'])->name('asistencia.exportar');
    Route::post('/asistencia/cerrar-huerfanas', [AsistenciaController::class, 'cerrarHuerfanas'])->middleware('can:asistencia')->name('asistencia.cerrar-huerfanas');
    Route::post('/asistencia', [AsistenciaController::class, 'store'])->middleware('can:asistencia')->name('asistencia.store');
    Route::post('/asistencia/salida', [AsistenciaController::class, 'salida'])->middleware('can:asistencia')->name('asistencia.salida');

    Route::get('/entrenadores', [EntrenadorController::class, 'index'])->name('entrenadores.index');
    Route::post('/entrenadores', [EntrenadorController::class, 'store'])->middleware(['can:administrar', 'throttle:exportaciones'])->name('entrenadores.store');
    Route::put('/entrenadores/{entrenador}', [EntrenadorController::class, 'update'])->middleware('can:administrar')->name('entrenadores.update');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::post('/productos', [ProductoController::class, 'store'])->middleware('can:inventario')->name('productos.store');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])->middleware('can:inventario')->name('productos.update');
    Route::get('/ventas', [VentaController::class, 'index'])->middleware('can:inventario')->name('ventas.index');
    Route::post('/ventas', [VentaController::class, 'store'])->middleware(['can:inventario', 'throttle:operaciones-financieras'])->name('ventas.store');
    Route::get('/ventas/{venta}/comprobante', [VentaController::class, 'comprobante'])->middleware('can:inventario')->name('ventas.comprobante');
    Route::post('/ventas/{venta}/enviar-correo', [VentaController::class, 'enviarCorreo'])->middleware(['can:inventario', 'throttle:exportaciones'])->name('ventas.enviar-correo');

    Route::get('/analitica', [AnaliticaFinancieraController::class, 'index'])->middleware('can:analitica_financiera')->name('analitica.index');
    Route::get('/analitica/pdf', [AnaliticaFinancieraController::class, 'exportarPdf'])->middleware(['can:analitica_financiera', 'throttle:exportaciones'])->name('analitica.pdf');
    Route::get('/analitica/excel', [AnaliticaFinancieraController::class, 'exportarExcel'])->middleware(['can:analitica_financiera', 'throttle:exportaciones'])->name('analitica.excel');

    Route::get('/progreso', [ProgresoController::class, 'index'])->middleware('can:progreso')->name('progreso.index');
    Route::post('/progreso', [ProgresoController::class, 'store'])->middleware('can:progreso')->name('progreso.store');
    Route::delete('/progreso/{personalRecord}', [ProgresoController::class, 'destroy'])->middleware('can:progreso')->name('progreso.destroy');
    Route::get('/entrenador/clientes/{cliente}/progreso', [ProgresoController::class, 'clienteProgreso'])->name('entrenador.cliente.progreso');

    Route::get('/rankings', fn () => redirect()->route('progreso.index'))->middleware('can:progreso')->name('rankings.index');
    Route::get('/estadisticas', fn () => redirect()->route('progreso.index'))->middleware('can:progreso')->name('estadisticas.index');
});

// Entrenamientos (empleados o clientes, cualquiera que esté logueado)
Route::middleware(['auth:cliente,web', 'auth.session'])->prefix('entrenamientos')->name('entrenamientos.')->group(function () {
    Route::get('/', [RutinaController::class, 'index'])->name('index');
    Route::get('/historial', [RutinaController::class, 'historial'])->name('historial');
    Route::post('/crear', [RutinaController::class, 'crear'])->name('crear');
    Route::get('/catalogo/buscar', [RutinaController::class, 'buscarEjercicios'])->name('catalogo.buscar');
    Route::get('/{rutina}', [RutinaController::class, 'editar'])->name('editar');
    Route::put('/{rutina}', [RutinaController::class, 'actualizar'])->name('actualizar');
    Route::delete('/{rutina}', [RutinaController::class, 'eliminar'])->name('eliminar');
    Route::post('/{rutina}/asignar', [RutinaController::class, 'asignar'])->name('asignar');
    Route::get('/{rutina}/entrenar/{dia?}', [RutinaController::class, 'entrenar'])->name('entrenar');
    Route::post('/{rutina}/finalizar', [RutinaController::class, 'finalizarSesion'])->name('finalizar');
    Route::get('/{rutina}/imprimir', [RutinaController::class, 'imprimir'])->name('imprimir');

    // Días
    Route::post('/{rutina}/dias', [RutinaController::class, 'agregarDia'])->name('dias.crear');
    Route::put('/dias/{dia}', [RutinaController::class, 'renombrarDia'])->name('dias.renombrar');
    Route::delete('/dias/{dia}', [RutinaController::class, 'eliminarDia'])->name('dias.eliminar');

    // Catálogo de ejercicios (buscador panel derecho)

    // Ejercicios dentro de un día
    Route::post('/dias/{dia}/ejercicios', [RutinaController::class, 'agregarEjercicio'])->name('ejercicios.crear');
    Route::put('/dias/{dia}/ejercicios/orden', [RutinaController::class, 'reordenarEjercicios'])->name('ejercicios.reordenar');
    Route::put('/ejercicios/{rutinaEjercicio}', [RutinaController::class, 'actualizarEjercicio'])->name('ejercicios.actualizar');
    Route::delete('/ejercicios/{rutinaEjercicio}', [RutinaController::class, 'eliminarEjercicio'])->name('ejercicios.eliminar');
});

// Ejercicios (módulo de populares de la semana y calificaciones en estrellas)
Route::middleware(['auth:cliente,web', 'auth.session'])->prefix('ejercicios')->name('ejercicios.')->group(function () {
    Route::get('/', [EjercicioController::class, 'index'])->name('index');
    Route::post('/{ejercicio}/calificar', [EjercicioController::class, 'calificar'])->name('calificar');
    Route::post('/', [EjercicioController::class, 'store'])->middleware('can:administrar')->name('store');
    Route::put('/{ejercicio}', [EjercicioController::class, 'update'])->middleware('can:administrar')->name('update');
    Route::patch('/{ejercicio}/toggle', [EjercicioController::class, 'toggle'])->middleware('can:administrar')->name('toggle');
});

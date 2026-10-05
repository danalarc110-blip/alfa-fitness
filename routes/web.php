<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\Auth\ClienteLoginController;
use App\Http\Controllers\Auth\DosFactoresController;
use App\Http\Controllers\Auth\EstablecerPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CuentasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EjercicioController;
use App\Http\Controllers\EntrenadorController;
use App\Http\Controllers\EstadisticaEjercicioController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\PlanMembresiaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProgresoController;
use App\Http\Controllers\RutinaController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

// Página raíz: redirige al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Login (empleados)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/salir', [LoginController::class, 'salir'])->name('salir');
Route::get('/verificar-acceso', [DosFactoresController::class, 'desafio'])->name('dos-factores.desafio');
Route::post('/verificar-acceso', [DosFactoresController::class, 'verificar'])->middleware('throttle:5,1')->name('dos-factores.verificar');
Route::middleware(['auth:cliente,web', 'auth.session', 'can:administrar'])->prefix('administracion/dos-factores')->name('dos-factores.')->group(function () {
    Route::get('/', [DosFactoresController::class, 'configurar'])->name('configurar');
    Route::post('/preparar', [DosFactoresController::class, 'preparar'])->middleware('throttle:5,1')->name('preparar');
    Route::post('/activar', [DosFactoresController::class, 'activar'])->middleware('throttle:5,1')->name('activar');
    Route::delete('/', [DosFactoresController::class, 'desactivar'])->middleware('throttle:5,1')->name('desactivar');
});

// Login (clientes)
Route::post('/cliente/login', [ClienteLoginController::class, 'login'])->middleware('throttle:5,1')->name('cliente.login.submit');
Route::post('/cliente/registro', [ClienteLoginController::class, 'registrar'])->middleware('throttle:5,1')->name('cliente.registro');
Route::get('/establecer-contrasena/{token}', [EstablecerPasswordController::class, 'show'])->name('password.reset');
Route::post('/establecer-contrasena', [EstablecerPasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
Route::post('/cliente/logout', [ClienteLoginController::class, 'logout'])->name('cliente.logout');
Route::post('/cliente/salir', [ClienteLoginController::class, 'salir'])->name('cliente.salir');

// Google (clientes)
Route::get('/cliente/google', [ClienteLoginController::class, 'redirectToGoogle'])->name('cliente.google');
Route::get('/cliente/google/callback', [ClienteLoginController::class, 'handleGoogleCallback'])->name('cliente.google.callback');
Route::get('/cliente/google/consentimiento', [ClienteLoginController::class, 'consentimientoGoogle'])->name('cliente.google.consentimiento');
Route::post('/cliente/google/consentimiento', [ClienteLoginController::class, 'aceptarGoogle'])->middleware('throttle:5,1')->name('cliente.google.aceptar');
Route::get('/legal/{documento}', [LegalController::class, 'documento'])->name('legal.documento');
Route::post('/legal/solicitudes', [LegalController::class, 'guardarSolicitud'])->middleware('throttle:5,1')->name('legal.solicitud');
Route::middleware(['auth:cliente', 'auth.session'])->group(function () {
    Route::get('/cliente/aceptar-documentos', [LegalController::class, 'mostrarAceptacion'])->name('cliente.legal.mostrar');
    Route::post('/cliente/aceptar-documentos', [LegalController::class, 'aceptarCliente'])->middleware('throttle:5,1')->name('cliente.legal.aceptar');
});
Route::middleware(['auth:cliente,web', 'auth.session', 'can:administrar'])->group(function () {
    Route::get('/administracion/solicitudes-datos', [LegalController::class, 'solicitudes'])->name('legal.solicitudes');
    Route::patch('/administracion/solicitudes-datos/{solicitud}', [LegalController::class, 'resolver'])->name('legal.resolver');
});

// Información del gimnasio (pública, no requiere login)
Route::get('/informacion', function () {
    return view('informacion');
})->name('informacion');

// Área protegida de empleados
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth:web', 'auth.session'])->name('dashboard');
Route::get('/cliente/dashboard', [DashboardController::class, 'index'])->middleware(['auth:cliente', 'auth.session'])->name('cliente.dashboard');

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
    Route::patch('/membresias/solicitudes/{solicitud}/activar', [MembresiaController::class, 'activar'])->middleware('can:operaciones')->name('membresias.activar');
    Route::patch('/membresias/solicitudes/{solicitud}/cancelar', [MembresiaController::class, 'cancelarSolicitud'])->name('membresias.solicitudes.cancelar');
    Route::patch('/membresias/{membresia}/cancelar', [MembresiaController::class, 'cancelar'])->middleware('can:operaciones')->name('membresias.cancelar');
    Route::post('/membresias/{membresia}/pausa', [MembresiaController::class, 'solicitarPausa'])->name('membresias.pausa.solicitar');
    Route::patch('/membresias/pausas/{pausa}/aprobar', [MembresiaController::class, 'aprobarPausa'])->middleware('can:operaciones')->name('membresias.pausa.aprobar');
    Route::patch('/membresias/pausas/{pausa}/rechazar', [MembresiaController::class, 'rechazarPausa'])->middleware('can:operaciones')->name('membresias.pausa.rechazar');
    Route::patch('/membresias/{membresia}/reanudar', [MembresiaController::class, 'reanudar'])->name('membresias.reanudar');

    Route::get('/asistencia', [AsistenciaController::class, 'index'])->middleware('can:asistencia')->name('asistencia.index');
    Route::get('/asistencia/exportar', [AsistenciaController::class, 'exportar'])->middleware('can:asistencia')->name('asistencia.exportar');
    Route::post('/asistencia/cerrar-huerfanas', [AsistenciaController::class, 'cerrarHuerfanas'])->middleware('can:asistencia')->name('asistencia.cerrar-huerfanas');
    Route::post('/asistencia', [AsistenciaController::class, 'store'])->middleware('can:asistencia')->name('asistencia.store');
    Route::post('/asistencia/salida', [AsistenciaController::class, 'salida'])->middleware('can:asistencia')->name('asistencia.salida');

    Route::get('/entrenadores', [EntrenadorController::class, 'index'])->name('entrenadores.index');
    Route::post('/entrenadores', [EntrenadorController::class, 'store'])->middleware('can:administrar')->name('entrenadores.store');
    Route::put('/entrenadores/{entrenador}', [EntrenadorController::class, 'update'])->middleware('can:administrar')->name('entrenadores.update');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::post('/productos', [ProductoController::class, 'store'])->middleware('can:inventario')->name('productos.store');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])->middleware('can:inventario')->name('productos.update');
    Route::get('/ventas', [VentaController::class, 'index'])->middleware('can:inventario')->name('ventas.index');
    Route::post('/ventas', [VentaController::class, 'store'])->middleware('can:inventario')->name('ventas.store');
    Route::get('/ventas/{venta}/comprobante', [VentaController::class, 'comprobante'])->middleware('can:inventario')->name('ventas.comprobante');
    Route::post('/ventas/{venta}/enviar-correo', [VentaController::class, 'enviarCorreo'])->middleware('can:inventario')->name('ventas.enviar-correo');

    Route::get('/progreso', [ProgresoController::class, 'index'])->middleware('can:progreso')->name('progreso.index');
    Route::get('/progreso/estadisticas', [EstadisticaEjercicioController::class, 'index'])->middleware('can:progreso')->name('progreso.estadisticas');
    Route::post('/progreso', [ProgresoController::class, 'store'])->middleware('can:progreso')->name('progreso.store');
    Route::delete('/progreso/{personalRecord}', [ProgresoController::class, 'destroy'])->middleware('can:progreso')->name('progreso.destroy');

    Route::get('/rankings', fn () => redirect()->route('progreso.index'))->middleware('can:progreso')->name('rankings.index');
    Route::get('/estadisticas', fn () => redirect()->route('progreso.index'))->middleware('can:progreso')->name('estadisticas.index');
});

// Entrenamientos (empleados o clientes, cualquiera que esté logueado)
Route::middleware(['auth:cliente,web', 'auth.session'])->prefix('entrenamientos')->name('entrenamientos.')->group(function () {
    Route::get('/', [RutinaController::class, 'index'])->name('index');
    Route::post('/crear', [RutinaController::class, 'crear'])->name('crear');
    Route::get('/catalogo/buscar', [RutinaController::class, 'buscarEjercicios'])->name('catalogo.buscar');
    Route::get('/{rutina}', [RutinaController::class, 'editar'])->name('editar');
    Route::put('/{rutina}', [RutinaController::class, 'actualizar'])->name('actualizar');
    Route::delete('/{rutina}', [RutinaController::class, 'eliminar'])->name('eliminar');
    Route::post('/{rutina}/asignar', [RutinaController::class, 'asignar'])->name('asignar');
    Route::get('/{rutina}/entrenar/{dia?}', [RutinaController::class, 'entrenar'])->name('entrenar');
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

require __DIR__.'/gestion.php';
require __DIR__.'/historial.php';

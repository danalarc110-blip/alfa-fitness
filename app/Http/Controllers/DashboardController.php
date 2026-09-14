<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\PersonalRecord;
use App\Models\Rutina;
use App\Models\SolicitudMembresia;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $guard = request()->routeIs('cliente.dashboard') ? 'cliente' : 'web';
        $user = auth($guard)->user();
        $nombre = $guard === 'web' ? $user->name : $user->nombre;
        $perfil = $guard === 'cliente' ? 'cliente' : match ($user->rol) {
            'Administrador' => 'administrador', 'Secretaria' => 'recepcion', default => 'entrenador',
        };
        $titulo = ['cliente' => 'Mi entrenamiento', 'administrador' => 'Administracion del gimnasio', 'recepcion' => 'Secretaria · Hoy', 'entrenador' => 'Espacio de entrenamiento'][$perfil];
        $acciones = match ($perfil) {
            'administrador' => [['cuentas.index', 'Gestionar cuentas'], ['productos.index', 'Gestionar productos']],
            'recepcion' => [['asistencia.index', 'Registrar entrada o salida'], ['membresias.index', 'Cobros y membresias']],
            'entrenador' => [['entrenamientos.index', 'Mis rutinas'], ['ejercicios.index', 'Ejercicios']],
            default => [['entrenamientos.index', 'Mis entrenamientos'], ['progreso.index', 'Progreso y marcas personales']],
        };
        $rutinas = Rutina::deUsuario($guard, $user->id)->withCount('dias')->latest()->limit(4)->get();
        $actividad = collect();
        $porVencer = collect();

        if ($perfil === 'cliente') {
            $visitas = Asistencia::where('cliente_id', $user->id);
            $membresias = Membresia::with('cliente:id,nombre')->where('cliente_id', $user->id)->where('cancelada', false)->where('inicio', '<=', today())->where('fin', '>=', today());
            $metricas = [['Mis rutinas', Rutina::deUsuario($guard, $user->id)->count()], ['Mis visitas este mes', (clone $visitas)->whereBetween('fecha_hora', [now()->startOfMonth(), now()->endOfMonth()])->count()], ['Membresias vigentes', $membresias->count()], ['Mis marcas', PersonalRecord::where('cliente_id', $user->id)->count()]];
            $actividad = $visitas->latest('fecha_hora')->limit(5)->get();
            $porVencer = $membresias->where('fin', '<=', today()->addDays(7))->limit(5)->get();
        } elseif ($perfil === 'recepcion') {
            $metricas = [['Entradas hoy', Asistencia::whereBetween('fecha_hora', [today(), today()->endOfDay()])->count()], ['Dentro ahora', Asistencia::whereNull('fecha_salida')->count()], ['Solicitudes pendientes', SolicitudMembresia::where('estado', 'pendiente')->count()]];
            $actividad = Asistencia::with('cliente:id,nombre')->latest('fecha_hora')->limit(5)->get();
            $porVencer = Membresia::with('cliente:id,nombre')->where('cancelada', false)->whereBetween('fin', [today(), today()->addDays(7)])->limit(5)->get();
        } elseif ($perfil === 'administrador') {
            $metricas = [['Clientes activos', Cliente::where('activo', true)->count()], ['Clientes inactivos', Cliente::where('activo', false)->count()], ['Personal activo', User::where('activo', true)->count()]];
        } else {
            $metricas = [['Mis rutinas', Rutina::deUsuario($guard, $user->id)->count()], ['Ejercicios disponibles', Ejercicio::where('activo', true)->count()]];
        }

        return view('home', compact('guard', 'nombre', 'rutinas', 'metricas', 'actividad', 'perfil', 'titulo', 'acciones', 'porVencer'));
    }
}

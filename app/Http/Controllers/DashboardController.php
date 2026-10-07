<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PersonalRecord;
use App\Models\Rutina;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;

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
        $titulo = ['cliente' => 'Mi entrenamiento', 'administrador' => 'Administración del gimnasio', 'recepcion' => 'Secretaria · Hoy', 'entrenador' => 'Espacio de entrenamiento'][$perfil];
        $acciones = match ($perfil) {
            'administrador' => [['cuentas.index', 'Gestionar cuentas'], ['productos.index', 'Gestionar productos'], ['ventas.index', 'Punto de Venta (TPV)'], ['analitica.index', 'Analítica']],
            'recepcion' => [['asistencia.index', 'Registrar entrada o salida'], ['membresias.index', 'Cobros y membresías'], ['ventas.index', 'Ventas mostrador']],
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
            $ingresosMembresiasMes = (float) PagoMembresia::whereBetween('pagado_en', [now()->startOfMonth(), now()->endOfMonth()])->sum('importe');
            if ($ingresosMembresiasMes <= 0) {
                $ingresosMembresiasMes = (float) Membresia::where('cancelada', false)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('importe');
            }
            $ingresosVentasMes = (float) Venta::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total');
            $totalIngresosMes = $ingresosMembresiasMes + $ingresosVentasMes;

            $metricas = [
                ['Clientes activos', Cliente::where('activo', true)->count()],
                ['Ingresos este mes', '$'.number_format($totalIngresosMes, 2)],
                ['Membresías cobradas', '$'.number_format($ingresosMembresiasMes, 2)],
                ['Ventas mostrador', '$'.number_format($ingresosVentasMes, 2)],
                ['Personal activo', User::where('activo', true)->count()],
            ];
            $actividad = Venta::with(['cliente:id,nombre', 'user:id,name'])->latest()->limit(5)->get();
            $porVencer = Membresia::with('cliente:id,nombre')->where('cancelada', false)->whereBetween('fin', [today(), today()->addDays(7)])->limit(5)->get();
        } else {
            $metricas = [
                ['Mis rutinas', Rutina::deUsuario($guard, $user->id)->count()],
                ['Ejercicios disponibles', Ejercicio::where('activo', true)->count()],
                ['Total entrenadores', User::where('rol', 'Entrenador')->where('activo', true)->count()],
            ];
            $actividad = Ejercicio::where('activo', true)
                ->withAvg('calificaciones as promedio_estrellas', 'estrellas')
                ->withCount('calificaciones as conteo_votos')
                ->orderByDesc('promedio_estrellas')
                ->orderByDesc('conteo_votos')
                ->limit(5)
                ->get();
        }

        $capacidadMaxima = 80;
        $dentroAhora = Asistencia::whereNull('fecha_salida')->where('fecha_hora', '>=', now()->subHours(12))->count();
        $porcentajeAforo = min(100, (int) round(($dentroAhora / $capacidadMaxima) * 100));
        $estadoAforo = match (true) {
            $porcentajeAforo >= 85 => ['etiqueta' => 'Afluencia Alta', 'color' => 'text-red-400', 'bg' => 'bg-red-500', 'badge' => 'bg-red-500/10 text-red-400 border-red-500/20', 'bar' => 'bg-red-500'],
            $porcentajeAforo >= 50 => ['etiqueta' => 'Afluencia Moderada', 'color' => 'text-amber-400', 'bg' => 'bg-amber-400', 'badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'bar' => 'bg-amber-400'],
            default => ['etiqueta' => 'Capacidad Óptima', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-400', 'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'bar' => 'bg-emerald-400'],
        };

        $aforo = [
            'actual' => $dentroAhora,
            'capacidad' => $capacidadMaxima,
            'porcentaje' => $porcentajeAforo,
            'estado' => $estadoAforo['etiqueta'],
            'color' => $estadoAforo['color'],
            'bg' => $estadoAforo['bg'],
            'badge' => $estadoAforo['badge'],
            'bar' => $estadoAforo['bar'],
        ];

        $asistencias30d = Asistencia::where('fecha_hora', '>=', now()->subDays(30))->pluck('fecha_hora');
        $horasDistribucion = [];
        for ($h = 6; $h <= 22; $h++) {
            $horasDistribucion[$h] = 0;
        }
        foreach ($asistencias30d as $fh) {
            $hora = (int) Carbon::parse($fh)->format('G');
            if (isset($horasDistribucion[$hora])) {
                $horasDistribucion[$hora]++;
            }
        }
        $maxVisitasHora = max(1, ...array_values($horasDistribucion));
        $horasPico = [];
        foreach ($horasDistribucion as $hora => $total) {
            $horasPico[] = [
                'hora' => sprintf('%02d:00', $hora),
                'hora_corta' => sprintf('%02d', $hora),
                'total' => $total,
                'porcentaje' => round(($total / $maxVisitasHora) * 100),
                'es_pico' => $total === $maxVisitasHora && $total > 0,
            ];
        }

        return view('home', compact('guard', 'nombre', 'rutinas', 'metricas', 'actividad', 'perfil', 'titulo', 'acciones', 'porVencer', 'aforo', 'horasPico'));
    }
}

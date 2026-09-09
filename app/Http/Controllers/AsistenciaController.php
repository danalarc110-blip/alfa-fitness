<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'estado' => ['nullable', Rule::in(['abierta', 'cerrada'])],
        ]);
        $busqueda = $filtros['q'] ?? '';
        $clientes = Cliente::where('activo', true)
            ->when($busqueda, fn ($q) => $q->where(fn ($s) => $s->where('nombre', 'like', "%{$busqueda}%")->orWhere('correo', 'like', "%{$busqueda}%")))
            ->with(['asistencias' => fn ($q) => $q->whereNull('fecha_salida')->latest('fecha_hora'),
                'membresias' => fn ($q) => $q->where('cancelada', false)->latest('fin')->limit(1)])
            ->orderBy('nombre')->paginate(12, ['*'], 'clientes_page')->withQueryString();

        $asistencias = Asistencia::with(['cliente:id,nombre,correo', 'registrador:id,name', 'registradorSalida:id,name'])
            ->when($filtros['cliente_id'] ?? null, fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($busqueda, fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$busqueda}%")->orWhere('correo', 'like', "%{$busqueda}%")))
            ->when($filtros['desde'] ?? null, fn ($q, $fecha) => $q->where('fecha_hora', '>=', $fecha.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($q, $fecha) => $q->where('fecha_hora', '<=', $fecha.' 23:59:59'))
            ->when(($filtros['estado'] ?? '') === 'abierta', fn ($q) => $q->whereNull('fecha_salida'))
            ->when(($filtros['estado'] ?? '') === 'cerrada', fn ($q) => $q->whereNotNull('fecha_salida'))
            ->latest('fecha_hora')->paginate(20, ['*'], 'historial_page')->withQueryString();

        $dentroAhora = Asistencia::with('cliente:id,nombre,correo')->whereNull('fecha_salida')->oldest('fecha_hora')->limit(100)->get();
        $hoy = Asistencia::whereBetween('fecha_hora', [today(), today()->endOfDay()])->count();
        $dentro = Asistencia::whereNull('fecha_salida')->count();

        return view('asistencia.index', compact('clientes', 'busqueda', 'asistencias', 'hoy', 'dentro', 'dentroAhora', 'filtros'));
    }

    public function store(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);
        $data = $request->validate(['cliente_id' => ['required', 'exists:clientes,id']]);
        app(\App\Services\RegistroAsistencia::class)->registrar((int) $data['cliente_id'], auth('web')->id(), false);
        return back()->with('status', 'Entrada registrada.');
    }

    public function salida(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);
        $data = $request->validate(['cliente_id' => ['required', 'exists:clientes,id']]);
        app(\App\Services\RegistroAsistencia::class)->registrar((int) $data['cliente_id'], auth('web')->id(), true);
        return back()->with('status', 'Salida registrada.');
    }
}

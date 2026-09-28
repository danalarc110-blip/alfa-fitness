<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Services\RegistroAsistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'estado' => ['nullable', Rule::in(['abierta', 'cerrada'])],
        ]);
        $busqueda = $filtros['q'] ?? '';
        $clientes = Cliente::where('activo', true)
            ->when($busqueda, fn ($q) => $q->where(fn ($s) => $s->where('nombre', 'like', "%{$busqueda}%")->orWhere('correo', 'like', "%{$busqueda}%")))
            ->with(['asistencias' => fn ($q) => $q->whereNull('fecha_salida')->latest('fecha_hora'),
                'ultimaMembresia'])
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
        $huerfanasCount = Asistencia::whereNull('fecha_salida')->where('fecha_hora', '<', now()->subHours(12))->count();

        return view('asistencia.index', compact('clientes', 'busqueda', 'asistencias', 'hoy', 'dentro', 'dentroAhora', 'filtros', 'huerfanasCount'));
    }

    public function store(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);
        $data = $request->validate(['cliente_id' => ['required', 'exists:clientes,id']]);
        app(RegistroAsistencia::class)->registrar((int) $data['cliente_id'], auth('web')->id(), false);

        return back()->with('status', 'Entrada registrada.');
    }

    public function salida(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);
        $data = $request->validate(['cliente_id' => ['required', 'exists:clientes,id']]);
        app(RegistroAsistencia::class)->registrar((int) $data['cliente_id'], auth('web')->id(), true);

        return back()->with('status', 'Salida registrada.');
    }

    public function exportar(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', Rule::in(['abierta', 'cerrada'])],
        ]);
        $busqueda = $filtros['q'] ?? '';

        $asistencias = Asistencia::with(['cliente:id,nombre,correo', 'registrador:id,name', 'registradorSalida:id,name'])
            ->when($filtros['cliente_id'] ?? null, fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($busqueda, fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$busqueda}%")->orWhere('correo', 'like', "%{$busqueda}%")))
            ->when($filtros['desde'] ?? null, fn ($q, $fecha) => $q->where('fecha_hora', '>=', $fecha.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($q, $fecha) => $q->where('fecha_hora', '<=', $fecha.' 23:59:59'))
            ->when(($filtros['estado'] ?? '') === 'abierta', fn ($q) => $q->whereNull('fecha_salida'))
            ->when(($filtros['estado'] ?? '') === 'cerrada', fn ($q) => $q->whereNotNull('fecha_salida'))
            ->latest('fecha_hora')
            ->get();

        $filename = 'asistencias_'.now()->format('Y-m-d_His').'.csv';

        $callback = function () use ($asistencias) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID', 'Cliente', 'Correo', 'Fecha Entrada', 'Fecha Salida', 'Duración', 'Registrado Por', 'Salida Por']);

            foreach ($asistencias as $a) {
                fputcsv($handle, [
                    $a->id,
                    $a->cliente->nombre ?? 'N/A',
                    $a->cliente->correo ?? 'N/A',
                    $a->fecha_hora->format('Y-m-d H:i:s'),
                    $a->fecha_salida ? $a->fecha_salida->format('Y-m-d H:i:s') : 'En curso',
                    $a->duracion ?? 'En gimnasio',
                    $a->registrador->name ?? 'Sistema',
                    $a->registradorSalida->name ?? ($a->fecha_salida ? 'Sistema' : ''),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function cerrarHuerfanas(Request $request)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);

        $cerradas = 0;
        DB::transaction(function () use (&$cerradas) {
            $huerfanas = Asistencia::whereNull('fecha_salida')
                ->where('fecha_hora', '<', now()->subHours(12))
                ->lockForUpdate()
                ->get();

            foreach ($huerfanas as $asistencia) {
                $asistencia->update([
                    'fecha_salida' => $asistencia->fecha_hora->copy()->addHours(2),
                    'salida_registrada_por' => auth('web')->id(),
                ]);
                $cerradas++;
            }
        });

        Cache::forget('aforo_en_vivo');

        return back()->with('status', $cerradas > 0
            ? "Se cerraron exitosamente {$cerradas} visitas huérfanas de días anteriores."
            : 'No se encontraron visitas huérfanas pendientes.');
    }
}

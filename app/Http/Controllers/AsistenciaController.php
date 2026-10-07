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
        abort_unless(auth('web')->user()?->can('asistencia'), 403);
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
        abort_unless(auth('web')->user()?->can('asistencia'), 403);
        $data = $request->validate(['cliente_id' => ['required', 'exists:clientes,id']]);
        app(RegistroAsistencia::class)->registrar((int) $data['cliente_id'], auth('web')->id(), false);

        return back()->with('status', 'Entrada registrada.');
    }

    public function salida(Request $request)
    {
        abort_unless(auth('web')->user()?->can('asistencia'), 403);
        $data = $request->validate(['cliente_id' => ['required', 'exists:clientes,id']]);
        app(RegistroAsistencia::class)->registrar((int) $data['cliente_id'], auth('web')->id(), true);

        return back()->with('status', 'Salida registrada.');
    }

    public function exportar(Request $request)
    {
        abort_unless(auth('web')->user()?->can('asistencia'), 403);
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'estado' => ['nullable', Rule::in(['abierta', 'cerrada'])],
        ]);
        $busqueda = $filtros['q'] ?? '';

        $asistencias = Asistencia::with(['cliente:id,nombre', 'registrador:id,name', 'registradorSalida:id,name'])
            ->when($filtros['cliente_id'] ?? null, fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($busqueda, fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$busqueda}%")->orWhere('correo', 'like', "%{$busqueda}%")))
            ->when($filtros['desde'] ?? null, fn ($q, $fecha) => $q->where('fecha_hora', '>=', $fecha.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($q, $fecha) => $q->where('fecha_hora', '<=', $fecha.' 23:59:59'))
            ->when(($filtros['estado'] ?? '') === 'abierta', fn ($q) => $q->whereNull('fecha_salida'))
            ->when(($filtros['estado'] ?? '') === 'cerrada', fn ($q) => $q->whereNotNull('fecha_salida'))
            ->latest('fecha_hora')
            ->orderByDesc('id');

        $filename = 'asistencias_'.now()->format('Y-m-d_His').'.csv';

        $callback = function () use ($asistencias) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID', 'Cliente', 'Fecha Entrada', 'Fecha Salida', 'Duración', 'Registrado Por', 'Salida Por'], ',', '"', '');

            // Eager-load relations per bounded batch without materializing the full history.
            foreach ($asistencias->lazy(500) as $a) {
                $fila = [
                    $a->id,
                    $a->cliente->nombre ?? 'N/A',
                    $a->fecha_hora->format('Y-m-d H:i:s'),
                    $a->fecha_salida ? $a->fecha_salida->format('Y-m-d H:i:s') : 'En curso',
                    $a->duracion ?? 'En gimnasio',
                    $a->registrador->name ?? 'Sistema',
                    $a->registradorSalida->name ?? ($a->fecha_salida ? 'Sistema' : ''),
                ];
                // CSV quoting alone does not prevent spreadsheet formula execution.
                $fila = array_map(static function ($valor) {
                    if (is_string($valor) && preg_match('/^[\x00-\x20]*[=+\-@]|^[\t\r\n]/u', $valor)) {
                        return "'".$valor;
                    }

                    return $valor;
                }, $fila);
                fputcsv($handle, $fila, ',', '"', '');
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
        abort_unless(auth('web')->user()?->can('asistencia'), 403);

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

    /**
     * Consulta el historial privado de asistencia del cliente autenticado.
     */
    public function miAsistencia(Request $request)
    {
        abort_unless(auth('cliente')->check(), 403);
        $cliente = auth('cliente')->user();

        $asistencias = Asistencia::where('cliente_id', $cliente->id)
            ->latest('fecha_hora')
            ->paginate(15);

        $totalVisitas = Asistencia::where('cliente_id', $cliente->id)->count();
        $visitaActual = Asistencia::where('cliente_id', $cliente->id)
            ->whereNull('fecha_salida')
            ->latest('fecha_hora')
            ->first();

        return view('cliente.asistencia', compact('cliente', 'asistencias', 'totalVisitas', 'visitaActual'));
    }
}

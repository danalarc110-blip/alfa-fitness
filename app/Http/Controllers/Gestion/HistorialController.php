<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\User;
use App\Models\Venta;
use App\Rules\SinDatosTarjeta;
use App\Services\CorreccionHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HistorialController extends Controller
{
    private function acceso(bool $soloAdmin = false): User
    {
        $actor = auth('web')->user();
        abort_unless(! auth('cliente')->check() && $actor?->activo && in_array($actor->rol, $soloAdmin ? ['Administrador'] : ['Administrador', 'Secretaria'], true), 403);

        return $actor;
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['vigente', 'anulada'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
        ]);
    }

    public function ventas(Request $request)
    {
        $this->acceso();
        $filtros = $this->filtros($request);
        $registros = Venta::withoutGlobalScope('vigentes')->with(['cliente:id,nombre', 'user:id,name'])
            ->when($filtros['q'] ?? null, fn ($query, $q) => $query->where(fn ($query) => $query->where('notas', 'like', "%{$q}%")->orWhereHas('cliente', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$q}%"))))
            ->when(($filtros['estado'] ?? '') === 'vigente', fn ($query) => $query->whereNull('anulada_en'))
            ->when(($filtros['estado'] ?? '') === 'anulada', fn ($query) => $query->whereNotNull('anulada_en'))
            ->when($filtros['desde'] ?? null, fn ($query, $fecha) => $query->where('created_at', '>=', $fecha.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($query, $fecha) => $query->where('created_at', '<=', $fecha.' 23:59:59'))
            ->latest()->paginate(15)->withQueryString();
        $tipo = 'ventas';

        return view('gestion.historial.index', compact('registros', 'filtros', 'tipo'));
    }

    public function asistencias(Request $request)
    {
        $this->acceso();
        $filtros = $this->filtros($request);
        $registros = Asistencia::withoutGlobalScope('vigentes')->with(['cliente:id,nombre', 'registrador:id,name'])
            ->when($filtros['q'] ?? null, fn ($query, $q) => $query->whereHas('cliente', fn ($query) => $query->where('nombre', 'like', "%{$q}%")))
            ->when(($filtros['estado'] ?? '') === 'vigente', fn ($query) => $query->whereNull('anulada_en'))
            ->when(($filtros['estado'] ?? '') === 'anulada', fn ($query) => $query->whereNotNull('anulada_en'))
            ->when($filtros['desde'] ?? null, fn ($query, $fecha) => $query->where('fecha_hora', '>=', $fecha.' 00:00:00'))
            ->when($filtros['hasta'] ?? null, fn ($query, $fecha) => $query->where('fecha_hora', '<=', $fecha.' 23:59:59'))
            ->latest('fecha_hora')->paginate(20)->withQueryString();
        $tipo = 'asistencias';

        return view('gestion.historial.index', compact('registros', 'filtros', 'tipo'));
    }

    public function editarVenta(int $venta)
    {
        $this->acceso();
        $registro = Venta::withoutGlobalScope('vigentes')->with(['detalles.producto', 'cliente:id,nombre'])->findOrFail($venta);

        return $this->formulario('ventas', $registro);
    }

    public function actualizarVenta(Request $request, int $venta, CorreccionHistorial $correcciones)
    {
        $actor = $this->acceso();
        $datos = $request->validate(['notas' => ['nullable', 'string', 'max:255', new SinDatosTarjeta], 'motivo' => ['required', 'string', 'max:255', new SinDatosTarjeta]]);
        $correcciones->editarNotaVenta($venta, $datos['notas'] ?? null, $datos['motivo'], $actor);

        return back()->with('status', 'Nota corregida. Importes, productos, cliente, pago y comprobantes emitidos se conservaron.');
    }

    public function anularVenta(Request $request, int $venta, CorreccionHistorial $correcciones)
    {
        $actor = $this->acceso(true);
        $datos = $this->validarAnulacion($request);
        $cambio = $correcciones->anularVenta($venta, $datos['motivo'], $actor);

        return redirect()->route('gestion.historial.ventas.index')->with('status', $cambio
            ? 'Venta anulada como registro erróneo. Se restituyó inventario una sola vez; no se realizó un reembolso.'
            : 'La venta ya estaba anulada. No se movió inventario ni se realizó un reembolso.');
    }

    public function editarAsistencia(int $asistencia)
    {
        $this->acceso();
        $registro = Asistencia::withoutGlobalScope('vigentes')->with('cliente:id,nombre')->findOrFail($asistencia);

        return $this->formulario('asistencias', $registro);
    }

    public function actualizarAsistencia(Request $request, int $asistencia, CorreccionHistorial $correcciones)
    {
        $actor = $this->acceso();
        $datos = $request->validate([
            'fecha_hora' => ['required', 'date'],
            'fecha_salida' => ['nullable', 'date', 'after_or_equal:fecha_hora'],
            'motivo' => ['required', 'string', 'max:255'],
        ], ['fecha_salida.after_or_equal' => 'La salida debe ser igual o posterior a la entrada.']);
        $correcciones->editarAsistencia($asistencia, $datos, $datos['motivo'], $actor);

        return back()->with('status', 'Horario corregido. Cliente y operadores originales se conservaron.');
    }

    public function anularAsistencia(Request $request, int $asistencia, CorreccionHistorial $correcciones)
    {
        $actor = $this->acceso();
        $datos = $this->validarAnulacion($request);
        $cambio = $correcciones->anularAsistencia($asistencia, $datos['motivo'], $actor);

        return redirect()->route('gestion.historial.asistencias.index')->with('status', $cambio ? 'Asistencia anulada. Se conservó su historial y se actualizó el aforo.' : 'La asistencia ya estaba anulada.');
    }

    private function validarAnulacion(Request $request): array
    {
        return $request->validate(['motivo' => ['required', 'string', 'max:255'], 'confirmacion' => ['required', 'accepted']],
            ['confirmacion.required' => 'Confirma que se trata de un registro erróneo.', 'confirmacion.accepted' => 'Confirma que se trata de un registro erróneo.']);
    }

    private function formulario(string $tipo, $registro)
    {
        $correcciones = DB::table('historial_correcciones')->where('tabla', $tipo)->where('registro_id', $registro->id)->orderByDesc('id')->paginate(15)->withQueryString();

        return view('gestion.historial.form', compact('tipo', 'registro', 'correcciones'));
    }
}

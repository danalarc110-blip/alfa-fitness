<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PausaMembresia;
use App\Models\PlanMembresia;
use App\Models\SolicitudMembresia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MembresiaController extends Controller
{
    public function index(Request $request)
    {
        $guard = auth('cliente')->check() ? 'cliente' : 'web';
        $clienteId = $guard === 'cliente' ? auth('cliente')->id() : null;
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['pendiente', 'activada', 'cancelada'])],
        ]);
        $busqueda = $filtros['q'] ?? '';
        $estado = $filtros['estado'] ?? '';
        $solicitudes = SolicitudMembresia::with(['cliente:id,nombre,correo', 'membresia.pago.registrador:id,name'])
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->when($busqueda && ! $clienteId, fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$busqueda}%")->orWhere('correo', 'like', "%{$busqueda}%")))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest()->paginate(15, ['*'], 'solicitudes_page')->withQueryString();
        $membresias = Membresia::with(['cliente:id,nombre', 'pago.registrador:id,name', 'pausas'])
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->latest('inicio')->paginate(15, ['*'], 'membresias_page')->withQueryString();
        $pausasPendientes = PausaMembresia::with(['cliente:id,nombre', 'membresia'])
            ->where('estado', 'pendiente')
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->latest()
            ->get();
        $planes = PlanMembresia::where('activo', true)->orderBy('precio')->get();
        $todosLosPlanes = Gate::allows('administrar') ? PlanMembresia::orderBy('precio')->get() : collect();

        return view('membresias.index', compact('guard', 'busqueda', 'estado', 'solicitudes', 'membresias', 'pausasPendientes', 'planes', 'todosLosPlanes'));
    }

    public function solicitar(Request $request)
    {
        abort_unless(auth('cliente')->check(), 403);
        $data = $request->validate(['plan_id' => ['required', Rule::exists('planes_membresia', 'id')->where('activo', true)]]);
        DB::transaction(function () use ($data) {
            $clienteId = auth('cliente')->id();
            Cliente::whereKey($clienteId)->lockForUpdate()->firstOrFail();
            if (SolicitudMembresia::where('cliente_id', $clienteId)->where('plan_id', $data['plan_id'])->where('estado', 'pendiente')->exists()) {
                throw ValidationException::withMessages(['plan_id' => 'Ya tienes una solicitud pendiente para este plan.']);
            }
            $plan = PlanMembresia::whereKey($data['plan_id'])->where('activo', true)->firstOrFail();
            SolicitudMembresia::create([
                'cliente_id' => $clienteId, 'plan_id' => $plan->id,
                'plan_nombre' => $plan->nombre, 'precio_acordado' => $plan->precio,
                'duracion_dias' => $plan->duracion_dias, 'condiciones' => $plan->condiciones,
            ]);
        });

        return back()->with('status', 'Solicitud creada. La membresia se activara cuando la secretaria confirme el pago presencial.');
    }

    public function activar(Request $request, SolicitudMembresia $solicitud)
    {
        Gate::authorize('operaciones');
        $data = $request->validate([
            'importe' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999'],
            'referencia' => ['nullable', 'string', 'max:100'],
        ]);
        $secretariaId = $request->user()->id;
        DB::transaction(function () use ($solicitud, $data, $secretariaId) {
            // Serialize all activations for this client, including different requests.
            $cliente = Cliente::whereKey($solicitud->cliente_id)->lockForUpdate()->firstOrFail();
            if (! $cliente->activo) {
                throw ValidationException::withMessages(['importe' => 'No se puede activar una membresía de una cuenta desactivada.']);
            }
            $solicitud = SolicitudMembresia::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->estado !== 'pendiente' || $solicitud->membresia()->exists()) {
                throw ValidationException::withMessages(['importe' => 'Esta solicitud ya fue resuelta; no se registro otro cobro.']);
            }
            $ultimaFecha = Membresia::where('cliente_id', $solicitud->cliente_id)->where('cancelada', false)->max('fin');
            $inicio = $ultimaFecha && today()->lte($ultimaFecha) ? Carbon::parse($ultimaFecha)->addDay() : today();
            $fin = $inicio->copy()->addDays($solicitud->duracion_dias - 1);
            $membresia = Membresia::create([
                'cliente_id' => $solicitud->cliente_id, 'solicitud_id' => $solicitud->id,
                'plan' => $solicitud->plan_nombre, 'importe' => $data['importe'],
                'inicio' => $inicio, 'fin' => $fin, 'cancelada' => false, 'activada_por' => $secretariaId,
            ]);
            PagoMembresia::create([
                'membresia_id' => $membresia->id, 'solicitud_id' => $solicitud->id,
                'registrado_por' => $secretariaId, 'importe' => $data['importe'],
                'pagado_en' => now(), 'referencia' => $data['referencia'] ?? null,
            ]);
            $solicitud->update(['estado' => 'activada', 'resuelta_en' => now(), 'resuelta_por' => $secretariaId]);
        });

        return back()->with('status', 'Pago registrado y membresia activada sin perder dias ya pagados.');
    }

    public function cancelarSolicitud(SolicitudMembresia $solicitud)
    {
        $esPropietario = auth('cliente')->check() && $solicitud->cliente_id === auth('cliente')->id();
        $esSecretaria = ! auth('cliente')->check() && auth('web')->user()?->rol === 'Secretaria';
        abort_unless($esSecretaria || $esPropietario, 403);
        DB::transaction(function () use ($solicitud) {
            $bloqueada = SolicitudMembresia::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if ($bloqueada->estado !== 'pendiente') {
                throw ValidationException::withMessages(['solicitud' => 'Solo se puede cancelar una solicitud pendiente.']);
            }
            $bloqueada->update(['estado' => 'cancelada', 'resuelta_en' => now(), 'resuelta_por' => auth('web')->id()]);
        });

        return back()->with('status', 'Solicitud cancelada.');
    }

    public function cancelar(Membresia $membresia)
    {
        abort_unless(auth('web')->user()?->rol === 'Secretaria', 403);
        DB::transaction(function () use ($membresia) {
            Cliente::whereKey($membresia->cliente_id)->lockForUpdate()->firstOrFail();
            $membresia = Membresia::whereKey($membresia->id)->lockForUpdate()->firstOrFail();
            $membresia->update(['cancelada' => true]);
            $membresia->pausas()->whereIn('estado', ['pendiente', 'aprobada'])->update(['estado' => 'rechazada']);
        });

        return back()->with('status', 'Membresia cancelada. El pago y el historial se conservan.');
    }

    public function solicitarPausa(Request $request, Membresia $membresia)
    {
        $esPropietario = auth('cliente')->check() && $membresia->cliente_id === auth('cliente')->id();
        $esStaff = auth('web')->check() && in_array(auth('web')->user()?->rol, ['Secretaria', 'Administrador'], true);
        abort_unless($esPropietario || $esStaff, 403);

        $data = $request->validate([
            'dias' => ['required', 'integer', 'min:3', 'max:30'],
            'motivo' => ['required', 'string', 'max:255'],
            'inicio_pausa' => ['required', 'date', 'after_or_equal:today'],
        ]);

        DB::transaction(function () use ($membresia, $data, $esStaff) {
            $cliente = Cliente::whereKey($membresia->cliente_id)->lockForUpdate()->firstOrFail();
            $membresiaBloqueada = Membresia::whereKey($membresia->id)->lockForUpdate()->firstOrFail();

            if (! $cliente->activo) {
                throw ValidationException::withMessages(['dias' => 'No se puede gestionar pausas de un socio inactivo o bloqueado.']);
            }
            if ($membresiaBloqueada->cancelada) {
                throw ValidationException::withMessages(['dias' => 'No se puede pausar una membresía cancelada.']);
            }
            if ($membresiaBloqueada->fin->isBefore(today())) {
                throw ValidationException::withMessages(['dias' => 'No se puede pausar una membresía que ya está vencida.']);
            }

            $inicio = Carbon::parse($data['inicio_pausa']);
            if ($inicio->isBefore($membresiaBloqueada->inicio)) {
                throw ValidationException::withMessages(['inicio_pausa' => 'La pausa no puede comenzar antes de la membresía.']);
            }
            if ($inicio->isAfter($membresiaBloqueada->fin)) {
                throw ValidationException::withMessages([
                    'inicio_pausa' => "La fecha de inicio de la pausa ({$inicio->format('d/m/Y')}) no puede ser posterior al vencimiento de la membresía ({$membresiaBloqueada->fin->format('d/m/Y')}).",
                ]);
            }

            if ($membresiaBloqueada->pausas()->whereIn('estado', ['pendiente', 'aprobada'])->where('fin_pausa_estimada', '>=', today())->exists()) {
                throw ValidationException::withMessages(['dias' => 'Esta membresía ya tiene una pausa activa o pendiente de revisión.']);
            }

            $diasPrevios = $membresiaBloqueada->diasPausadosAcumulados();
            if ($diasPrevios + $data['dias'] > 30) {
                $restantes = max(0, 30 - $diasPrevios);
                throw ValidationException::withMessages(['dias' => "Límite superado. Máximo 30 días de pausa por membresía. Disponibles: {$restantes} días."]);
            }

            $finEstimada = $inicio->copy()->addDays($data['dias'] - 1);
            $nuevoFin = $esStaff ? $this->finExtendidoSinSolapamiento($membresiaBloqueada, $data['dias'], 'dias') : null;

            $pausa = PausaMembresia::create([
                'membresia_id' => $membresiaBloqueada->id,
                'cliente_id' => $membresiaBloqueada->cliente_id,
                'dias' => $data['dias'],
                'motivo' => $data['motivo'],
                'estado' => $esStaff ? 'aprobada' : 'pendiente',
                'inicio_pausa' => $inicio,
                'fin_pausa_estimada' => $finEstimada,
                'aprobada_por' => $esStaff ? auth('web')->id() : null,
            ]);

            if ($esStaff) {
                $membresiaBloqueada->fin = $nuevoFin;
                $membresiaBloqueada->save();
            }
        });

        $msg = $esStaff
            ? 'Membresía congelada con éxito. Se postergó la fecha de vencimiento sin pérdida de días.'
            : 'Solicitud de congelamiento enviada. La secretaria revisará y autorizará la pausa.';

        return back()->with('status', $msg);
    }

    public function aprobarPausa(Request $request, PausaMembresia $pausa)
    {
        abort_unless(auth('web')->check() && in_array(auth('web')->user()?->rol, ['Secretaria', 'Administrador'], true), 403);

        DB::transaction(function () use ($pausa) {
            // Todas las operaciones toman cliente, membresía y pausa en ese orden.
            $cliente = Cliente::whereKey($pausa->cliente_id)->lockForUpdate()->firstOrFail();
            $membresia = Membresia::whereKey($pausa->membresia_id)->lockForUpdate()->firstOrFail();
            $pausaBloqueada = PausaMembresia::whereKey($pausa->id)->lockForUpdate()->firstOrFail();

            if (! $cliente->activo) {
                throw ValidationException::withMessages(['pausa' => 'No se puede aprobar una pausa de una cuenta desactivada.']);
            }

            if ($pausaBloqueada->estado !== 'pendiente') {
                throw ValidationException::withMessages(['pausa' => 'Esta pausa ya fue resuelta anteriormente.']);
            }

            if ($membresia->cancelada) {
                throw ValidationException::withMessages(['pausa' => 'No se puede aprobar una pausa sobre una membresía cancelada.']);
            }

            if ($membresia->fin->isBefore(today())) {
                throw ValidationException::withMessages(['pausa' => 'No se puede aprobar una pausa sobre una membresía que ya venció.']);
            }

            if ($pausaBloqueada->fin_pausa_estimada->isBefore(today())) {
                throw ValidationException::withMessages(['pausa' => 'El período solicitado para la pausa ya transcurrió en el pasado.']);
            }

            if ($membresia->diasPausadosAcumulados() + $pausaBloqueada->dias > 30) {
                throw ValidationException::withMessages(['pausa' => 'La aprobación excede el tope de 30 días de congelamiento permitido.']);
            }
            $nuevoFin = $this->finExtendidoSinSolapamiento($membresia, $pausaBloqueada->dias, 'pausa');

            $pausaBloqueada->update([
                'estado' => 'aprobada',
                'aprobada_por' => auth('web')->id(),
            ]);

            $membresia->fin = $nuevoFin;
            $membresia->save();
        });

        return back()->with('status', 'Pausa aprobada. Fecha de vencimiento postergada exitosamente.');
    }

    private function finExtendidoSinSolapamiento(Membresia $membresia, int $dias, string $campo): Carbon
    {
        // El cliente ya está bloqueado: activaciones y pausas comparten ese bloqueo.
        $nuevoFin = $membresia->fin->copy()->addDays($dias);
        $hayConflicto = Membresia::where('cliente_id', $membresia->cliente_id)
            ->whereKeyNot($membresia->id)
            ->where('cancelada', false)
            ->whereDate('inicio', '<=', $nuevoFin->toDateString())
            ->whereDate('fin', '>=', $membresia->fin->copy()->addDay()->toDateString())
            ->exists();
        if ($hayConflicto) {
            throw ValidationException::withMessages([$campo => 'La pausa extendería la vigencia sobre otra membresía ya registrada. Las fechas de ambos períodos se conservan.']);
        }

        return $nuevoFin;
    }

    public function rechazarPausa(Request $request, PausaMembresia $pausa)
    {
        abort_unless(auth('web')->check() && in_array(auth('web')->user()?->rol, ['Secretaria', 'Administrador'], true), 403);

        DB::transaction(function () use ($pausa) {
            $pausaBloqueada = PausaMembresia::whereKey($pausa->id)->lockForUpdate()->firstOrFail();
            if ($pausaBloqueada->estado !== 'pendiente') {
                throw ValidationException::withMessages(['pausa' => 'Solo se pueden rechazar solicitudes de pausa pendientes.']);
            }

            $pausaBloqueada->update(['estado' => 'rechazada']);
        });

        return back()->with('status', 'Solicitud de pausa rechazada.');
    }

    public function reanudar(Request $request, Membresia $membresia)
    {
        $esPropietario = auth('cliente')->check() && $membresia->cliente_id === auth('cliente')->id();
        $esStaff = auth('web')->check() && in_array(auth('web')->user()?->rol, ['Secretaria', 'Administrador'], true);
        abort_unless($esPropietario || $esStaff, 403);

        DB::transaction(function () use ($membresia) {
            Cliente::whereKey($membresia->cliente_id)->lockForUpdate()->firstOrFail();
            $membresiaBloqueada = Membresia::whereKey($membresia->id)->lockForUpdate()->firstOrFail();

            if ($membresiaBloqueada->cancelada) {
                throw ValidationException::withMessages(['membresia' => 'No se puede reanudar una membresía cancelada.']);
            }

            $pausa = $membresiaBloqueada->pausas()
                ->where('estado', 'aprobada')
                ->where('fin_pausa_estimada', '>=', today())
                ->lockForUpdate()
                ->first();

            if (! $pausa) {
                throw ValidationException::withMessages(['membresia' => 'Esta membresía no tiene ninguna pausa activa o programada para reanudar.']);
            }

            if (today()->isBefore($pausa->inicio_pausa)) {
                // Pausa futura que se anula/cancela antes de haber comenzado efectivamente
                $diasDevolver = $pausa->dias;
                $membresiaBloqueada->fin = $membresiaBloqueada->fin->copy()->subDays($diasDevolver);
                $membresiaBloqueada->save();

                $pausa->update([
                    'estado' => 'reanudada_anticipada',
                    'fecha_reanudacion' => today(),
                    'dias' => 0,
                    'fin_pausa_estimada' => today(),
                ]);
            } else {
                // Pausa actualmente en curso
                $diasEfectivos = max(1, (int) today()->diffInDays($pausa->inicio_pausa, true) + 1);
                $diasDevolver = max(0, $pausa->dias - $diasEfectivos);

                $membresiaBloqueada->fin = $membresiaBloqueada->fin->copy()->subDays($diasDevolver);
                $membresiaBloqueada->save();

                $pausa->update([
                    'estado' => 'reanudada_anticipada',
                    'fecha_reanudacion' => today(),
                    'dias' => $diasEfectivos,
                    'fin_pausa_estimada' => today(),
                ]);
            }
        });

        return back()->with('status', 'Membresía reanudada con éxito. Los días no utilizados fueron devueltos a la vigencia.');
    }
}

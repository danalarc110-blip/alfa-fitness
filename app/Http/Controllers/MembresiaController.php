<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoMembresia;
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
        $membresias = Membresia::with(['cliente:id,nombre', 'pago.registrador:id,name'])
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->latest('inicio')->paginate(15, ['*'], 'membresias_page')->withQueryString();
        $planes = PlanMembresia::where('activo', true)->orderBy('precio')->get();

        return view('membresias.index', compact('guard', 'busqueda', 'estado', 'solicitudes', 'membresias', 'planes'));
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
            $membresia->update(['cancelada' => true]);
        });

        return back()->with('status', 'Membresia cancelada. El pago y el historial se conservan.');
    }
}

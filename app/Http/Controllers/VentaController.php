<?php

namespace App\Http\Controllers;

use App\Mail\ComprobanteVentaMail;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Support\Dinero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('inventario');
        ['guard' => $guard, 'user' => $user] = $this->actual();
        $filtros = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'q' => ['nullable', 'string', 'max:255'],
            'metodo' => ['nullable', Rule::in(['Efectivo', 'Tarjeta', 'Transferencia'])],
        ]);
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;
        $busqueda = trim($filtros['q'] ?? '');
        $metodoSeleccionado = $filtros['metodo'] ?? '';
        $query = Venta::with(['user', 'cliente', 'detalles.producto'])->latest();
        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
        }
        if ($metodoSeleccionado) {
            $query->where('metodo_pago', $metodoSeleccionado);
        }
        if ($busqueda !== '') {
            $query->where(function ($query) use ($busqueda) {
                $query->where('notas', 'like', '%'.$busqueda.'%')
                    ->orWhereHas('cliente', fn ($cliente) => $cliente->where(function ($cliente) use ($busqueda) {
                        $cliente->where('nombre', 'like', '%'.$busqueda.'%')->orWhere('correo', 'like', '%'.$busqueda.'%');
                    }))
                    ->orWhereHas('detalles.producto', fn ($producto) => $producto->where('nombre', 'like', '%'.$busqueda.'%'));
                if (ctype_digit(ltrim($busqueda, '#'))) {
                    $query->orWhere('id', ltrim($busqueda, '#'));
                }
            });
        }
        $ventas = $query->paginate(15)->withQueryString();
        $hoyTotal = Venta::whereDate('created_at', today())->sum('total');
        $hoyConteo = Venta::whereDate('created_at', today())->count();
        $mesTotal = Venta::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('total');
        $productos = Producto::where('activo', true)->where('stock', '>', 0)->orderBy('nombre')->get();
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'correo']);

        return view('ventas.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'ventas' => $ventas,
            'hoyTotal' => $hoyTotal,
            'hoyConteo' => $hoyConteo,
            'mesTotal' => $mesTotal,
            'productos' => $productos,
            'clientes' => $clientes,
            'desde' => $desde,
            'hasta' => $hasta,
            'busqueda' => $busqueda,
            'metodoSeleccionado' => $metodoSeleccionado,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('inventario');
        ['user' => $user] = $this->actual();
        $data = $request->validate([
            'venta_uuid' => ['nullable', 'uuid'],
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'metodo_pago' => ['required', 'string', 'in:Efectivo,Tarjeta,Transferencia'],
            'notas' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ]);
        if (! empty($data['venta_uuid'])) {
            $data['venta_uuid'] = strtolower($data['venta_uuid']);
        }

        $venta = DB::transaction(function () use ($data, $user) {
            // Serializa reintentos del mismo vendedor incluso si cambia el carrito.
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! empty($data['venta_uuid'])) {
                $existente = Venta::where('venta_uuid', $data['venta_uuid'])->first();
                if ($existente) {
                    abort_unless($existente->user_id === $user->id, 403);

                    return $existente;
                }
            }
            if (! empty($data['cliente_id']) && ! Cliente::whereKey($data['cliente_id'])->lockForUpdate()->first()?->activo) {
                throw ValidationException::withMessages(['cliente_id' => 'El cliente seleccionado no está activo.']);
            }
            $totalCentavos = 0;
            $detalles = [];
            $cantidades = [];
            foreach ($data['items'] as $item) {
                $pid = (int) $item['producto_id'];
                $cantidades[$pid] = ($cantidades[$pid] ?? 0) + (int) $item['cantidad'];
                if ($cantidades[$pid] > 999) {
                    throw ValidationException::withMessages(['items' => 'El máximo por producto es de 999 unidades.']);
                }
            }
            $productos = Producto::whereIn('id', array_keys($cantidades))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($cantidades as $productoId => $cantidad) {
                $producto = $productos->get($productoId);
                if (! $producto || ! $producto->activo) {
                    throw ValidationException::withMessages(['items' => 'El producto seleccionado no está disponible para venta.']);
                }
                if ($producto->stock < $cantidad) {
                    throw ValidationException::withMessages(['items' => "Stock insuficiente para \"{$producto->nombre}\". Disponible: {$producto->stock}."]);
                }
                $precioCentavos = Dinero::centavos($producto->precio);
                $subtotalCentavos = $precioCentavos * $cantidad;
                $totalCentavos += $subtotalCentavos;
                if ($precioCentavos < 0 || $totalCentavos > 9999999999) {
                    throw ValidationException::withMessages(['items' => 'El importe de la venta excede el límite permitido.']);
                }
                $detalles[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => Dinero::decimal($precioCentavos),
                    'subtotal' => Dinero::decimal($subtotalCentavos),
                ];
            }
            $venta = Venta::create([
                'venta_uuid' => $data['venta_uuid'] ?? null,
                'user_id' => $user->id,
                'cliente_id' => $data['cliente_id'] ?? null,
                'total' => Dinero::decimal($totalCentavos),
                'metodo_pago' => $data['metodo_pago'],
                'notas' => $data['notas'] ?? null,
            ]);
            foreach ($detalles as $detalle) {
                $productos->get($detalle['producto_id'])->decrement('stock', $detalle['cantidad']);
                $venta->detalles()->create($detalle);
            }

            return $venta;
        });

        return back()->with('status', "Venta #{$venta->id} registrada exitosamente por \${$venta->total}.")->with('venta_creada_id', $venta->id);
    }

    public function comprobante(Venta $venta)
    {
        Gate::authorize('inventario');
        ['guard' => $guard, 'user' => $user] = $this->actual();
        $venta->load(['user', 'cliente', 'detalles.producto']);

        return view('ventas.comprobante', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'venta' => $venta,
        ]);
    }

    public function enviarCorreo(Request $request, Venta $venta)
    {
        Gate::authorize('inventario');
        $data = $request->validate(['correo' => ['required', 'email', 'max:255']]);
        $venta->load(['user', 'cliente', 'detalles.producto']);
        try {
            Mail::to($data['correo'])->send(new ComprobanteVentaMail($venta));
        } catch (\Throwable $e) {
            Log::warning('Fallo al enviar comprobante de venta por correo.', ['venta_id' => $venta->id, 'exception' => get_class($e)]);

            return back()->with('error', 'No se pudo enviar el correo en este momento. Verifique la conexión o intente más tarde.');
        }

        return back()->with('status', "Comprobante de la venta #{$venta->id} enviado exitosamente a {$data['correo']}.");
    }
}

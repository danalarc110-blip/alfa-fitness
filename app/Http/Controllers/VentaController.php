<?php

namespace App\Http\Controllers;

use App\Mail\ComprobanteVentaMail;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\PagoSimulado;
use App\Support\Dinero;
use Illuminate\Database\UniqueConstraintViolationException;
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
        $hoyTotal = Venta::reales()->whereDate('created_at', today())->sum('total');
        $hoyConteo = Venta::reales()->whereDate('created_at', today())->count();
        $mesTotal = Venta::reales()->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('total');
        $simulacionesHoy = Venta::where('pago_simulado', true)->whereDate('created_at', today())->count();
        $productos = Producto::where('activo', true)->orderBy('nombre')->get();
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'correo']);
        $itemsAnteriores = $request->session()->getOldInput('items', []);
        $itemsAnteriores = is_array($itemsAnteriores) ? array_values(array_filter($itemsAnteriores, 'is_array')) : [];
        $idsAnteriores = collect($itemsAnteriores)->pluck('producto_id')
            ->filter(fn ($id) => is_scalar($id) && filter_var($id, FILTER_VALIDATE_INT) !== false);
        // Conserva también las líneas que dejaron de estar disponibles tras un error.
        $catalogoVenta = $productos->merge(Producto::whereIn('id', $idsAnteriores)->get())
            ->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'categoria' => $producto->categoria ?: 'Sin categoría',
                'precio_centavos' => Dinero::centavos($producto->precio),
                'stock' => $producto->stock,
                'activo' => $producto->activo,
                'imagen_url' => $producto->imagen_url,
            ])->values();

        return view('ventas.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'ventas' => $ventas,
            'hoyTotal' => $hoyTotal,
            'hoyConteo' => $hoyConteo,
            'mesTotal' => $mesTotal,
            'simulacionesHoy' => $simulacionesHoy,
            'productos' => $productos,
            'clientes' => $clientes,
            'catalogoVenta' => $catalogoVenta,
            'itemsAnteriores' => $itemsAnteriores,
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
        $identidad = $request->validate(['venta_uuid' => ['required', 'uuid']], [
            'venta_uuid.required' => 'La venta debe tener un identificador para evitar registros duplicados.',
            'venta_uuid.uuid' => 'El identificador de la venta es inválido.',
        ]);
        $identidad['venta_uuid'] = strtolower($identidad['venta_uuid']);
        $existente = Venta::where('venta_uuid', $identidad['venta_uuid'])->first();
        if ($existente) {
            abort_unless($existente->user_id === $user->id, 403);

            return $this->ventaRegistrada($existente);
        }
        $data = $request->validate([
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'metodo_pago' => ['required', 'string', 'in:Efectivo,Tarjeta,Transferencia'],
            'notas' => ['nullable', 'string', 'max:255'],
            'pago_simulado' => ['sometimes', 'boolean'],
            'efectivo_recibido' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'resultado_tarjeta' => ['nullable', Rule::in(['aprobada', 'rechazada'])],
            'numero_tarjeta' => ['prohibited'],
            'cvv' => ['prohibited'],
            'card_number' => ['prohibited'],
            'cvc' => ['prohibited'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'cliente_id.exists' => 'El cliente seleccionado ya no está disponible. Elige otro cliente o Público General.',
            'metodo_pago.in' => 'Elige Efectivo, Tarjeta o Transferencia para registrar el pago presencial.',
            'items.required' => 'Añade al menos un producto a la venta.',
            'items.min' => 'Añade al menos un producto a la venta.',
            'items.max' => 'La venta admite hasta 100 productos distintos.',
            'items.*.producto_id.exists' => 'Uno de los productos ya no existe. Retíralo de la venta para continuar.',
            'items.*.cantidad.integer' => 'La cantidad de cada producto debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser al menos 1.',
            'items.*.cantidad.max' => 'El máximo por producto es de 999 unidades.',
        ]);
        $data['venta_uuid'] = $identidad['venta_uuid'];

        try {
            $venta = DB::transaction(function () use ($data, $user) {
                // Serializa reintentos del mismo vendedor incluso si cambia el carrito.
                $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $existente = Venta::where('venta_uuid', $data['venta_uuid'])->first();
                if ($existente) {
                    abort_unless($existente->user_id === $user->id, 403);

                    return $existente;
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
                $pago = app(PagoSimulado::class)->resolver($data, $totalCentavos);
                $venta = Venta::create([
                    'venta_uuid' => $data['venta_uuid'],
                    'user_id' => $user->id,
                    'cliente_id' => $data['cliente_id'] ?? null,
                    'total' => Dinero::decimal($totalCentavos),
                    'metodo_pago' => $data['metodo_pago'],
                    'notas' => $data['notas'] ?? null,
                ] + $pago);
                foreach ($detalles as $detalle) {
                    $productos->get($detalle['producto_id'])->decrement('stock', $detalle['cantidad']);
                    $venta->detalles()->create($detalle);
                }

                return $venta;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            // Otro vendedor puede haber usado el mismo UUID mientras esta venta esperaba.
            $venta = Venta::where('venta_uuid', $data['venta_uuid'])->first();
            if (! $venta) {
                throw $exception;
            }
            abort_unless($venta->user_id === $user->id, 403);
        }

        return $this->ventaRegistrada($venta);
    }

    private function ventaRegistrada(Venta $venta)
    {
        $tipo = $venta->pago_simulado ? ' Pago simulado: no se ha realizado ningún cobro real.' : '';

        return back()->with('status', "Venta #{$venta->id} registrada exitosamente por \${$venta->total}.".$tipo)->with('venta_creada_id', $venta->id);
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

        if (in_array(config('mail.mailers.'.config('mail.default').'.transport'), ['log', 'array'], true)) {
            return back()->with('status', 'Comprobante generado en modo de prueba. No se entregó por correo; puedes imprimirlo desde esta pantalla.');
        }

        return back()->with('status', "Comprobante de la venta #{$venta->id} enviado exitosamente a {$data['correo']}.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\ComprobanteVentaMail;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Rules\SinDatosTarjeta;
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
        ]);
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;

        $query = Venta::with(['user', 'cliente', 'detalles.producto'])->latest();

        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
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
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('inventario');
        ['user' => $user] = $this->actual();

        $data = $request->validate([
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->where('activo', true)],
            'metodo_pago' => ['required', 'string', 'in:Efectivo,Tarjeta,Transferencia'],
            'notas' => ['nullable', 'string', 'max:255', new SinDatosTarjeta],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'request_uid' => ['nullable', 'uuid'],
            'numero_tarjeta' => ['prohibited'],
            'card_number' => ['prohibited'],
            'pan' => ['prohibited'],
            'cvv' => ['prohibited'],
            'cvc' => ['prohibited'],
        ]);

        $venta = null;

        try {
            DB::transaction(function () use ($data, $user, &$venta) {
                $totalVenta = 0;
                $detallesParaCrear = [];

                $cantidadesPorProducto = [];
                foreach ($data['items'] as $item) {
                    $pid = (int) $item['producto_id'];
                    $cantidadesPorProducto[$pid] = ($cantidadesPorProducto[$pid] ?? 0) + (int) $item['cantidad'];
                }

                $productIds = array_keys($cantidadesPorProducto);
                sort($productIds);

                $productos = Producto::whereIn('id', $productIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if (! empty($data['request_uid'])) {
                    $existente = Venta::withoutGlobalScope('vigentes')->where('request_uid', $data['request_uid'])->lockForUpdate()->first();
                    if ($existente) {
                        $this->validarReintento($existente, $user->id);
                        $venta = $existente;

                        return;
                    }
                }

                foreach ($cantidadesPorProducto as $productoId => $cantidad) {
                    $producto = $productos->get($productoId);

                    if (! $producto || ! $producto->activo) {
                        throw ValidationException::withMessages([
                            'items' => 'El producto seleccionado no está disponible para venta.',
                        ]);
                    }

                    if ($producto->stock < $cantidad) {
                        throw ValidationException::withMessages([
                            'items' => "Stock insuficiente para \"{$producto->nombre}\". Disponible: {$producto->stock}.",
                        ]);
                    }

                    $subtotal = round($producto->precio * $cantidad, 2);
                    $totalVenta += $subtotal;
                    if ($subtotal > 99999999.99 || $totalVenta > 99999999.99) {
                        throw ValidationException::withMessages(['items' => 'El importe supera el máximo permitido. Divide la operación antes de registrar la venta.']);
                    }

                    $producto->decrement('stock', $cantidad);

                    $detallesParaCrear[] = [
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $producto->precio,
                        'subtotal' => $subtotal,
                    ];
                }

                $venta = Venta::create([
                    'user_id' => $user->id,
                    'cliente_id' => $data['cliente_id'] ?? null,
                    'total' => $totalVenta,
                    'metodo_pago' => $data['metodo_pago'],
                    'notas' => $data['notas'] ?? null,
                    'request_uid' => $data['request_uid'] ?? null,
                ]);

                foreach ($detallesParaCrear as $detalle) {
                    $venta->detalles()->create($detalle);
                }
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $venta = ! empty($data['request_uid']) ? Venta::withoutGlobalScope('vigentes')->where('request_uid', $data['request_uid'])->first() : null;
            if (! $venta) {
                throw $exception;
            }
            $this->validarReintento($venta, $user->id);
        }

        return back()
            ->with('status', "Venta #{$venta->id} registrada exitosamente por \${$venta->total}.")
            ->with('venta_creada_id', $venta->id);
    }

    private function validarReintento(Venta $venta, int $actorId): void
    {
        if ($venta->user_id !== $actorId || $venta->anulada_en) {
            throw ValidationException::withMessages(['items' => 'Ese identificador ya pertenece a otra operación o a una venta anulada. Abre un formulario nuevo.']);
        }
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
        $data = $request->validate([
            'correo' => ['required', 'email', 'max:255'],
        ]);

        $venta->load(['user', 'cliente', 'detalles.producto']);

        try {
            Mail::to($data['correo'])->send(new ComprobanteVentaMail($venta));
        } catch (\Throwable $e) {
            Log::warning('Fallo al enviar comprobante de venta por correo.', [
                'venta_id' => $venta->id,
                'exception' => get_class($e),
            ]);

            return back()->with('error', "No se pudo enviar el correo a {$data['correo']} en este momento. Verifique la conexión o intente más tarde.");
        }

        return back()->with('status', "Comprobante de la venta #{$venta->id} enviado exitosamente a {$data['correo']}.");
    }
}

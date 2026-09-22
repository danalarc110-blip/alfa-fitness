<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('inventario');
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

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
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'metodo_pago' => ['required', 'string', 'in:Efectivo,Tarjeta,Transferencia'],
            'notas' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $venta = null;

        DB::transaction(function () use ($data, $user, &$venta) {
            $totalVenta = 0;
            $detallesParaCrear = [];

            $cantidadesPorProducto = [];
            foreach ($data['items'] as $item) {
                $pid = (int) $item['producto_id'];
                $cantidadesPorProducto[$pid] = ($cantidadesPorProducto[$pid] ?? 0) + (int) $item['cantidad'];
            }

            $productos = Producto::whereIn('id', array_keys($cantidadesPorProducto))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

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
            ]);

            foreach ($detallesParaCrear as $detalle) {
                $venta->detalles()->create($detalle);
            }
        });

        return back()->with('status', "Venta #{$venta->id} registrada exitosamente por \${$venta->total}.");
    }
}

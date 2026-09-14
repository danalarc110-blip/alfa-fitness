<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\ImagenSegura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    public const LIMITE_CATALOGO = 5;

    public function index(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $busqueda = $filtros['q'] ?? '';
        $productos = Producto::query()
            ->when(! Gate::allows('inventario'), fn ($q) => $q->where('activo', true))
            ->when($busqueda, fn ($q) => $q->where(fn ($s) => $s->where('nombre', 'like', "%{$busqueda}%")->orWhere('categoria', 'like', "%{$busqueda}%")))
            ->orderBy('orden')->orderBy('nombre')->paginate(self::LIMITE_CATALOGO)->withQueryString();

        $totalCatalogo = Gate::allows('inventario') ? Producto::count() : null;

        return view('productos.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'productos' => $productos,
            'busqueda' => $busqueda,
            'limiteCatalogo' => self::LIMITE_CATALOGO,
            'totalCatalogo' => $totalCatalogo,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('inventario');
        $data = $request->validate($this->reglas());
        unset($data['imagen'], $data['eliminar_imagen']);
        $nuevo = null;
        $producto = null;
        try {
            DB::transaction(function () use ($data, $request, &$producto, &$nuevo) {
                $existentes = Producto::query()->lockForUpdate()->get(['id']);
                if ($existentes->count() >= self::LIMITE_CATALOGO) {
                    throw ValidationException::withMessages([
                        'nombre' => 'El catálogo admite un máximo de '.self::LIMITE_CATALOGO.' productos. Edita uno de los existentes.',
                    ]);
                }
                $producto = Producto::create($data + ['activo' => true]);
                if ($request->hasFile('imagen')) {
                    $nuevo = app(ImagenSegura::class)->guardar($request->file('imagen'), 'productos', 'producto_'.$producto->id);
                    $producto->update(['imagen' => $nuevo]);
                }
            });
        } catch (\Throwable $e) {
            if ($nuevo) {
                app(ImagenSegura::class)->eliminar($nuevo, 'productos', 'producto_'.$producto->id);
            }
            throw $e;
        }

        return back()->with('status', 'Producto creado.');
    }

    public function update(Request $request, Producto $producto)
    {
        Gate::authorize('inventario');
        $data = $request->validate($this->reglas(true));
        $anterior = $producto->imagen;
        $nuevo = $request->hasFile('imagen') ? app(ImagenSegura::class)->guardar($request->file('imagen'), 'productos', 'producto_'.$producto->id) : null;
        try {
            DB::transaction(function () use ($producto, $data, $request, $nuevo, &$anterior) {
                $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
                $anterior = $producto->imagen;
                $producto->update([
                    'nombre' => $data['nombre'], 'precio' => $data['precio'], 'categoria' => $data['categoria'] ?? null,
                    'stock' => $data['stock'], 'activo' => $request->boolean('activo'),
                    'imagen' => $nuevo ?: ($request->boolean('eliminar_imagen') ? null : $anterior),
                ]);
            });
        } catch (\Throwable $e) {
            if ($nuevo) {
                app(ImagenSegura::class)->eliminar($nuevo, 'productos', 'producto_'.$producto->id);
            }
            throw $e;
        }
        if ($nuevo || $request->boolean('eliminar_imagen')) {
            app(ImagenSegura::class)->eliminar($anterior, 'productos', 'producto_'.$producto->id);
        }

        return back()->with('status', 'Producto actualizado.');
    }

    private function reglas(bool $actualizar = false): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'], 'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999'],
            'categoria' => ['nullable', 'string', 'max:100'], 'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'activo' => [$actualizar ? 'nullable' : 'prohibited', 'boolean'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
            'eliminar_imagen' => [$actualizar ? 'nullable' : 'prohibited', 'boolean'],
        ];
    }
}

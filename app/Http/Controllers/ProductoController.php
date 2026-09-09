<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\ImagenSegura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        $busqueda = mb_substr($request->string('q')->toString(), 0, 100);
        $productos = Producto::query()
            ->when(! Gate::allows('inventario'), fn ($q) => $q->where('activo', true))
            ->when($busqueda, fn ($q) => $q->where(fn ($s) => $s->where('nombre', 'like', "%{$busqueda}%")->orWhere('categoria', 'like', "%{$busqueda}%")))
            ->orderBy('orden')->orderBy('nombre')->paginate(16)->withQueryString();
        return view('productos.index', ['guard' => $guard, 'nombre' => $this->nombreActual($guard, $user), 'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro', 'avatarUrl' => $user->avatar_url, 'productos' => $productos, 'busqueda' => $busqueda]);
    }

    public function store(Request $request)
    {
        Gate::authorize('inventario');
        $data = $request->validate($this->reglas());
        $producto = Producto::create($data + ['activo' => true]);
        if ($request->hasFile('imagen')) $producto->update(['imagen' => app(ImagenSegura::class)->guardar($request->file('imagen'), 'productos', 'producto_'.$producto->id)]);
        return back()->with('status', 'Producto creado.');
    }

    public function update(Request $request, Producto $producto)
    {
        Gate::authorize('inventario');
        $data = $request->validate($this->reglas(true));
        $anterior = $producto->imagen;
        $nuevo = $request->hasFile('imagen') ? app(ImagenSegura::class)->guardar($request->file('imagen'), 'productos', 'producto_'.$producto->id) : null;
        try {
            $producto->update([
                'nombre' => $data['nombre'], 'precio' => $data['precio'], 'categoria' => $data['categoria'] ?? null,
                'stock' => $data['stock'], 'activo' => $request->boolean('activo'),
                'imagen' => $nuevo ?: ($request->boolean('eliminar_imagen') ? null : $anterior),
            ]);
        } catch (\Throwable $e) {
            if ($nuevo) app(ImagenSegura::class)->eliminar($nuevo, 'productos', 'producto_'.$producto->id);
            throw $e;
        }
        if ($nuevo || $request->boolean('eliminar_imagen')) app(ImagenSegura::class)->eliminar($anterior, 'productos', 'producto_'.$producto->id);
        return back()->with('status', 'Producto actualizado.');
    }

    private function reglas(bool $actualizar = false): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'], 'precio' => ['required', 'numeric', 'min:0', 'max:999999'],
            'categoria' => ['nullable', 'string', 'max:100'], 'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'activo' => [$actualizar ? 'nullable' : 'prohibited', 'boolean'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
            'eliminar_imagen' => [$actualizar ? 'nullable' : 'prohibited', 'boolean'],
        ];
    }
}

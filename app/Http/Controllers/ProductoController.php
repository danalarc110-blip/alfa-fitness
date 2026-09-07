<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductoController extends Controller
{
    private function actual(): array
    {
        if (Auth::guard('web')->check()) {
            return ['guard' => 'web', 'user' => Auth::guard('web')->user()];
        }

        return ['guard' => 'cliente', 'user' => Auth::guard('cliente')->user()];
    }

    private function nombreActual(string $guard, $user): string
    {
        return $guard === 'web' ? $user->name : $user->nombre;
    }

    public function index(Request $request): View
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $busqueda = $request->string('q')->toString();
        $productos = Producto::query()
            ->when($busqueda, fn ($query) => $query->where(function ($subquery) use ($busqueda) {
                $subquery
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('categoria', 'like', "%{$busqueda}%");
            }))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('productos.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'productos' => $productos,
            'busqueda' => $busqueda,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::guard('web')->check(), 403);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0', 'max:999999'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        Producto::create($data + ['activo' => true]);

        return back()->with('status', 'Producto creado.');
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        abort_unless(Auth::guard('web')->check(), 403);

        $data = $request->validate([
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $producto->update([
            'stock' => $data['stock'],
            'activo' => $request->boolean('activo'),
        ]);

        return back()->with('status', 'Producto actualizado.');
    }
}

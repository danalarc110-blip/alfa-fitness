@extends('layouts.app', ['active' => 'productos'])

@section('title', 'Productos')

@section('page-header')
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Productos</h1>
            <p class="text-gray-400 text-xs mt-1">Un catálogo breve de hasta {{ $limiteCatalogo }} productos, con precio y existencias al día.</p>
        </div>

        <div class="flex items-center gap-3">
            @can('inventario')
                <a href="{{ route('ventas.index') }}"
                    class="alpha-btn-primary px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-md shadow-yellow-400/10">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    Punto de Venta (TPV)
                </a>
            @endcan
            <button type="button" onclick="alphaToggleTema()" title="Cambiar tema" aria-label="Cambiar tema"
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            </button>
        </div>
    </header>
@endsection

@section('content')
    @php($puedeGestionar = \Illuminate\Support\Facades\Gate::allows('inventario'))

    <section class="grid grid-cols-1 xl:grid-cols-[340px_1fr] gap-5">
        <aside class="space-y-5">
            <form method="GET" action="{{ route('productos.index') }}" class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card" role="search">
                <label class="block" for="buscar-producto">
                    <span class="block text-xs font-semibold text-gray-400 mb-1.5">Buscar producto</span>
                    <input id="buscar-producto" type="search" name="q" value="{{ $busqueda }}" maxlength="100" placeholder="Nombre o categoría">
                </label>

                <div class="grid grid-cols-2 gap-3 mt-4">
                    <button type="submit" class="alpha-btn-primary rounded-xl px-4 py-2.5 text-sm font-semibold">Buscar</button>
                    <a href="{{ route('productos.index') }}" class="alpha-btn-secondary rounded-xl px-4 py-2.5 text-sm font-semibold">Limpiar</a>
                </div>
            </form>

            @if ($puedeGestionar)
                @if ($totalCatalogo < $limiteCatalogo)
                    <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data" class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
                        @csrf
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-base font-bold text-white">Nuevo producto</h2>
                            <span class="text-[11px] font-semibold text-gray-400">{{ $totalCatalogo }}/{{ $limiteCatalogo }}</span>
                        </div>

                        <div class="space-y-4">
                            <label>
                                Nombre
                                <input type="text" name="nombre" value="{{ old('nombre') }}" maxlength="255" required>
                            </label>

                            <label>
                                Imagen opcional
                                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" data-image-preview="producto-nuevo-preview">
                                <span class="block mt-1 text-[11px] text-gray-500">JPG, PNG o WebP · máximo 2 MB</span>
                            </label>
                            <img id="producto-nuevo-preview" class="hidden w-full h-36 object-cover rounded-xl" alt="Vista previa de la imagen seleccionada">

                            <div class="grid grid-cols-2 gap-3">
                                <label>
                                    Precio
                                    <input type="number" name="precio" min="0" max="999999" step="0.01" value="{{ old('precio') }}" required>
                                </label>
                                <label>
                                    Stock
                                    <input type="number" name="stock" min="0" max="999999" value="{{ old('stock', 0) }}" required>
                                </label>
                            </div>

                            <label>
                                Categoría
                                <input type="text" name="categoria" value="{{ old('categoria') }}" maxlength="100" placeholder="Opcional">
                            </label>

                            <button type="submit" class="alpha-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-semibold">Guardar producto</button>
                        </div>
                    </form>
                @else
                    <div class="alpha-card rounded-2xl p-5 text-sm text-gray-400" data-animate="card">
                        <p class="font-semibold text-white">Catálogo completo: {{ $limiteCatalogo }}/{{ $limiteCatalogo }}</p>
                        <p class="mt-1">Para mantenerlo breve, edita uno de los productos existentes.</p>
                    </div>
                @endif
            @endif
        </aside>

        <section class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0" data-animate="card">
            <div class="flex items-center justify-between gap-3 mb-5">
                <h2 class="text-base font-bold text-white">Catálogo</h2>
                <span class="text-xs font-semibold text-gray-500">{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}</span>
            </div>

            @if ($productos->isNotEmpty())
                <div class="grid grid-cols-1 2xl:grid-cols-2 gap-3">
                    @foreach ($productos as $producto)
                        <article class="alpha-card-interactive bg-black/40 border border-white/10 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-16 h-16 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                    @if ($producto->imagen_url)
                                        <img
                                            src="{{ $producto->imagen_url }}"
                                            alt="{{ $producto->nombre }}"
                                            width="64"
                                            height="64"
                                            loading="lazy"
                                            decoding="async"
                                            class="w-full h-full object-cover"
                                        >
                                    @else
                                        <svg class="w-6 h-6 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-bold text-white truncate">{{ $producto->nombre }}</h3>
                                            <p class="text-[11px] text-gray-500 truncate">{{ $producto->categoria ?: 'Sin categoría' }}</p>
                                        </div>
                                        <span class="shrink-0 text-sm font-bold text-yellow-400">{{ $producto->precio_formateado }}</span>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between text-xs">
                                        <span class="text-gray-500">Existencias</span>
                                        <div class="text-right">
                                            @if (! $producto->activo || $producto->stock <= 0)
                                                <span class="font-semibold text-red-300">No disponible</span>
                                            @elseif ($producto->stock <= 3)
                                                <span class="inline-flex items-center gap-1 font-semibold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-md text-[11px]" title="Quedan {{ $producto->stock }} unidad(es)">
                                                    <svg class="w-3 h-3 text-amber-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                                    Stock crítico ({{ $producto->stock }})
                                                </span>
                                            @else
                                                <span class="font-semibold text-green-400">
                                                    {{ $producto->stock.' disponibles' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($puedeGestionar)
                                <details class="mt-4 border-t border-white/10 pt-3">
                                    <summary class="text-sm font-semibold text-yellow-400 cursor-pointer">Editar producto</summary>
                                    <form method="POST" action="{{ route('productos.update', $producto) }}" enctype="multipart/form-data" class="alpha-form mt-4 grid sm:grid-cols-2 gap-3">
                                        @csrf
                                        @method('PUT')

                                        <label>Nombre<input name="nombre" value="{{ $producto->nombre }}" maxlength="255" required></label>
                                        <label>Precio<input type="number" name="precio" min="0" max="999999" step="0.01" value="{{ $producto->precio }}" required @readonly(!\Illuminate\Support\Facades\Gate::allows('administrar'))></label>
                                        <label>Categoría<input name="categoria" value="{{ $producto->categoria }}" maxlength="100"></label>
                                        <label>Stock<input type="number" name="stock" min="0" max="999999" value="{{ $producto->stock }}" required></label>

                                        <label class="inline-flex items-center gap-2 text-xs text-gray-400">
                                            <input type="checkbox" name="activo" value="1" @checked($producto->activo)>
                                            Visible y disponible
                                        </label>

                                        <label class="sm:col-span-2">
                                            Cambiar imagen
                                            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" data-image-preview="producto-{{ $producto->id }}-preview">
                                        </label>
                                        <img id="producto-{{ $producto->id }}-preview" class="hidden sm:col-span-2 w-full h-32 object-cover rounded-xl" alt="Vista previa de la imagen seleccionada">

                                        @if ($producto->imagen)
                                            <label class="sm:col-span-2 inline-flex items-center gap-2">
                                                <input type="checkbox" name="eliminar_imagen" value="1">
                                                Eliminar imagen actual
                                            </label>
                                        @endif

                                        <button type="submit" class="alpha-btn-primary sm:col-span-2 rounded-xl px-4 py-2.5">Guardar cambios</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <h3 class="text-base font-bold text-white mb-1">Sin productos encontrados</h3>
                    <p class="text-xs text-gray-400">{{ $busqueda ? 'Prueba con otra búsqueda.' : 'Agrega el primer producto para iniciar el catálogo.' }}</p>
                </div>
            @endif
        </section>
    </section>

    @if ($productos->hasPages())
        <div class="mt-5">{{ $productos->links() }}</div>
    @endif
@endsection

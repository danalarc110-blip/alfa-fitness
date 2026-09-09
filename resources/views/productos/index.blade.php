@extends('layouts.app', ['active' => 'productos'])
@section('title', 'Productos')
@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Productos</h1>
                    <p class="text-gray-400 text-xs mt-1">Catálogo base para stock y futuras ventas/consumos.</p>
                </div>

                <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
            </header>
@endsection
@section('content')
<section class="grid grid-cols-1 xl:grid-cols-[360px_1fr] gap-5">
                <aside class="space-y-5">
                    <form method="GET" action="{{ route('productos.index') }}" class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
                        <label class="block">
                            <span class="block text-xs font-semibold text-gray-400 mb-1.5">Buscar producto</span>
                            <input type="text" name="q" value="{{ $busqueda }}" placeholder="Nombre o categoría"
                                class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                        </label>

                        <button type="submit" class="alpha-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-semibold mt-4">
                            Buscar
                        </button>
                    </form>

                    @if (\Illuminate\Support\Facades\Gate::allows('inventario'))
                        <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data" class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
                            @csrf
                            <h2 class="text-base font-bold text-white mb-4">Nuevo producto</h2>

                            <div class="space-y-4">
                                <label class="block">
                                    <span class="block text-xs font-semibold text-gray-400 mb-1.5">Nombre</span>
                                    <input type="text" name="nombre" value="{{ old('nombre') }}" required
                                        class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                                </label>

                                <label class="block"><span class="block text-xs font-semibold text-gray-400 mb-1.5">Imagen (JPG, PNG o WebP, max. 2 MB)</span><input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" data-image-preview="producto-nuevo-preview"></label>
                                <img id="producto-nuevo-preview" class="hidden w-full h-36 object-cover rounded-xl" alt="Vista previa">

                                <div class="grid grid-cols-2 gap-3">
                                    <label class="block">
                                        <span class="block text-xs font-semibold text-gray-400 mb-1.5">Precio</span>
                                        <input type="number" name="precio" min="0" step="0.01" value="{{ old('precio') }}" required
                                            class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                                    </label>

                                    <label class="block">
                                        <span class="block text-xs font-semibold text-gray-400 mb-1.5">Stock</span>
                                        <input type="number" name="stock" min="0" value="{{ old('stock', 0) }}" required
                                            class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                                    </label>
                                </div>

                                <label class="block">
                                    <span class="block text-xs font-semibold text-gray-400 mb-1.5">Categoría</span>
                                    <input type="text" name="categoria" value="{{ old('categoria') }}" placeholder="Opcional"
                                        class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                                </label>

                                <button type="submit" class="alpha-btn-primary w-full rounded-xl px-4 py-2.5 text-sm font-semibold">
                                    Guardar producto
                                </button>
                            </div>
                        </form>
                    @endif
                </aside>

                <section class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0" data-animate="card">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-base font-bold text-white">Catálogo</h2>
                        <span class="text-xs font-semibold text-gray-500">{{ $productos->total() }} productos</span>
                    </div>

                    @if ($productos->isNotEmpty())
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            @foreach ($productos as $producto)
                                <div class="bg-black/40 border border-white/10 rounded-xl p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-14 h-14 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                            @if ($producto->imagen_url)
                                                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-6 h-6 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
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

                                            @if (\Illuminate\Support\Facades\Gate::allows('inventario'))
                                                <form method="POST" action="{{ route('productos.update', $producto) }}" enctype="multipart/form-data" class="alpha-form mt-4 grid grid-cols-2 gap-3">
                                                    @csrf
                                                    @method('PUT')

                                                    <label>Nombre<input name="nombre" value="{{ $producto->nombre }}" required></label>
                                                    <label>Precio<input type="number" name="precio" min="0" step="0.01" value="{{ $producto->precio }}" required></label>
                                                    <label>Categoria<input name="categoria" value="{{ $producto->categoria }}"></label>
                                                    <label>
                                                        <span>Stock</span>
                                                        <input type="number" name="stock" min="0" value="{{ $producto->stock }}"
                                                            class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white outline-none focus:border-yellow-400/60">
                                                    </label>

                                                    <label class="inline-flex items-center gap-2 text-xs text-gray-400">
                                                        <input type="checkbox" name="activo" value="1" @checked($producto->activo) class="rounded border-white/20 bg-black text-yellow-400">
                                                        Activo
                                                    </label>

                                                    <label class="col-span-2">Cambiar imagen<input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" data-image-preview="producto-{{ $producto->id }}-preview"></label>
                                                    <img id="producto-{{ $producto->id }}-preview" class="hidden col-span-2 w-full h-32 object-cover rounded-xl" alt="Vista previa">
                                                    @if($producto->imagen)<label class="col-span-2 inline-flex items-center gap-2"><input type="checkbox" name="eliminar_imagen" value="1"> Eliminar imagen actual</label>@endif
                                                    <button type="submit" class="alpha-btn-primary col-span-2 rounded-xl px-4 py-2">Guardar cambios</button>
                                                </form>
                                            @else
                                                <div class="mt-4 flex items-center justify-between text-xs">
                                                    <span class="text-gray-500">Stock</span>
                                                    <span class="font-semibold {{ $producto->stock > 0 && $producto->activo ? 'text-green-400' : 'text-red-300' }}">
                                                        {{ $producto->activo && $producto->stock > 0 ? $producto->stock.' disponibles' : 'No disponible' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <h3 class="text-base font-bold text-white mb-1">Sin productos todavía</h3>
                            <p class="text-xs text-gray-400">Agrega productos para iniciar el catálogo.</p>
                        </div>
                    @endif
                </section>
            </section>
        <div class="mt-5">{{ $productos->links() }}</div>
<script>document.querySelectorAll('[data-image-preview]').forEach(input=>input.addEventListener('change',()=>{const image=document.getElementById(input.dataset.imagePreview);const file=input.files[0];if(!file){image.classList.add('hidden');return}image.src=URL.createObjectURL(file);image.classList.remove('hidden')}));</script>
@endsection

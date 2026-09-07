<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Productos</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <script>
        if (localStorage.getItem('alphaTema') === 'light') {
            document.documentElement.classList.add('light');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black text-white min-h-screen">
    <div class="min-h-screen flex flex-col md:flex-row">
        @include('partials.sidebar', ['active' => 'productos'])

        <main class="flex-1 flex flex-col min-w-0 px-4 sm:px-6 md:px-10 py-6 sm:py-8">
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

            @if (session('status'))
                <div class="mb-5 rounded-xl border border-yellow-400/20 bg-yellow-400/10 px-4 py-3 text-sm font-semibold text-yellow-300">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

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

                    @if ($guard === 'web')
                        <form method="POST" action="{{ route('productos.store') }}" class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
                            @csrf
                            <h2 class="text-base font-bold text-white mb-4">Nuevo producto</h2>

                            <div class="space-y-4">
                                <label class="block">
                                    <span class="block text-xs font-semibold text-gray-400 mb-1.5">Nombre</span>
                                    <input type="text" name="nombre" value="{{ old('nombre') }}" required
                                        class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white outline-none focus:border-yellow-400/60">
                                </label>

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
                        <span class="text-xs font-semibold text-gray-500">{{ $productos->count() }} productos</span>
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

                                            @if ($guard === 'web')
                                                <form method="POST" action="{{ route('productos.update', $producto) }}" class="mt-4 flex items-center gap-3">
                                                    @csrf
                                                    @method('PUT')

                                                    <label class="flex-1">
                                                        <span class="sr-only">Stock</span>
                                                        <input type="number" name="stock" min="0" value="{{ $producto->stock }}"
                                                            class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white outline-none focus:border-yellow-400/60">
                                                    </label>

                                                    <label class="inline-flex items-center gap-2 text-xs text-gray-400">
                                                        <input type="checkbox" name="activo" value="1" @checked($producto->activo) class="rounded border-white/20 bg-black text-yellow-400">
                                                        Activo
                                                    </label>

                                                    <button type="submit" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-yellow-400 hover:text-black border border-white/10 text-gray-300 transition-colors" title="Actualizar">
                                                        <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                                    </button>
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
        </main>
    </div>
</body>
</html>

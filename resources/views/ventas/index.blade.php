@extends('layouts.app', ['active' => 'ventas'])
@section('title', 'Punto de Venta')

@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Ventas</h1>
        </div>
        <p class="text-gray-400 text-sm mt-1">Añade productos, elige cómo te pagan y registra la venta.</p>
    </div>

</header>
@endsection

@section('content')
@php
    $mostrarHistorial = !session()->hasOldInput('venta_uuid') && (request('vista') === 'historial' || request()->anyFilled(['q', 'desde', 'hasta', 'metodo', 'page']));
@endphp
<nav class="flex flex-wrap gap-2 mb-5" aria-label="Secciones de ventas">
    <button type="button" data-venta-ir="nueva" aria-controls="venta-nueva" aria-pressed="{{ $mostrarHistorial ? 'false' : 'true' }}" class="alpha-btn-secondary px-4 py-2">Nueva venta</button>
    <button type="button" data-venta-ir="historial" aria-controls="venta-historial" aria-pressed="{{ $mostrarHistorial ? 'true' : 'false' }}" class="alpha-btn-secondary px-4 py-2">Historial y comprobantes</button>
</nav>
@if(session('venta_creada_id'))
<div class="mb-6 p-4 rounded-2xl bg-yellow-400/10 border border-yellow-400/30 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs" data-animate="card">
    <div class="flex items-center gap-2.5 text-yellow-400 font-semibold">
        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <span>¡Venta registrada exitosamente! Puedes consultar el comprobante digital o enviarlo por correo:</span>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('ventas.comprobante', session('venta_creada_id')) }}" class="alpha-btn-primary px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-yellow-400/10">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
            Ver / Enviar Comprobante
        </a>
    </div>
</div>
@endif

<section id="venta-historial" aria-label="Historial y comprobantes" @class(['hidden' => !$mostrarHistorial])>
{{-- MÉTRICAS DE VENTAS --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 sm:mb-8" data-animate="card">
    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-400">Ventas Hoy</span>
            <span class="w-8 h-8 rounded-xl bg-yellow-400/10 text-yellow-400 flex items-center justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            </span>
        </div>
        <p class="text-2xl font-black text-white">${{ number_format($hoyTotal, 2) }}</p>
        <p class="text-[11px] text-gray-500 mt-1">{{ $hoyConteo }} {{ $hoyConteo === 1 ? 'ticket cobrado' : 'tickets cobrados' }}</p>
    </div>

    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-400">Ventas del Mes</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-400/10 text-emerald-400 flex items-center justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
        </div>
        <p class="text-2xl font-black text-white">${{ number_format($mesTotal, 2) }}</p>
        <p class="text-[11px] text-gray-500 mt-1">{{ now()->translatedFormat('F Y') }}</p>
    </div>

    <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-400">Productos con Stock</span>
            <span class="w-8 h-8 rounded-xl bg-blue-400/10 text-blue-400 flex items-center justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
            </span>
        </div>
        <p class="text-2xl font-black text-white">{{ $productos->where('stock', '>', 0)->count() }}</p>
        <p class="text-[11px] text-gray-500 mt-1">Disponibles para venta inmediata</p>
    </div>
</div>

{{-- FILTROS DE FECHA Y LISTADO DE VENTAS --}}
<div class="alpha-card rounded-2xl p-5 sm:p-6 border border-white/10 mb-6" data-animate="card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 pb-4 border-b border-white/5">
        <div class="flex items-center gap-2">
            <h2 class="text-base font-bold text-white">Historial de Ventas</h2>
            <span class="text-xs text-gray-500">({{ $ventas->total() }} registros)</span>
        </div>

        <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="vista" value="historial">
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-3 py-1.5">
                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" aria-label="Buscar ticket, cliente o producto" value="{{ $busqueda ?? '' }}" placeholder="Buscar ticket, cliente..." class="bg-transparent text-xs text-white placeholder-gray-500 focus:outline-none w-36 sm:w-44">
            </div>
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-2.5 py-1.5">
                <select name="metodo" aria-label="Filtrar por método de pago" class="bg-transparent text-xs text-white focus:outline-none">
                    <option value="" class="bg-[#141416]">Todos los métodos</option>
                    <option value="Efectivo" class="bg-[#141416]" @selected(($metodoSeleccionado ?? '') === 'Efectivo')>Efectivo</option>
                    <option value="Tarjeta" class="bg-[#141416]" @selected(($metodoSeleccionado ?? '') === 'Tarjeta')>Tarjeta</option>
                    <option value="Transferencia" class="bg-[#141416]" @selected(($metodoSeleccionado ?? '') === 'Transferencia')>Transferencia</option>
                </select>
            </div>
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-3 py-1.5">
                <span class="text-[11px] text-gray-400 font-medium">Desde:</span>
                <input type="date" name="desde" aria-label="Ventas desde" value="{{ $desde }}" class="bg-transparent text-xs text-white focus:outline-none">
            </div>
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-3 py-1.5">
                <span class="text-[11px] text-gray-400 font-medium">Hasta:</span>
                <input type="date" name="hasta" aria-label="Ventas hasta" value="{{ $hasta }}" class="bg-transparent text-xs text-white focus:outline-none">
            </div>
            <button type="submit" class="alpha-btn-primary px-3.5 py-1.5 rounded-xl text-xs font-semibold">Filtrar</button>
            @if($desde || $hasta || !empty($busqueda) || !empty($metodoSeleccionado))
                <a href="{{ route('ventas.index', ['vista' => 'historial']) }}" class="alpha-btn-secondary px-3 py-1.5 rounded-xl text-xs">Limpiar</a>
            @endif
        </form>
    </div>

    @if($ventas->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-gray-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-3">Ticket</th>
                        <th class="py-3 px-3">Fecha</th>
                        <th class="py-3 px-3">Atendido por</th>
                        <th class="py-3 px-3">Cliente</th>
                        <th class="py-3 px-3">Productos</th>
                        <th class="py-3 px-3">Método</th>
                        <th class="py-3 px-3 text-right">Total</th>
                        <th class="py-3 px-3 text-right">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($ventas as $v)
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="py-3 px-3 font-mono font-bold text-yellow-400">#{{ str_pad($v->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3 px-3 text-gray-300">{{ $v->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-3 text-gray-300">{{ $v->user->name ?? 'Staff' }}</td>
                            <td class="py-3 px-3 text-gray-300">
                                @if($v->cliente)
                                    <span class="font-semibold text-white">{{ $v->cliente->nombre }}</span>
                                @else
                                    <span class="text-gray-500 italic">Público General</span>
                                @endif
                                @if($v->notas)
                                    <p class="text-[10px] text-gray-400 italic mt-0.5 truncate max-w-xs" title="{{ $v->notas }}">{{ $v->notas }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach($v->detalles as $det)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-white/5 border border-white/5 text-[11px] text-gray-300">
                                            <strong>{{ $det->cantidad }}x</strong> {{ $det->producto->nombre ?? 'Producto' }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $v->metodo_pago === 'Efectivo' ? 'bg-emerald-400/10 text-emerald-400 border border-emerald-400/20' : ($v->metodo_pago === 'Tarjeta' ? 'bg-blue-400/10 text-blue-400 border border-blue-400/20' : 'bg-purple-400/10 text-purple-400 border border-purple-400/20') }}">
                                    {{ $v->metodo_pago }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-white text-sm">
                                ${{ number_format($v->total, 2) }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('ventas.comprobante', $v) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/5 hover:bg-yellow-400/10 border border-white/10 hover:border-yellow-400/30 text-gray-300 hover:text-yellow-400 transition text-[11px] font-medium" title="Ver comprobante e imprimir o enviar por correo">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                    Ticket
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $ventas->links() }}</div>
    @else
        <div class="py-12 text-center text-gray-400">
            <svg class="w-10 h-10 mx-auto text-gray-600 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            <p class="font-semibold text-white">No hay ventas registradas en el período seleccionado.</p>
            <button type="button" data-venta-ir="nueva" class="alpha-btn-primary px-4 py-2 mt-4">Registrar una venta</button>
        </div>
    @endif
</div>
</section>

<section id="venta-nueva" aria-labelledby="titulo-nueva-venta" @class(['hidden' => $mostrarHistorial])>
    <div class="alpha-card border border-white/10 rounded-2xl w-full p-4 sm:p-6">
        <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-white/10">
            <div>
                <h2 id="titulo-nueva-venta" class="text-lg font-bold text-white">Nueva venta de mostrador</h2>
                <p class="text-sm text-gray-400 mt-1">1. Añade productos · 2. Prepara el cobro · 3. Confirma</p>
            </div>
        </div>

        @php
            $recuperarVenta = session()->hasOldInput('venta_uuid');
            $clienteAnterior = is_scalar(old('cliente_id')) ? old('cliente_id') : '';
            $metodoAnterior = is_string(old('metodo_pago')) ? old('metodo_pago') : 'Efectivo';
            $notasAnteriores = is_string(old('notas')) ? old('notas') : '';
            $uuidAnterior = is_string(old('venta_uuid')) && \Illuminate\Support\Str::isUuid(old('venta_uuid')) ? old('venta_uuid') : (string) \Illuminate\Support\Str::uuid();
        @endphp
        @if($recuperarVenta && $errors->any())
            <div id="venta-errores" role="alert" tabindex="-1" class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm">
                <p class="font-semibold">La venta no se registró. Conservamos tus datos para corregirla.</p>
                <ul class="list-disc pl-5 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <noscript><p class="text-sm text-red-400">Activa JavaScript para buscar productos y registrar una venta de mostrador.</p></noscript>

        <form method="POST" action="{{ route('ventas.store') }}" id="form-venta" class="space-y-4">
            @csrf
            <input type="hidden" name="venta_uuid" value="{{ $uuidAnterior }}">
            <div id="venta-edicion" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <section aria-labelledby="venta-catalogo-titulo" class="min-w-0">
                    <h3 id="venta-catalogo-titulo" class="text-base font-bold mb-2">1. Añade productos</h3>
                    <label for="venta-busqueda" class="block text-xs text-gray-300 mb-1">Buscar por nombre o categoría</label>
                    <input id="venta-busqueda" type="search" autocomplete="off" placeholder="Ej: proteína, bebidas..."
                        class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-yellow-400/60 outline-none">
                    <div class="flex flex-wrap gap-2 mt-3" role="group" aria-label="Categorías de productos">
                        <button type="button" data-venta-categoria="" aria-pressed="true" class="alpha-btn-secondary px-3 py-2 text-xs">Todos</button>
                        @foreach($productos->map(fn ($p) => $p->categoria ?: 'Sin categoría')->unique()->sort() as $categoria)
                            <button type="button" data-venta-categoria="{{ $categoria }}" aria-pressed="false" class="alpha-btn-secondary px-3 py-2 text-xs">{{ $categoria }}</button>
                        @endforeach
                    </div>
                    <p id="venta-resultados" class="text-xs text-gray-400 mt-3" role="status"></p>
                    <a id="venta-ir-carrito" href="#venta-items-titulo" class="alpha-btn-secondary inline-block px-3 py-2 text-xs mt-2 lg:hidden">Ver carrito ↓</a>
                    <div id="venta-catalogo" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[32rem] overflow-y-auto pr-1">
                        @foreach($productos as $producto)
                            <article class="venta-producto bg-black/40 border border-white/10 rounded-xl p-3 min-w-0"
                                data-producto-id="{{ $producto->id }}" data-categoria="{{ $producto->categoria ?: 'Sin categoría' }}" data-busqueda="{{ $producto->nombre }} {{ $producto->categoria }}">
                                <div class="flex gap-3 items-center">
                                    @if($producto->imagen_url)
                                        <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" width="56" height="56" loading="lazy" class="w-14 h-14 shrink-0 rounded-lg object-contain bg-white/5">
                                    @else
                                        <div class="w-14 h-14 shrink-0 rounded-lg bg-white/5 flex items-center justify-center text-gray-500" aria-hidden="true">
                                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/></svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <h5 class="text-xs font-semibold text-white break-words">{{ $producto->nombre }}</h5>
                                        <p class="text-[11px] text-gray-400 break-words">{{ $producto->categoria ?: 'Sin categoría' }}</p>
                                        <p class="text-sm font-bold text-yellow-400">${{ number_format($producto->precio, 2) }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2 mt-2">
                                    <span class="text-[11px] {{ $producto->stock <= 3 ? 'text-amber-400' : 'text-gray-400' }}">
                                        {{ $producto->stock > 0 ? 'Stock: '.$producto->stock : 'Agotado' }}
                                    </span>
                                    <button type="button" data-anadir-producto="{{ $producto->id }}" aria-label="Añadir {{ $producto->nombre }}"
                                        @disabled($producto->stock < 1)
                                        class="alpha-btn-primary px-3 py-1.5 text-xs font-semibold disabled:opacity-40 disabled:cursor-not-allowed">Añadir</button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <p id="venta-sin-resultados" class="text-xs text-gray-400 py-4 {{ $productos->isEmpty() ? '' : 'hidden' }}">No hay productos que coincidan con la búsqueda.</p>
                </section>

                <section aria-labelledby="venta-items-titulo" class="min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h3 id="venta-items-titulo" tabindex="-1" class="text-base font-bold">2. Prepara el cobro</h3>
                        <span id="venta-conteo" class="text-xs text-gray-400">0 unidades</span>
                    </div>
                    <div id="items-contenedor" class="space-y-2 max-h-80 overflow-y-auto pr-1"></div>
                    <p id="venta-vacia" class="text-sm text-gray-400 py-8 text-center border border-dashed border-white/10 rounded-xl">Tu carrito está vacío. Pulsa Añadir en un producto para empezar.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                        <div>
                            <label for="venta-cliente" class="block text-xs font-semibold text-gray-300 mb-1">Cliente</label>
                            <select id="venta-cliente" name="cliente_id" class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-yellow-400/60 outline-none">
                                <option value="">Público General</option>
                                @if($clienteAnterior && !$clientes->contains('id', $clienteAnterior))
                                    <option value="{{ $clienteAnterior }}" selected>Cliente no disponible (#{{ $clienteAnterior }}): selecciona otro</option>
                                @endif
                                @foreach($clientes as $cli)
                                    <option value="{{ $cli->id }}" @selected((string) $clienteAnterior === (string) $cli->id)>{{ $cli->nombre }} ({{ $cli->correo }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Opcional. Puedes vender a Público General.</p>
                        </div>
                        <div>
                            <label for="venta-metodo" class="block text-xs font-semibold text-gray-300 mb-1">Pago presencial *</label>
                            <select id="venta-metodo" name="metodo_pago" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-yellow-400/60 outline-none">
                                @if(!in_array($metodoAnterior, ['Efectivo', 'Tarjeta', 'Transferencia'], true))
                                    <option value="{{ $metodoAnterior }}" selected>Método no válido: selecciona otro</option>
                                @endif
                                <option value="Efectivo" @selected($metodoAnterior === 'Efectivo')>Efectivo</option>
                                <option value="Tarjeta" @selected($metodoAnterior === 'Tarjeta')>Tarjeta</option>
                                <option value="Transferencia" @selected($metodoAnterior === 'Transferencia')>Transferencia</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="venta-notas" class="block text-xs font-semibold text-gray-300 mb-1">Notas / Observaciones</label>
                        <input id="venta-notas" type="text" name="notas" maxlength="255" value="{{ $notasAnteriores }}" placeholder="Ej: Pago exacto, voucher #1234"
                            class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-white placeholder-gray-500 focus:border-yellow-400/60 outline-none">
                    </div>
                </section>
            </div>

            <section id="venta-revision" aria-labelledby="venta-revision-titulo" class="hidden rounded-xl border border-yellow-400/30 bg-yellow-400/5 p-4">
                <h3 id="venta-revision-titulo" tabindex="-1" class="text-base font-bold text-yellow-400 mb-3">3. Confirma la venta</h3>
                <ul id="venta-resumen-items" class="space-y-2 text-sm"></ul>
                <p id="venta-resumen-cliente" class="text-xs text-gray-300 mt-4"></p>
                <p id="venta-resumen-pago" class="text-xs text-gray-300 mt-1"></p>
                <p id="venta-resumen-notas" class="text-xs text-gray-400 mt-1 break-words"></p>
                <p class="text-xs text-gray-400 mt-3">Al registrar, se descontarán las existencias y podrás imprimir o enviar el comprobante.</p>
            </section>

            <p id="venta-mensaje" role="status" aria-live="polite" class="text-xs text-amber-400 min-h-4"></p>
            <div class="venta-cobro flex flex-wrap items-center justify-between gap-3 p-3 border border-white/10 rounded-xl sticky bottom-2 z-10">
                <div>
                    <span class="text-xs text-gray-400">Total a cobrar:</span>
                    <p class="text-2xl font-black text-yellow-400" id="gran-total" aria-live="polite">$0.00</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button id="venta-editar" type="button" class="hidden alpha-btn-secondary px-4 py-2 text-xs font-semibold">Editar venta</button>
                    <button id="venta-revisar" type="button" disabled class="alpha-btn-primary px-4 py-2.5 text-sm font-semibold disabled:opacity-50">Revisar venta</button>
                    <button id="venta-registrar" type="submit" disabled class="hidden alpha-btn-primary px-4 py-2.5 text-xs font-semibold disabled:opacity-50">Registrar venta</button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script id="venta-config" type="application/json">{!! json_encode(['productos' => $catalogoVenta, 'itemsAnteriores' => $itemsAnteriores, 'recuperarVenta' => $recuperarVenta && $errors->any()], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@extends('layouts.app', ['active' => 'ventas'])
@section('title', 'Punto de Venta')

@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Punto de Venta / Mostrador</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                TPV Inventario
            </span>
        </div>
        <p class="text-gray-400 text-xs mt-1">Registro de ventas directas de suplementos, bebidas y mercancía</p>
    </div>

    <div class="flex items-center gap-3 self-end sm:self-auto">
        <button type="button" onclick="abrirModalVenta()"
            class="alpha-btn-primary px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-lg shadow-yellow-400/15 active:scale-95 transition-all">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Registrar Venta
        </button>
        <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
            class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
    </div>
</header>
@endsection

@section('content')
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
        <p class="text-2xl font-black text-white">{{ $productos->count() }}</p>
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
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-3 py-1.5">
                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $busqueda ?? '' }}" placeholder="Buscar #ticket, cliente..." class="bg-transparent text-xs text-white placeholder-gray-500 focus:outline-none w-36 sm:w-44">
            </div>
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-2.5 py-1.5">
                <select name="metodo" class="bg-transparent text-xs text-white focus:outline-none">
                    <option value="" class="bg-[#141416]">Todos los métodos</option>
                    <option value="Efectivo" class="bg-[#141416]" @selected(($metodoSeleccionado ?? '') === 'Efectivo')>Efectivo</option>
                    <option value="Tarjeta" class="bg-[#141416]" @selected(($metodoSeleccionado ?? '') === 'Tarjeta')>Tarjeta</option>
                    <option value="Transferencia" class="bg-[#141416]" @selected(($metodoSeleccionado ?? '') === 'Transferencia')>Transferencia</option>
                </select>
            </div>
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-3 py-1.5">
                <span class="text-[11px] text-gray-400 font-medium">Desde:</span>
                <input type="date" name="desde" value="{{ $desde }}" class="bg-transparent text-xs text-white focus:outline-none">
            </div>
            <div class="flex items-center gap-1.5 bg-black/40 border border-white/10 rounded-xl px-3 py-1.5">
                <span class="text-[11px] text-gray-400 font-medium">Hasta:</span>
                <input type="date" name="hasta" value="{{ $hasta }}" class="bg-transparent text-xs text-white focus:outline-none">
            </div>
            <button type="submit" class="alpha-btn-primary px-3.5 py-1.5 rounded-xl text-xs font-semibold">Filtrar</button>
            @if($desde || $hasta || !empty($busqueda) || !empty($metodoSeleccionado))
                <a href="{{ route('ventas.index') }}" class="alpha-btn-secondary px-3 py-1.5 rounded-xl text-xs">Limpiar</a>
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
            <p class="text-xs mt-1">Registra una nueva venta de mostrador usando el botón superior.</p>
        </div>
    @endif
</div>

{{-- MODAL NUEVA VENTA (TPV) --}}
<div id="modal-nueva-venta" aria-labelledby="titulo-nueva-venta" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 hidden">
    <div class="alpha-card bg-[#141416] border border-white/10 rounded-2xl w-full max-w-xl p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5 pb-3 border-b border-white/10">
            <h3 id="titulo-nueva-venta" class="text-base font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                Registrar Venta de Mostrador
            </h3>
            <button type="button" onclick="cerrarModalVenta()" aria-label="Cerrar registro de venta" class="text-gray-400 hover:text-white text-lg font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('ventas.store') }}" id="form-venta" class="space-y-4">
            @csrf
            <input type="hidden" name="venta_uuid" value="{{ old('venta_uuid', (string) \Illuminate\Support\Str::uuid()) }}">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="venta-cliente" class="block text-xs font-semibold text-gray-300 mb-1">Cliente (Opcional)</label>
                    <select id="venta-cliente" name="cliente_id" class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-yellow-400/60 outline-none">
                        <option value="">-- Público General --</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nombre }} ({{ $cli->correo }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="venta-metodo" class="block text-xs font-semibold text-gray-300 mb-1">Método de Pago *</label>
                    <select id="venta-metodo" name="metodo_pago" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-white focus:border-yellow-400/60 outline-none">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta">Tarjeta de Débito / Crédito</option>
                        <option value="Transferencia">Transferencia Bancaria</option>
                    </select>
                </div>
            </div>

            {{-- LÍNEAS DE PRODUCTOS --}}
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-2">Productos a Vender *</label>
                <div id="items-contenedor" class="space-y-2.5">
                    {{-- Fila inicial --}}
                    <div class="item-fila flex items-center gap-2 bg-black/40 border border-white/10 p-2.5 rounded-xl">
                        <div class="flex-1">
                            <select name="items[0][producto_id]" aria-label="Producto" required onchange="actualizarPrecioFila(this)"
                                class="select-producto w-full bg-black/60 border border-white/10 rounded-lg px-2.5 py-1.5 text-xs text-white focus:border-yellow-400/60 outline-none">
                                <option value="" data-precio="0" data-stock="0">-- Seleccionar producto --</option>
                                @foreach($productos as $prod)
                                    <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}" data-stock="{{ $prod->stock }}">
                                        {{ $prod->nombre }} - ${{ number_format($prod->precio, 2) }}
                                        @if($prod->stock <= 3)
                                            ⚠️ (¡Stock crítico: {{ $prod->stock }}!)
                                        @else
                                            (Stock: {{ $prod->stock }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-20">
                            <input type="number" name="items[0][cantidad]" aria-label="Cantidad" value="1" min="1" max="999" required
                                oninput="recalcularTotal()"
                                class="input-cantidad w-full bg-black/60 border border-white/10 rounded-lg px-2.5 py-1.5 text-xs text-center text-white focus:border-yellow-400/60 outline-none">
                        </div>
                        <div class="w-20 text-right text-xs font-bold text-yellow-400 fila-subtotal">
                            $0.00
                        </div>
                        <button type="button" onclick="eliminarFila(this)" title="Quitar producto"
                            class="w-7 h-7 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors">
                            &times;
                        </button>
                    </div>
                </div>

                <button type="button" onclick="agregarFila()"
                    class="mt-2 text-xs font-semibold text-yellow-400 hover:text-yellow-300 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Añadir otro producto
                </button>
            </div>

            <div>
                <label for="venta-notas" class="block text-xs font-semibold text-gray-300 mb-1">Notas / Observaciones</label>
                <input id="venta-notas" type="text" name="notas" maxlength="255" placeholder="Ej: Pago exacto, voucher #1234"
                    class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-white placeholder-gray-500 focus:border-yellow-400/60 outline-none">
            </div>

            {{-- TOTAL Y BOTONES --}}
            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                <div>
                    <span class="text-xs text-gray-400">Total a Cobrar:</span>
                    <p class="text-2xl font-black text-yellow-400" id="gran-total">$0.00</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="cerrarModalVenta()" class="alpha-btn-secondary px-4 py-2 text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="alpha-btn-primary px-6 py-2.5 text-xs font-semibold shadow-lg shadow-yellow-400/20">Confirmar Venta</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let filaIndex = 1;
    const catalogoProductos = @json($productos);

    function abrirModalVenta() {
        window.alphaAnimateModalOpen('#modal-nueva-venta');
    }

    function cerrarModalVenta() {
        window.alphaAnimateModalClose('#modal-nueva-venta');
    }

    function actualizarPrecioFila(selectEl) {
        const fila = selectEl.closest('.item-fila');
        const option = selectEl.options[selectEl.selectedIndex];
        const maxStock = parseInt(option.dataset.stock, 10) || 0;
        const inputCant = fila.querySelector('.input-cantidad');

        if (maxStock > 0) {
            inputCant.max = maxStock;
            if (parseInt(inputCant.value, 10) > maxStock) {
                inputCant.value = maxStock;
            }
        }
        recalcularTotal();
    }

    function recalcularTotal() {
        let granTotal = 0;
        document.querySelectorAll('.item-fila').forEach(fila => {
            const select = fila.querySelector('.select-producto');
            const option = select.options[select.selectedIndex];
            const precio = parseFloat(option?.dataset.precio) || 0;
            const cantidad = parseInt(fila.querySelector('.input-cantidad').value, 10) || 0;
            const subtotal = precio * cantidad;

            fila.querySelector('.fila-subtotal').textContent = `$${subtotal.toFixed(2)}`;
            granTotal += subtotal;
        });

        document.getElementById('gran-total').textContent = `$${granTotal.toFixed(2)}`;
    }

    function agregarFila() {
        const contenedor = document.getElementById('items-contenedor');
        const nuevaFila = document.createElement('div');
        nuevaFila.className = 'item-fila flex items-center gap-2 bg-black/40 border border-white/10 p-2.5 rounded-xl';

        nuevaFila.innerHTML = `
            <div class="flex-1">
                <select name="items[${filaIndex}][producto_id]" aria-label="Producto" required onchange="actualizarPrecioFila(this)"
                    class="select-producto w-full bg-black/60 border border-white/10 rounded-lg px-2.5 py-1.5 text-xs text-white focus:border-yellow-400/60 outline-none">
                    <option value="" data-precio="0" data-stock="0">-- Seleccionar producto --</option>
                </select>
            </div>
            <div class="w-20">
                <input type="number" name="items[${filaIndex}][cantidad]" aria-label="Cantidad" value="1" min="1" max="999" required
                    oninput="recalcularTotal()"
                    class="input-cantidad w-full bg-black/60 border border-white/10 rounded-lg px-2.5 py-1.5 text-xs text-center text-white focus:border-yellow-400/60 outline-none">
            </div>
            <div class="w-20 text-right text-xs font-bold text-yellow-400 fila-subtotal">
                $0.00
            </div>
            <button type="button" onclick="eliminarFila(this)" title="Quitar producto"
                class="w-7 h-7 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors">
                &times;
            </button>
        `;

        const select = nuevaFila.querySelector('.select-producto');
        catalogoProductos.forEach(producto => {
            const option = document.createElement('option');
            option.value = producto.id;
            option.dataset.precio = producto.precio;
            option.dataset.stock = producto.stock;
            const stockLabel = producto.stock <= 3 ? `⚠️ (¡Stock crítico: ${producto.stock}!)` : `(Stock: ${producto.stock})`;
            option.textContent = `${producto.nombre} - $${Number(producto.precio).toFixed(2)} ${stockLabel}`;
            select.append(option);
        });

        contenedor.appendChild(nuevaFila);
        filaIndex++;
    }

    function eliminarFila(btn) {
        const filas = document.querySelectorAll('.item-fila');
        if (filas.length > 1) {
            btn.closest('.item-fila').remove();
            recalcularTotal();
        } else if (window.showAlphaToast) {
            window.showAlphaToast('La venta debe contener al menos un producto.', 'info');
        }
    }
</script>
@endpush

@extends('layouts.app')

@section('title', 'Comprobante #' . str_pad($venta->id, 6, '0', STR_PAD_LEFT))

@section('content')
<div class="max-w-2xl mx-auto py-6">
    {{-- Acciones superiores no imprimibles --}}
    <div class="no-print flex flex-wrap items-center justify-between gap-4 mb-6 p-4 bg-white/5 border border-white/10 rounded-2xl">
        <a href="{{ route('ventas.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Volver al Mostrador
        </a>

        <div class="flex items-center gap-3">
            <button type="button" onclick="window.print()" class="alpha-btn-secondary px-4 py-2 text-sm flex items-center gap-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Imprimir Ticket
            </button>

            <button type="button" onclick="document.getElementById('modal-enviar-correo').showModal()" class="alpha-btn-primary px-4 py-2 text-sm flex items-center gap-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                Enviar por Correo
            </button>
        </div>
    </div>

    {{-- Tarjeta del Comprobante Imprimible --}}
    <div id="ticket-imprimible" class="bg-[#121418] border border-white/10 rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
        {{-- Marca de agua decorativa --}}
        <div class="absolute -right-12 -top-12 w-48 h-48 bg-yellow-400/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="text-center pb-6 border-b border-white/10">
            <h1 class="text-2xl font-black tracking-widest text-yellow-400 uppercase">ALPHA FITNESS</h1>
            <p class="text-xs uppercase tracking-widest text-gray-400 mt-1">Comprobante de Venta Electrónico</p>
            <p class="text-sm font-mono text-gray-300 mt-2">Ticket #{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 py-6 border-b border-white/10 text-xs">
            <div>
                <p class="text-gray-400">Fecha y Hora:</p>
                <p class="font-semibold text-white mt-0.5">{{ $venta->created_at->format('d/m/Y h:i A') }}</p>
                <p class="text-gray-400 mt-3">Cliente:</p>
                <p class="font-semibold text-white mt-0.5">{{ $venta->cliente ? $venta->cliente->nombre : 'Público general / Mostrador' }}</p>
                @if($venta->cliente && $venta->cliente->correo)
                    <p class="text-gray-400 font-mono text-[11px]">{{ $venta->cliente->correo }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-gray-400">Método de Pago:</p>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/10 text-yellow-400 mt-0.5">
                    {{ $venta->metodo_pago }}
                </span>
                <p class="text-gray-400 mt-3">Atendido por:</p>
                <p class="font-semibold text-white mt-0.5">{{ $venta->user ? $venta->user->name : 'Personal de Recepción' }}</p>
            </div>
        </div>

        {{-- Desglose de Productos --}}
        <div class="py-6 border-b border-white/10">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-gray-400 uppercase text-[10px] tracking-wider border-b border-white/10">
                        <th class="text-left pb-3 font-semibold">Cant. & Producto</th>
                        <th class="text-right pb-3 font-semibold">P. Unitario</th>
                        <th class="text-right pb-3 font-semibold">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($venta->detalles as $detalle)
                    <tr>
                        <td class="py-3">
                            <span class="font-bold text-yellow-400 mr-1.5">{{ $detalle->cantidad }}x</span>
                            <span class="text-white font-medium">{{ $detalle->producto->nombre ?? 'Producto' }}</span>
                        </td>
                        <td class="text-right py-3 text-gray-400 font-mono">
                            ${{ number_format($detalle->precio_unitario, 2) }}
                        </td>
                        <td class="text-right py-3 font-bold text-white font-mono">
                            ${{ number_format($detalle->subtotal, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Total --}}
        <div class="pt-6 flex items-center justify-between">
            <span class="text-sm uppercase tracking-wider text-gray-400 font-semibold">Total Pagado:</span>
            <span class="text-3xl font-black text-yellow-400 font-mono tracking-tight">
                ${{ number_format($venta->total, 2) }}
            </span>
        </div>

        @if($venta->notas)
        <div class="mt-6 p-3 bg-white/5 border border-white/10 rounded-xl text-xs text-gray-300">
            <strong class="text-white">Nota de venta:</strong> {{ $venta->notas }}
        </div>
        @endif

        <div class="text-center pt-8 mt-6 border-t border-white/5">
            <p class="text-xs font-semibold text-yellow-400/90">¡Gracias por tu compra en Alpha Fitness!</p>
            <p class="text-[11px] text-gray-500 mt-1">Cada repetición, cada serie y cada día cuenta.</p>
        </div>
    </div>
</div>

{{-- Modal para Enviar por Correo --}}
<dialog id="modal-enviar-correo" class="modal-alpha backdrop:bg-black/80 backdrop:backdrop-blur-sm bg-transparent p-4 max-w-md w-full">
    <div class="bg-[#121418] border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl text-white">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-white/10">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                Enviar Comprobante por Correo
            </h2>
            <button type="button" onclick="document.getElementById('modal-enviar-correo').close()" class="text-gray-400 hover:text-white">✕</button>
        </div>

        <form action="{{ route('ventas.enviar-correo', $venta) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="correo_destino" class="block text-xs font-semibold text-gray-300 mb-1.5">Correo Electrónico de Destino:</label>
                <input type="email" id="correo_destino" name="correo" required
                    value="{{ old('correo', $venta->cliente?->correo) }}"
                    placeholder="cliente@ejemplo.com"
                    class="alpha-input w-full px-4 py-3 rounded-xl bg-black/50 border border-white/10 text-white text-sm focus:border-yellow-400 focus:outline-none">
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-enviar-correo').close()" class="alpha-btn-secondary px-4 py-2 text-sm">
                    Cancelar
                </button>
                <button type="submit" class="alpha-btn-primary px-5 py-2 text-sm font-semibold flex items-center gap-2">
                    Enviar Ahora
                </button>
            </div>
        </form>
    </div>
</dialog>

<style>
@media print {
    .no-print, .alpha-topbar, .alpha-shell > header, nav, footer, .alpha-skip {
        display: none !important;
    }
    body, .alpha-workspace, .alpha-content, .alpha-shell {
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #ticket-imprimible {
        border: 1px solid #ddd !important;
        background: #ffffff !important;
        color: #000000 !important;
        box-shadow: none !important;
        padding: 20px !important;
        width: 100% !important;
        max-width: 480px !important;
        margin: 0 auto !important;
    }
    #ticket-imprimible * {
        color: #000000 !important;
        border-color: #eee !important;
    }
}
</style>
@endsection

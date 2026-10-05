@extends('layouts.app')
@section('title', ($tipo === 'ventas' ? 'Venta #' : 'Asistencia #').$registro->id)
@section('content')
@include('gestion.nav')
<a class="alpha-btn-secondary px-4 py-2 rounded-xl mb-5" href="{{ route('gestion.historial.'.$tipo.'.index') }}">Volver al historial</a>
<section class="alpha-card rounded-2xl p-5 mb-6"><p>Cliente: {{ $registro->cliente?->nombre ?? 'Sin cliente asociado' }}</p>
@if($tipo === 'ventas')<p>Total original: ${{ number_format((float) $registro->total, 2) }} · Método: {{ $registro->metodo_pago }}</p><ul class="list-disc pl-5 mt-3">@foreach($registro->detalles as $detalle)<li>{{ $detalle->producto?->nombre ?? 'Producto no disponible' }} · {{ $detalle->cantidad }} unidades · ${{ number_format((float) $detalle->subtotal, 2) }}</li>@endforeach</ul>@else<p>Entrada: {{ $registro->fecha_hora->format('d/m/Y H:i:s') }} · Salida: {{ $registro->fecha_salida?->format('d/m/Y H:i:s') ?? 'En curso' }}</p>@endif
@if($registro->anulada_en)<p class="mt-4">Anulada el {{ $registro->anulada_en->format('d/m/Y H:i:s') }} por empleado #{{ $registro->anulada_por ?? 'No disponible' }}.</p><p>Motivo: {{ $registro->motivo_anulacion }}</p>@endif
</section>
@unless($registro->anulada_en)
<form method="POST" action="{{ route('gestion.historial.'.$tipo.'.update', $registro->id) }}" class="alpha-card alpha-form rounded-2xl p-5 max-w-xl grid gap-4 mb-6">
@csrf @method('PUT')
@if($tipo === 'ventas')<p class="text-sm text-gray-400">Solo se corrige la nota. No se cambian total, productos, método de pago, cliente ni operador originales.</p><label>Nota<textarea name="notas" maxlength="255" rows="3">{{ old('notas', $registro->notas) }}</textarea></label>@else
<label>Entrada ({{ config('app.timezone') }})<input name="fecha_hora" type="datetime-local" value="{{ old('fecha_hora', $registro->fecha_hora->format('Y-m-d\TH:i')) }}" required></label>
<label>Salida (vacía si sigue dentro)<input name="fecha_salida" type="datetime-local" value="{{ old('fecha_salida', $registro->fecha_salida?->format('Y-m-d\TH:i')) }}"></label>
@endif
<label>Motivo de la corrección<input name="motivo" value="{{ old('motivo') }}" maxlength="255" required></label>
<p class="text-sm text-gray-400">No incluyas contraseñas, números de tarjeta ni datos médicos en notas o motivos.</p>
<button class="alpha-btn-primary px-4 py-3 rounded-xl">Guardar corrección</button>
</form>
@if($tipo !== 'ventas' || auth('web')->user()?->rol === 'Administrador')
<form method="POST" action="{{ route('gestion.historial.'.$tipo.'.anular', $registro->id) }}" class="alpha-card alpha-form rounded-2xl p-5 max-w-xl grid gap-4 mb-6" onsubmit="return confirm('¿Anular este registro erróneo? Se conservará su historial.{{ $tipo === 'ventas' ? ' Se restituirá el inventario, sin realizar reembolso.' : '' }}')">
@csrf @method('DELETE')<h2 class="font-semibold">Anular registro erróneo</h2>
@if($tipo === 'ventas')<p class="text-sm">Solo para una venta registrada por error. Restituye inventario una vez. No devuelve dinero y no sustituye el procedimiento de devolución ni un documento fiscal.</p>@endif
<label>Motivo de la anulación<input name="motivo" maxlength="255" required></label>
<label class="flex items-start gap-3"><input type="checkbox" name="confirmacion" value="1" required style="width:1rem; margin:0; flex-shrink:0"><span>Confirmo que este registro es erróneo y debe quedar anulado con su historial.</span></label>
<button class="alpha-btn-secondary px-4 py-3 rounded-xl">Anular registro</button>
</form>
@endif
@endunless
<section class="alpha-card rounded-2xl p-5"><h2 class="text-lg font-semibold mb-4">Bitácora de correcciones</h2>
@forelse($correcciones as $correccion)<article class="border-t border-white/10 py-4"><p>#{{ $correccion->id }} · {{ $correccion->created_at }} · {{ $correccion->accion }} · Empleado #{{ $correccion->actor_id ?? 'No disponible' }}</p><p>Motivo: {{ $correccion->motivo }}</p><details class="mt-2"><summary class="cursor-pointer">Ver valores anteriores y posteriores</summary><pre class="whitespace-pre-wrap break-all text-xs mt-3">Antes: {{ $correccion->antes }}
Después: {{ $correccion->despues }}</pre></details></article>@empty<p class="text-sm text-gray-400">No hay correcciones registradas.</p>@endforelse
<div class="mt-5">{{ $correcciones->links() }}</div></section>
@endsection

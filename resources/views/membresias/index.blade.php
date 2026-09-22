@extends('layouts.app', ['active' => 'membresias'])
@section('title', 'Membresías')
@section('content')
<p class="text-gray-400 text-sm mb-6">{{ $guard === 'cliente' ? 'Elige un plan y presenta el pago en recepción. Solicitar no activa la membresía.' : 'Consulta solicitudes y membresías. Solo la secretaria puede confirmar cobros y activaciones.' }}</p>
@if($guard === 'cliente')
<section class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
    @foreach($planes as $plan)<article class="alpha-card p-6"><h2 class="alpha-editorial text-2xl">{{ $plan->nombre }}</h2><p class="text-3xl font-semibold text-yellow-400 my-4">${{ number_format((float)$plan->precio, 2) }}</p><p class="text-sm text-gray-400">{{ $plan->duracion_dias }} días · {{ $plan->condiciones }}</p><form method="POST" action="{{ route('membresias.solicitar') }}" class="mt-5">@csrf<input type="hidden" name="plan_id" value="{{ $plan->id }}"><button class="alpha-btn-primary px-5 py-3 w-full">Solicitar este plan</button></form></article>@endforeach
</section>
@else
<form method="GET" class="alpha-card alpha-form p-5 mb-7 grid sm:grid-cols-3 gap-4"><label>Cliente<input name="q" value="{{ $busqueda }}" placeholder="Nombre o correo"></label><label>Estado<select name="estado"><option value="">Todos</option>@foreach(['pendiente','activada','cancelada'] as $opcion)<option value="{{ $opcion }}" @selected($estado === $opcion)>{{ ucfirst($opcion) }}</option>@endforeach</select></label><div class="flex items-end gap-3"><button class="alpha-btn-primary px-5 py-3">Filtrar</button><a href="{{ route('membresias.index') }}" class="alpha-btn-secondary px-5 py-3">Limpiar</a></div></form>
@endif
<div class="flex justify-between items-center mb-4"><h2 class="text-lg font-semibold">Solicitudes</h2><span class="text-xs text-gray-400">{{ $solicitudes->total() }} {{ $solicitudes->total() === 1 ? 'solicitud' : 'solicitudes' }}</span></div>
<div class="alpha-requests">
@forelse($solicitudes as $solicitud)
    <article class="alpha-card alpha-request">
        <div class="alpha-request-summary"><span class="alpha-status {{ $solicitud->estado === 'activada' ? 'alpha-status-active' : '' }}">{{ ucfirst($solicitud->estado) }}</span><h3>{{ $solicitud->plan_nombre }}</h3><p class="text-sm text-gray-400">{{ $solicitud->cliente->nombre }}</p><p class="alpha-request-price">${{ number_format((float)$solicitud->precio_acordado, 2) }} <span class="text-xs text-gray-400">USD / {{ $solicitud->duracion_dias }} días</span></p><p class="text-xs text-gray-400">Condiciones guardadas al solicitar.</p></div>
        <div class="alpha-request-detail">
        @if($solicitud->estado === 'pendiente' && auth('web')->user()?->rol === 'Secretaria')
            <h3 class="font-semibold">Confirmar pago y activar</h3><p class="text-xs text-gray-400 mt-2">Registra el cobro recibido en recepción para activar la membresía.</p>
            <form method="POST" action="{{ route('membresias.activar', $solicitud) }}" class="alpha-form">@csrf @method('PATCH')<div class="alpha-payment-fields"><label>Importe cobrado<input type="number" name="importe" min="0.01" step="0.01" value="{{ old('importe', $solicitud->precio_acordado) }}" required></label><label>Referencia opcional<input name="referencia" maxlength="100" value="{{ old('referencia') }}"></label></div><button class="alpha-btn-primary px-5 py-3">Confirmar pago y activar</button></form>
        @elseif($solicitud->estado === 'pendiente')
            <h3 class="font-semibold">Pendiente de confirmación</h3><p class="text-sm text-gray-400 mt-3">Presenta el pago en recepción. Secretaría confirmará el cobro y activará el plan.</p>
        @else
            <h3 class="font-semibold">{{ $solicitud->estado === 'activada' ? 'Solicitud activada' : 'Solicitud cancelada' }}</h3><p class="text-sm text-gray-400 mt-3">Consulta la vigencia y el importe en el historial de membresías.</p>
        @endif
        @if($solicitud->estado === 'pendiente' && ($guard === 'cliente' || auth('web')->user()?->rol === 'Secretaria'))<form method="POST" action="{{ route('membresias.solicitudes.cancelar', $solicitud) }}" class="mt-4">@csrf @method('PATCH')<button class="text-sm text-red-300">Cancelar solicitud</button></form>@endif
        @if($solicitud->membresia?->pago)<p class="text-xs text-gray-400 mt-4">Cobrado {{ $solicitud->membresia->pago->pagado_en->format('d/m/Y H:i') }} por {{ $solicitud->membresia->pago->registrador?->name ?? 'Personal anterior' }}</p>@endif
        </div>
    </article>
@empty
    <div class="alpha-card p-8 text-gray-400">No hay solicitudes con estos filtros.</div>
@endforelse
</div>
{{ $solicitudes->links() }}
<h2 class="text-lg font-semibold mt-8 mb-4">Historial de membresías</h2>
@can('administrar')
<section class="mt-10 pt-8 border-t border-white/10" data-animate="card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Configuración de Planes de Membresía</h2>
            <p class="text-xs text-gray-400 mt-1">Crea nuevas tarifas, modifica precios o activa/desactiva planes para los socios.</p>
        </div>
        <button type="button" onclick="document.getElementById('modal-nuevo-plan').classList.remove('hidden')" class="alpha-btn-primary px-4 py-2.5 text-sm font-semibold flex items-center gap-2">
            <span>+ Nuevo Plan</span>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($todosLosPlanes as $p)
            <div class="alpha-card p-5 border border-white/10 rounded-2xl flex flex-col justify-between {{ !$p->activo ? 'opacity-60 bg-white/[0.01]' : '' }}">
                <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-bold text-base text-white">{{ $p->nombre }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $p->activo ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                            {{ $p->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-yellow-400 mb-2">${{ number_format((float)$p->precio, 2) }}</p>
                    <p class="text-xs text-gray-400 mb-1">Duración: <strong class="text-white">{{ $p->duracion_dias }} días</strong></p>
                    @if($p->condiciones)
                        <p class="text-xs text-gray-500 italic">{{ $p->condiciones }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-5 pt-3 border-t border-white/5">
                    <button type="button" onclick="abrirEditarPlan({{ \Illuminate\Support\Js::from($p) }})" class="alpha-btn-secondary px-3 py-1.5 text-xs font-semibold flex-1">
                        Editar
                    </button>
                    <form method="POST" action="{{ route('planes.toggle', $p) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-xl border border-white/10 hover:border-white/20 text-gray-300 transition-colors">
                            {{ $p->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- Modales Nuevo / Editar Plan --}}
<div id="modal-nuevo-plan" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
    <div class="alpha-card border border-white/10 rounded-2xl max-w-md w-full p-6 bg-[#101216] shadow-2xl">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Crear Nuevo Plan de Membresía</h3>
            <button type="button" onclick="document.getElementById('modal-nuevo-plan').classList.add('hidden')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <form method="POST" action="{{ route('planes.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1">Nombre del Plan</label>
                <input type="text" name="nombre" placeholder="Ej: Plan Trimestral Pro" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1">Precio ($)</label>
                    <input type="number" step="0.01" min="0.01" name="precio" placeholder="25.00" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1">Duración (días)</label>
                    <input type="number" min="1" max="3650" name="duracion_dias" placeholder="30" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1">Condiciones / Beneficios</label>
                <input type="text" name="condiciones" placeholder="Ej: Acceso libre a pesas y cardio" class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
            </div>
            <div class="flex justify-end gap-3 pt-3">
                <button type="button" onclick="document.getElementById('modal-nuevo-plan').classList.add('hidden')" class="alpha-btn-secondary px-4 py-2 text-sm">Cancelar</button>
                <button type="submit" class="alpha-btn-primary px-5 py-2 text-sm font-semibold">Guardar Plan</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-editar-plan" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
    <div class="alpha-card border border-white/10 rounded-2xl max-w-md w-full p-6 bg-[#101216] shadow-2xl">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Editar Plan de Membresía</h3>
            <button type="button" onclick="document.getElementById('modal-editar-plan').classList.add('hidden')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <form id="form-editar-plan" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1">Nombre del Plan</label>
                <input type="text" id="edit-nombre" name="nombre" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1">Precio ($)</label>
                    <input type="number" step="0.01" min="0.01" id="edit-precio" name="precio" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1">Duración (días)</label>
                    <input type="number" min="1" max="3650" id="edit-duracion" name="duracion_dias" required class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1">Condiciones</label>
                <input type="text" id="edit-condiciones" name="condiciones" class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1">Estado</label>
                <select id="edit-activo" name="activo" class="w-full bg-black/60 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:border-yellow-400/60 outline-none">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-3">
                <button type="button" onclick="document.getElementById('modal-editar-plan').classList.add('hidden')" class="alpha-btn-secondary px-4 py-2 text-sm">Cancelar</button>
                <button type="submit" class="alpha-btn-primary px-5 py-2 text-sm font-semibold">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirEditarPlan(plan) {
        document.getElementById('form-editar-plan').action = `/membresias/planes/${plan.id}`;
        document.getElementById('edit-nombre').value = plan.nombre;
        document.getElementById('edit-precio').value = Number(plan.precio).toFixed(2);
        document.getElementById('edit-duracion').value = plan.duracion_dias;
        document.getElementById('edit-condiciones').value = plan.condiciones || '';
        document.getElementById('edit-activo').value = plan.activo ? '1' : '0';
        document.getElementById('modal-editar-plan').classList.remove('hidden');
    }
</script>
@endcan
@endsection

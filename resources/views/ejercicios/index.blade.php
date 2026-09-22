@extends('layouts.app', ['active' => 'ejercicios'])
@section('title', 'Ejercicios')
@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8 pb-4 sm:pb-6 border-b border-white/5" data-animate="header">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Ejercicios Populares</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                ★ Calificaciones
            </span>
        </div>
        <p class="text-gray-400 text-xs mt-1">Califica con estrellas tus ejercicios favoritos y descubre los más votados</p>
    </div>

    <div class="flex items-center gap-3 self-end sm:self-auto">
        @can('administrar')
            <button type="button" onclick="document.getElementById('modal-nuevo-ejercicio').classList.remove('hidden')"
                class="alpha-btn-primary px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 shadow-md shadow-yellow-400/10">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo Ejercicio
            </button>
        @endcan
        <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
            class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
    </div>
</header>
@endsection
@section('content')
{{-- BARRA DE FILTROS Y BÚSQUEDA --}}
<div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 mb-6 sm:mb-8" data-animate="card">
    {{-- Filtros por Grupo Muscular (desplazable en móvil) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 no-scrollbar whitespace-nowrap">
        <a href="{{ route('ejercicios.index', ['grupo' => 'Todos', 'q' => $busqueda]) }}"
            class="px-3.5 py-2 rounded-xl text-xs font-semibold border shrink-0 transition-all duration-150 {{ $grupoSeleccionado === 'Todos' ? 'bg-yellow-400 text-black border-yellow-400 shadow-md shadow-yellow-400/20' : 'bg-[#141414] text-gray-300 border-white/10 hover:border-white/30 hover:bg-white/5' }}">
            Todos
        </a>
        @foreach ($gruposMusculares as $grupo)
            <a href="{{ route('ejercicios.index', ['grupo' => $grupo, 'q' => $busqueda]) }}"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold border shrink-0 transition-all duration-150 {{ $grupoSeleccionado === $grupo ? 'bg-yellow-400 text-black border-yellow-400 shadow-md shadow-yellow-400/20' : 'bg-[#141414] text-gray-300 border-white/10 hover:border-white/30 hover:bg-white/5' }}">
                {{ $grupo }}
            </a>
        @endforeach
    </div>

    {{-- Buscador --}}
    <form method="GET" action="{{ route('ejercicios.index') }}" class="w-full md:w-72 relative shrink-0">
        <input type="hidden" name="grupo" value="{{ $grupoSeleccionado }}">
        <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar ejercicio..."
            class="w-full bg-[#141414] border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-yellow-400/60 transition-colors">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    </form>
</div>

<p class="text-xs text-gray-400 mb-3">{{ $ejercicios->total() }} ejercicios</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    @forelse ($ejercicios as $index => $ejercicio)
        <div class="alpha-card rounded-2xl p-5 border border-white/10 flex flex-col justify-between relative overflow-hidden group shadow-lg" data-animate="card">

            {{-- Top Ranking Badge & Estado --}}
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold {{ $index === 0 ? 'bg-yellow-400 text-black shadow-md shadow-yellow-400/20' : ($index === 1 ? 'bg-gray-300 text-black' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-white/5 text-gray-400 border border-white/5')) }}">
                        {{ $ejercicio->conteo_votos ? '#'.($ejercicios->firstItem() + $index).' Global' : 'Sin votos' }}
                    </span>
                    @if(! $ejercicio->activo)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-red-500/20 text-red-400 border border-red-500/30">
                            Inactivo
                        </span>
                    @endif
                </div>

                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-white/5 text-gray-300 border border-white/5">
                    {{ $ejercicio->grupo_muscular }}
                </span>
            </div>

            {{-- Visor de Imagen con Músculos Entrenados --}}
            <div class="relative w-full h-48 bg-black/60 rounded-xl overflow-hidden mb-4 border border-white/10 flex items-center justify-center p-2">
                @if($ejercicio->tiene_imagen)
                <img loading="lazy" id="img-ej-{{ $ejercicio->id }}" src="{{ $ejercicio->imagen_url }}" alt="{{ $ejercicio->nombre }}"
                    class="w-full h-full object-contain transition-all duration-300 select-none">
                @else <span class="text-sm text-gray-400">Imagen no disponible</span> @endif

                {{-- Botón para alternar músculos entrenados --}}
                @if ($ejercicio->tiene_imagen_musculos)
                    <button type="button" onclick="alternarMusculos({{ $ejercicio->id }}, {{ \Illuminate\Support\Js::from($ejercicio->imagen_url) }}, {{ \Illuminate\Support\Js::from($ejercicio->imagen_musculos_url) }})"
                        class="absolute bottom-2 right-2 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-black/80 hover:bg-yellow-400 hover:text-black text-yellow-400 border border-yellow-400/30 backdrop-blur-md transition-all active:scale-95 shadow-md flex items-center gap-1.5 z-10">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span id="btn-label-{{ $ejercicio->id }}">Ver Músculos</span>
                    </button>
                @endif
            </div>

            {{-- Información del Ejercicio --}}
            <div class="mb-4">
                <h3 class="text-base font-bold text-white group-hover:text-yellow-400 transition-colors line-clamp-1">
                    {{ $ejercicio->nombre }}
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $ejercicio->subgrupo ?: 'Ejercicio integral' }}
                </p>
            </div>

            {{-- SECCIÓN DE CALIFICACIÓN CON ESTRELLAS --}}
            <div class="pt-4 border-t border-white/5 bg-white/[0.02] -mx-5 -mb-5 p-5 rounded-b-2xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-400 font-medium">Calificación promedio:</span>
                    <span class="text-xs font-bold text-yellow-400 flex items-center gap-1">
                        ★ <span id="promedio-{{ $ejercicio->id }}">{{ number_format($ejercicio->promedio_estrellas, 1) }}</span>
                        <span class="text-gray-500 font-normal text-[11px]">(<span id="votos-{{ $ejercicio->id }}">{{ $ejercicio->conteo_votos }}</span> {{ $ejercicio->conteo_votos === 1 ? 'voto' : 'votos' }})</span>
                    </span>
                </div>

                {{-- Selector Interactivo de Estrellas --}}
                <div class="flex items-center justify-between mt-3 bg-black/40 p-2.5 rounded-xl border border-white/5">
                    <span class="text-[11px] text-gray-400 font-semibold">Tu voto:</span>
                    <div class="flex items-center gap-1 star-rating" data-ejercicio-id="{{ $ejercicio->id }}" data-current="{{ $ejercicio->mi_calificacion }}">
                        @for ($star = 1; $star <= 5; $star++)
                            <button type="button" onclick="calificarEjercicio({{ $ejercicio->id }}, {{ $star }})"
                                onmouseenter="hoverStars({{ $ejercicio->id }}, {{ $star }})"
                                onmouseleave="resetStars({{ $ejercicio->id }})"
                                class="star-btn p-1 text-base transition-transform duration-150 hover:scale-125 focus:outline-none {{ $star <= $ejercicio->mi_calificacion ? 'text-yellow-400' : 'text-gray-600 hover:text-yellow-300' }}"
                                title="Calificar con {{ $star }} estrella{{ $star > 1 ? 's' : '' }}">
                                ★
                            </button>
                        @endfor
                    </div>
                </div>

                {{-- Acciones de Administración --}}
                @can('administrar')
                    <div class="flex items-center justify-between gap-2 mt-3 pt-3 border-t border-white/5">
                        <button type="button" onclick="abrirEditarEjercicio({{ json_encode(['id' => $ejercicio->id, 'nombre' => $ejercicio->nombre, 'grupo_muscular' => $ejercicio->grupo_muscular, 'subgrupo' => $ejercicio->subgrupo]) }})"
                            class="text-xs text-yellow-400 hover:text-yellow-300 font-semibold flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            Editar
                        </button>
                        <form method="POST" action="{{ route('ejercicios.toggle', $ejercicio) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-semibold flex items-center gap-1 transition-colors {{ $ejercicio->activo ? 'text-red-400 hover:text-red-300' : 'text-emerald-400 hover:text-emerald-300' }}">
                                {{ $ejercicio->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

        </div>
    @empty
        <div class="col-span-full alpha-card rounded-2xl p-12 text-center">
            <div class="w-12 h-12 rounded-2xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center mx-auto mb-4 anim-icono">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No se encontraron ejercicios</h3>
            <p class="text-gray-400 text-xs mb-4">Intenta cambiar el filtro o el término de búsqueda.</p>
            <a href="{{ route('ejercicios.index') }}" class="alpha-btn-secondary px-4 py-2 rounded-xl text-xs font-semibold">
                Ver todos los ejercicios
            </a>
        </div>
    @endforelse
</div>
<div class="mt-5">{{ $ejercicios->links() }}</div>

@can('administrar')
{{-- MODAL NUEVO EJERCICIO --}}
<div id="modal-nuevo-ejercicio" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 hidden">
    <div class="alpha-card bg-[#141416] border border-white/10 rounded-2xl w-full max-w-lg p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5 pb-3 border-b border-white/10">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                Nuevo Ejercicio
            </h3>
            <button type="button" onclick="document.getElementById('modal-nuevo-ejercicio').classList.add('hidden')" class="text-gray-400 hover:text-white text-lg font-bold">&times;</button>
        </div>
        <form method="POST" action="{{ route('ejercicios.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Nombre del Ejercicio *</label>
                <input type="text" name="nombre" required maxlength="100" placeholder="Ej: Press militar con barra" class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-gray-500 focus:border-yellow-400/60 outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Grupo Muscular *</label>
                    <input type="text" name="grupo_muscular" list="grupos-lista" required maxlength="50" placeholder="Ej: Pecho, Hombros..." class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-gray-500 focus:border-yellow-400/60 outline-none">
                    <datalist id="grupos-lista">
                        <option value="Pecho">
                        <option value="Espalda">
                        <option value="Piernas">
                        <option value="Hombros">
                        <option value="Brazos">
                        <option value="Abdomen">
                        <option value="Glúteos">
                        <option value="Cardio">
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Subgrupo Muscular</label>
                    <input type="text" name="subgrupo" maxlength="50" placeholder="Ej: Deltoides anterior" class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-gray-500 focus:border-yellow-400/60 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Foto o Ilustración del Ejercicio</label>
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-yellow-400/10 file:text-yellow-400 hover:file:bg-yellow-400/20">
                <span class="block mt-1 text-[11px] text-gray-500">JPG, PNG o WebP · Máximo 8 MB</span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Diagrama de Músculos Trabajados</label>
                <input type="file" name="imagen_musculos" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-yellow-400/10 file:text-yellow-400 hover:file:bg-yellow-400/20">
                <span class="block mt-1 text-[11px] text-gray-500">JPG, PNG o WebP · Diagrama anatómico</span>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                <button type="button" onclick="document.getElementById('modal-nuevo-ejercicio').classList.add('hidden')" class="alpha-btn-secondary px-4 py-2 text-xs font-semibold">Cancelar</button>
                <button type="submit" class="alpha-btn-primary px-5 py-2 text-xs font-semibold">Guardar Ejercicio</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR EJERCICIO --}}
<div id="modal-editar-ejercicio" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 hidden">
    <div class="alpha-card bg-[#141416] border border-white/10 rounded-2xl w-full max-w-lg p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5 pb-3 border-b border-white/10">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                Editar Ejercicio
            </h3>
            <button type="button" onclick="document.getElementById('modal-editar-ejercicio').classList.add('hidden')" class="text-gray-400 hover:text-white text-lg font-bold">&times;</button>
        </div>
        <form id="form-editar-ejercicio" method="POST" action="" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Nombre del Ejercicio *</label>
                <input type="text" id="edit-nombre" name="nombre" required maxlength="100" class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:border-yellow-400/60 outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Grupo Muscular *</label>
                    <input type="text" id="edit-grupo" name="grupo_muscular" list="grupos-lista" required maxlength="50" class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:border-yellow-400/60 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1">Subgrupo Muscular</label>
                    <input type="text" id="edit-subgrupo" name="subgrupo" maxlength="50" class="w-full bg-black/60 border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white focus:border-yellow-400/60 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Reemplazar Foto del Ejercicio</label>
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-yellow-400/10 file:text-yellow-400 hover:file:bg-yellow-400/20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-300 mb-1">Reemplazar Diagrama de Músculos</label>
                <input type="file" name="imagen_musculos" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-yellow-400/10 file:text-yellow-400 hover:file:bg-yellow-400/20">
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                <button type="button" onclick="document.getElementById('modal-editar-ejercicio').classList.add('hidden')" class="alpha-btn-secondary px-4 py-2 text-xs font-semibold">Cancelar</button>
                <button type="submit" class="alpha-btn-primary px-5 py-2 text-xs font-semibold">Actualizar Ejercicio</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // Alternar vista entre foto del ejercicio y mapa muscular
    const estadoMusculos = {};
    function alternarMusculos(id, urlNormal, urlMusculos) {
        const img = document.getElementById(`img-ej-${id}`);
        const btnLabel = document.getElementById(`btn-label-${id}`);
        if (!img) return;

        img.style.transition = 'opacity 0.2s ease-in-out';
        img.style.opacity = '0.2';

        setTimeout(() => {
            const viendoMusculos = estadoMusculos[id] || false;
            if (viendoMusculos) {
                img.src = urlNormal;
                if (btnLabel) btnLabel.textContent = 'Ver Músculos';
                estadoMusculos[id] = false;
            } else {
                img.src = urlMusculos;
                if (btnLabel) btnLabel.textContent = 'Ver Ejercicio';
                estadoMusculos[id] = true;
            }
            img.style.opacity = '1';
        }, 120);
    }

    // Efectos Hover en Estrellas
    function hoverStars(ejercicioId, starCount) {
        const container = document.querySelector(`.star-rating[data-ejercicio-id="${ejercicioId}"]`);
        if (!container) return;
        const buttons = container.querySelectorAll('.star-btn');
        buttons.forEach((btn, index) => {
            if (index < starCount) {
                btn.classList.add('text-yellow-400');
                btn.classList.remove('text-gray-600');
            } else {
                btn.classList.remove('text-yellow-400');
                btn.classList.add('text-gray-600');
            }
        });
    }

    function resetStars(ejercicioId) {
        const container = document.querySelector(`.star-rating[data-ejercicio-id="${ejercicioId}"]`);
        if (!container) return;
        const currentRating = parseInt(container.dataset.current, 10) || 0;
        const buttons = container.querySelectorAll('.star-btn');
        buttons.forEach((btn, index) => {
            if (index < currentRating) {
                btn.classList.add('text-yellow-400');
                btn.classList.remove('text-gray-600');
            } else {
                btn.classList.remove('text-yellow-400');
                btn.classList.add('text-gray-600');
            }
        });
    }

    // Calificar con AJAX
    function calificarEjercicio(ejercicioId, estrellas) {
        const rating = document.querySelector(`.star-rating[data-ejercicio-id="${ejercicioId}"]`);
        if (rating.dataset.saving) return;
        rating.dataset.saving = 'true';
        rating.querySelectorAll('button').forEach(button => button.disabled = true);
        fetch(`/ejercicios/${ejercicioId}/calificar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ estrellas: estrellas })
        })
        .then(res => {
            if (!res.ok) throw new Error('Error al calificar');
            return res.json();
        })
        .then(data => {
            const container = document.querySelector(`.star-rating[data-ejercicio-id="${ejercicioId}"]`);
            if (container) {
                container.dataset.current = data.estrellas;
                resetStars(ejercicioId);
            }

            const promedioEl = document.getElementById(`promedio-${ejercicioId}`);
            const votosEl = document.getElementById(`votos-${ejercicioId}`);
            if (promedioEl) promedioEl.textContent = Number(data.promedio).toFixed(1);
            if (votosEl) votosEl.textContent = data.total_votos;

            if (window.showAlphaToast) {
                window.showAlphaToast(data.mensaje, 'success');
            }
        })
        .catch(err => {
            console.error(err);
            if (window.showAlphaToast) {
                window.showAlphaToast('No se pudo guardar la calificación', 'error');
            }
        }).finally(() => {
            delete rating.dataset.saving;
            rating.querySelectorAll('button').forEach(button => button.disabled = false);
        });
    }

    function abrirEditarEjercicio(ej) {
        document.getElementById('form-editar-ejercicio').action = `/ejercicios/${ej.id}`;
        document.getElementById('edit-nombre').value = ej.nombre;
        document.getElementById('edit-grupo').value = ej.grupo_muscular;
        document.getElementById('edit-subgrupo').value = ej.subgrupo || '';
        document.getElementById('modal-editar-ejercicio').classList.remove('hidden');
    }
</script>
@endpush

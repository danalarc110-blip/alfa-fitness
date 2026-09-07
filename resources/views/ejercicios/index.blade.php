<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Ejercicios Populares</title>

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
        @include('partials.sidebar', ['active' => 'ejercicios'])

        <div class="flex-1 flex flex-col min-w-0 px-4 sm:px-6 md:px-10 py-6 sm:py-8">

            {{-- CABECERA --}}
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
                    <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    </button>
                </div>
            </header>

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

            {{-- GRID DE EJERCICIOS POPULARES --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                @forelse ($ejercicios as $index => $ejercicio)
                    <div class="alpha-card rounded-2xl p-5 border border-white/10 flex flex-col justify-between relative overflow-hidden group shadow-lg" data-animate="card">
                        
                        {{-- Top Ranking Badge --}}
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold {{ $index === 0 ? 'bg-yellow-400 text-black shadow-md shadow-yellow-400/20' : ($index === 1 ? 'bg-gray-300 text-black' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-white/5 text-gray-400 border border-white/5')) }}">
                                @if ($index === 0) 🏆 TOP #1 @elseif ($index === 1) 🥈 TOP #2 @elseif ($index === 2) 🥉 TOP #3 @else #{{ $index + 1 }} Semanal @endif
                            </span>

                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-white/5 text-gray-300 border border-white/5">
                                {{ $ejercicio->grupo_muscular }}
                            </span>
                        </div>

                        {{-- Visor de Imagen con Músculos Entrenados --}}
                        <div class="relative w-full h-48 bg-black/60 rounded-xl overflow-hidden mb-4 border border-white/10 flex items-center justify-center p-2">
                            <img id="img-ej-{{ $ejercicio->id }}" src="{{ $ejercicio->imagen_url }}" alt="{{ $ejercicio->nombre }}"
                                class="w-full h-full object-contain transition-all duration-300 select-none">
                            
                            {{-- Botón para alternar músculos entrenados --}}
                            @if ($ejercicio->tiene_imagen_musculos)
                                <button type="button" onclick="alternarMusculos({{ $ejercicio->id }}, '{{ $ejercicio->imagen_url }}', '{{ $ejercicio->imagen_musculos_url }}')"
                                    class="absolute bottom-2 right-2 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-black/80 hover:bg-yellow-400 hover:text-black text-yellow-400 border border-yellow-400/30 backdrop-blur-md transition-all active:scale-95 shadow-md flex items-center gap-1">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M12 2v20"/></svg>
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

        </div>
    </div>

    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        // Alternar vista entre foto del ejercicio y mapa muscular (exclusivo para este módulo)
        const estadoMusculos = {};
        function alternarMusculos(id, urlNormal, urlMusculos) {
            const img = document.getElementById(`img-ej-${id}`);
            const btnLabel = document.getElementById(`btn-label-${id}`);
            if (!img) return;

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
                // Actualizar estado del contenedor de estrellas
                const container = document.querySelector(`.star-rating[data-ejercicio-id="${ejercicioId}"]`);
                if (container) {
                    container.dataset.current = data.estrellas;
                    resetStars(ejercicioId);
                }

                // Actualizar promedio y conteo de votos
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
            });
        }
    </script>
</body>
</html>

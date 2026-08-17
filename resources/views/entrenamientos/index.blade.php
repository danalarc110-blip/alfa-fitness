<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Entrenamientos</title>

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
    <div class="min-h-screen flex">
        @include('partials.sidebar', ['active' => 'entrenamientos'])

        <div class="flex-1 flex flex-col min-w-0 px-6 sm:px-10 py-8">
            <header class="flex items-center justify-between gap-4 flex-wrap mb-8 pb-6 border-b border-white/5" data-animate="header">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white mb-1">Entrenamientos</h1>
                    <p class="text-gray-400 text-xs">Gestiona, crea y visualiza tus planes de entrenamiento</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    </button>

                    <form method="POST" action="{{ route('entrenamientos.crear') }}">
                        @csrf
                        <button class="alpha-btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Crear nueva rutina
                        </button>
                    </form>
                </div>
            </header>
                </form>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse ($rutinas as $rutina)
                    <a href="{{ route('entrenamientos.editar', $rutina) }}"
                        class="alpha-card alpha-card-interactive group rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between"
                        data-animate="card" data-tilt>
                        
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <h2 class="font-bold text-lg text-white group-hover:text-yellow-400 transition-colors line-clamp-1">
                                    {{ $rutina->nombre }}
                                </h2>
                                <span class="px-2 py-0.5 rounded-md text-[10px] uppercase tracking-wider font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20 shrink-0">
                                    {{ $rutina->nivel ?? 'General' }}
                                </span>
                            </div>

                            <p class="text-xs text-gray-400 mb-6 line-clamp-2">
                                {{ $rutina->objetivo ?: 'Sin objetivo definido' }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-white/5 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                {{ $rutina->dias->count() }} días
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-yellow-400/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $rutina->totalEjercicios() }} ejercicios
                            </span>
                            <span class="text-yellow-400 opacity-0 group-hover:opacity-100 transition-opacity font-semibold">
                                Editar →
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full alpha-card rounded-2xl p-12 text-center" data-animate="card">
                        <div class="w-12 h-12 rounded-2xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center mx-auto mb-4 anim-icono">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 6.5 4 4M4 4l-1.5 1.5M4 4l2.5 2.5M17.5 17.5 20 20m0 0 1.5-1.5M20 20l-2.5-2.5M7 12h10M4.5 9v6M2 10.5v3M19.5 9v6M22 10.5v3M8 8l8 8"/></svg>
                        </div>
                        <h3 class="text-base font-bold text-white mb-1">Aún no tienes rutinas creadas</h3>
                        <p class="text-gray-400 text-xs mb-6 max-w-sm mx-auto">Haz clic en el botón superior para crear tu primera rutina personalizada.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</body>
</html>

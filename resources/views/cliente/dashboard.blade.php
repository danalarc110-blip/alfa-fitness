<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Mi cuenta</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <script>
        if (localStorage.getItem('alphaTema') === 'light') {
            document.documentElement.classList.add('light');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black min-h-screen text-white">
    @php
        $cliente = auth('cliente')->user();
        $avatarUrl = $cliente->avatar
            ? (filter_var($cliente->avatar, FILTER_VALIDATE_URL) ? $cliente->avatar : asset('images/avatars/'.$cliente->avatar))
            : null;
    @endphp

    <div class="min-h-screen flex">
        @include('partials.sidebar', [
            'active' => 'inicio',
            'guard' => 'cliente',
            'nombre' => $cliente->nombre,
            'rolEtiqueta' => 'Cliente',
            'avatarUrl' => $avatarUrl,
        ])

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between gap-4 flex-wrap px-6 sm:px-10 py-6 border-b border-white/5 backdrop-blur-sm" data-animate="header">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-white">Mi Panel</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20 shimmer-badge">
                        Miembro Alpha
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    </button>
                </div>
            </header>

            <div class="flex-1 px-6 sm:px-10 py-10 max-w-4xl mx-auto w-full space-y-8">
                <div class="alpha-card rounded-2xl p-8 sm:p-10 text-center relative overflow-hidden" data-animate="card">
                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-yellow-400/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="w-20 h-20 rounded-full overflow-hidden bg-white/10 flex items-center justify-center mx-auto mb-5 border-2 border-yellow-400/30 shadow-lg relative">
                        @if ($avatarUrl ?? null)
                            <img src="{{ $avatarUrl }}" alt="{{ $cliente->nombre }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-10 h-10 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                        @endif
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-white mb-2">
                        ¡Bienvenido, {{ $cliente->nombre }}!
                    </h1>
                    <p class="text-gray-400 text-sm max-w-md mx-auto mb-8">
                        Tu espacio de entrenamiento personal está listo. Consulta tus rutinas, monitorea tu progreso o actualiza tu perfil.
                    </p>

                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('entrenamientos.index') }}" class="alpha-btn-primary px-6 py-3 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6.5 6.5 4 4M4 4l-1.5 1.5M4 4l2.5 2.5M17.5 17.5 20 20m0 0 1.5-1.5M20 20l-2.5-2.5M7 12h10M4.5 9v6M2 10.5v3M19.5 9v6M22 10.5v3M8 8l8 8"/></svg>
                            Ver Entrenamientos
                        </a>
                        <a href="{{ route('configuracion') }}" class="alpha-btn-secondary px-6 py-3 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                            Configuración
                        </a>
                        <form method="POST" action="{{ route('cliente.salir') }}">
                            @csrf
                            <button type="submit" title="Sales del panel, pero tu sesión sigue activa" class="alpha-btn-secondary px-5 py-3 rounded-xl text-sm font-semibold">
                                Salir
                            </button>
                        </form>
                        <form method="POST" action="{{ route('cliente.logout') }}">
                            @csrf
                            <button type="submit" title="Cierra tu sesión por completo" class="bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-red-400 font-semibold px-5 py-3 rounded-xl text-sm transition-all duration-150 active:scale-95">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
    </div>
</body>
</html>

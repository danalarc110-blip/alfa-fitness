<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Dashboard</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <script>
        if (localStorage.getItem('alphaTema') === 'light') {
            document.documentElement.classList.add('light');
        }
    </script>

    <style>
        html.light .bg-black { background-color: #f1f0ec; }
        html.light .bg-black\/40 { background-color: #eae8e3; }
        html.light .bg-black\/30 { background-color: #eae8e3; }
        html.light .bg-\[\#141414\] { background-color: #f8f7f4; }
        html.light .bg-\[\#1a1a1a\] { background-color: #f8f7f4; }
        html.light .bg-white\/5 { background-color: #e8e6e1; }
        html.light .bg-white\/10 { background-color: #e0dfd8; }
        html.light .hover\:bg-white\/5:hover { background-color: #e0dfd8; }
        html.light .border-white\/10 { border-color: #ddd9d0; }
        html.light .border-white\/5 { border-color: #e6e3dc; }
        html.light .hover\:border-white\/20:hover { border-color: #c7c2b6; }
        html.light .text-white { color: #171717; }
        html.light .hover\:text-white:hover { color: #171717; }
        html.light .text-gray-300 { color: #4b5563; }
        html.light .text-gray-400 { color: #57606f; }
        html.light .text-gray-500 { color: #6b7280; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black text-white min-h-screen">

    <div class="min-h-screen flex">

        @include('partials.sidebar', ['active' => 'inicio'])

        <div class="flex-1 flex flex-col min-w-0">

            <header class="flex items-center justify-between gap-4 flex-wrap px-6 sm:px-10 py-6 border-b border-white/5 backdrop-blur-sm" data-animate="header">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-white">¡Bienvenido, {{ $nombre }}!</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20 shimmer-badge">
                            {{ $rolEtiqueta }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Panel de administración y control Alpha Fitness</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                        class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-200 active:scale-95 shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    </button>
                </div>
            </header>

            <div class="flex-1 px-6 sm:px-10 py-8 space-y-6">
                {{-- Quick Metrics / Actions Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="alpha-card rounded-2xl p-6 relative overflow-hidden" data-animate="card">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-yellow-400/5 rounded-full blur-2xl pointer-events-none"></div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Entrenamientos</span>
                            <div class="w-10 h-10 rounded-xl bg-yellow-400/10 flex items-center justify-center text-yellow-400 border border-yellow-400/20">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 6.5 4 4M4 4l-1.5 1.5M4 4l2.5 2.5M17.5 17.5 20 20m0 0 1.5-1.5M20 20l-2.5-2.5M7 12h10M4.5 9v6M2 10.5v3M19.5 9v6M22 10.5v3M8 8l8 8"/></svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-1">Rutinas</h3>
                        <p class="text-xs text-gray-400 mb-4">Gestiona y personaliza las rutinas de entrenamiento</p>
                        <a href="{{ route('entrenamientos.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-yellow-400 hover:text-yellow-300 transition-colors">
                            Ver catálogo de rutinas →
                        </a>
                    </div>

                    <div class="alpha-card rounded-2xl p-6 relative overflow-hidden" data-animate="card">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Configuración</span>
                            <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-300 border border-white/10">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-1">Mi Perfil</h3>
                        <p class="text-xs text-gray-400 mb-4">Actualiza tus datos personales y credenciales</p>
                        <a href="{{ route('configuracion') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-yellow-400 hover:text-yellow-300 transition-colors">
                            Ir a configuración →
                        </a>
                    </div>

                    <div class="alpha-card rounded-2xl p-6 relative overflow-hidden" data-animate="card">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Gimnasio</span>
                            <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-300 border border-white/10">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-1">Información</h3>
                        <p class="text-xs text-gray-400 mb-4">Consulta detalles, horarios y normas del gimnasio</p>
                        <a href="{{ route('informacion') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-yellow-400 hover:text-yellow-300 transition-colors">
                            Ver información →
                        </a>
                    </div>
                </div>

                {{-- Status Banner --}}
                <div class="alpha-card rounded-2xl p-8 text-center relative overflow-hidden" data-animate="card">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center mx-auto mb-4 anim-icono">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-white mb-1">Sesión iniciada con éxito</h2>
                    <p class="text-gray-400 text-sm max-w-md mx-auto">Selecciona una opción del menú lateral para comenzar a gestionar tu entrenamiento.</p>
                </div>
            </div>

        </div>
    </div>

    <script>
        function alphaToggleTema() {
            document.documentElement.classList.toggle('light');
            localStorage.setItem('alphaTema', document.documentElement.classList.contains('light') ? 'light' : 'dark');
        }
    </script>

</body>
</html>

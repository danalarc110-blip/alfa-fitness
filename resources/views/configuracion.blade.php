<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Configuración</title>

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
        html.light .border-\[\#141414\] { border-color: #f8f7f4; }
        html.light .text-white { color: #171717; }
        html.light .hover\:text-white:hover { color: #171717; }
        html.light .text-gray-300 { color: #4b5563; }
        html.light .hover\:text-gray-300:hover { color: #374151; }
        html.light .text-gray-400 { color: #57606f; }
        html.light .text-gray-500 { color: #6b7280; }
        html.light .text-gray-600 { color: #4b5563; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black text-white min-h-screen">

    <div class="min-h-screen flex">

        @include('partials.sidebar', ['active' => 'configuracion'])

        {{-- ===================== CONTENIDO ===================== --}}
        <div class="flex-1 flex flex-col min-w-0">

            <header class="flex items-center justify-between gap-4 flex-wrap px-6 sm:px-10 py-6">
                <div class="flex items-center gap-3">
                    <svg class="w-7 h-7 text-yellow-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                    <div>
                        <h1 class="text-xl font-bold leading-tight">Configuración</h1>
                        <p class="text-xs text-gray-500">Administra tu cuenta y preferencias</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="alphaToggleTema()" title="Cambiar a modo claro"
                        class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    </button>

                    <details class="relative">
                        <summary class="list-none flex items-center gap-2 cursor-pointer select-none">
                            <div class="w-9 h-9 rounded-full overflow-hidden bg-white/10 flex items-center justify-center shrink-0">
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $nombre }}" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                                @endif
                            </div>
                            <div class="leading-tight hidden sm:block">
                                <p class="text-sm font-semibold">{{ $nombre }}</p>
                                <p class="text-[11px] text-gray-500">{{ $rolEtiqueta }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="absolute right-0 mt-2 w-40 bg-[#1a1a1a] border border-white/10 rounded-lg overflow-hidden z-10">
                            <form method="POST" action="{{ route($guard === 'web' ? 'logout' : 'cliente.logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-red-500/10 transition-colors">
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <div class="flex-1 px-6 sm:px-10 py-6">

            @if (session('status'))
                <div class="mb-6 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-400">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid lg:grid-cols-2 gap-6">

                {{-- Perfil --}}
                <div class="alpha-card rounded-2xl p-6" data-animate="card">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                        <div>
                            <h2 class="font-bold text-base text-white">Perfil</h2>
                            <p class="text-xs text-gray-400">Personaliza tu información de cuenta</p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                            {{ $rolEtiqueta }}
                        </span>
                    </div>

                    <div class="flex items-center gap-4 mb-6">
                        <div class="relative shrink-0 group">
                            <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-yellow-400/30 bg-black/40 flex items-center justify-center shadow-lg">
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $nombre }}" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-9 h-9 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                                @endif
                            </div>
                            <label for="inputAvatar" class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-yellow-400 text-black flex items-center justify-center cursor-pointer border-2 border-[#141414] hover:scale-110 transition-transform shadow-md" title="Cambiar avatar">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </label>
                        </div>
                        <div>
                            <p class="font-bold text-white text-base">{{ $nombre }}</p>
                            <p class="text-xs text-gray-400">{{ $correo }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 bg-white/[0.02] p-4 rounded-xl border border-white/5 mb-5">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-yellow-400/80 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                            <div>
                                <p class="text-[11px] text-gray-400">Nombre registrado</p>
                                <p class="text-sm font-semibold text-white">{{ $nombre }}</p>
                            </div>
                        </div>
                        @if ($miembroDesde)
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-yellow-400/80 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <div>
                                    <p class="text-[11px] text-gray-400">Miembro desde</p>
                                    <p class="text-sm font-semibold text-white">{{ $miembroDesde }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-yellow-400/80 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                            <div>
                                <p class="text-[11px] text-gray-400">Correo electrónico</p>
                                <p class="text-sm font-semibold text-white">{{ $correo }}</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('configuracion.avatar') }}" enctype="multipart/form-data" id="formAvatar" class="pt-4 border-t border-white/5">
                        @csrf
                        <p class="text-xs font-semibold text-gray-300 mb-1">Cambiar foto de perfil</p>
                        <p class="text-[11px] text-gray-500 mb-3">Formatos permitidos: JPG, PNG (Máximo 2MB)</p>
                        <input type="file" name="avatar" id="inputAvatar" accept="image/png,image/jpeg" class="hidden" onchange="document.getElementById('formAvatar').submit()">
                        <label for="inputAvatar" class="alpha-btn-secondary cursor-pointer w-full flex items-center justify-center gap-2 text-xs font-semibold rounded-xl py-3 shadow-sm">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/></svg>
                            Seleccionar nueva imagen
                        </label>
                    </form>
                </div>

                {{-- Contraseña: no aplica a clientes que entran con Google --}}
                @unless ($esGoogle)
                    <div class="alpha-card rounded-2xl p-6" data-animate="card">
                        <div class="mb-4 pb-3 border-b border-white/5">
                            <h2 class="font-bold text-base text-white">Cambiar contraseña</h2>
                            <p class="text-xs text-gray-400">Mantén tu cuenta protegida con una contraseña segura</p>
                        </div>
                        <form method="POST" action="{{ route('configuracion.password') }}" class="space-y-3">
                            @csrf
                            <div class="relative">
                                <input type="password" name="password_actual" required placeholder="Contraseña actual"
                                    class="w-full bg-black/50 border border-white/10 rounded-xl py-3 pl-4 pr-10 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-yellow-400/60 transition-colors">
                                <button type="button" onclick="alphaToggleClave(this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="relative">
                                <input type="password" name="password" required minlength="8" placeholder="Nueva contraseña"
                                    class="w-full bg-black/50 border border-white/10 rounded-xl py-3 pl-4 pr-10 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-yellow-400/60 transition-colors">
                                <button type="button" onclick="alphaToggleClave(this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="relative">
                                <input type="password" name="password_confirmation" required minlength="8" placeholder="Confirmar nueva contraseña"
                                    class="w-full bg-black/50 border border-white/10 rounded-xl py-3 pl-4 pr-10 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-yellow-400/60 transition-colors">
                                <button type="button" onclick="alphaToggleClave(this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <button class="alpha-btn-primary w-full text-sm font-semibold px-4 py-3 rounded-xl mt-2">
                                Actualizar contraseña
                            </button>
                        </form>
                    </div>
                @else
                    <div class="alpha-card rounded-2xl p-6 flex items-start gap-4" data-animate="card">
                        <div class="w-10 h-10 rounded-xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-white mb-1">Autenticación con Google</h2>
                            <p class="text-sm text-gray-400">Iniciaste sesión con Google, por lo que tu seguridad y contraseña son gestionadas directamente por tu cuenta de Google.</p>
                        </div>
                    </div>
                @endunless

            </div>

            {{-- ===================== ESTADÍSTICAS RÁPIDAS ===================== --}}
            <div class="alpha-card rounded-2xl p-6 mt-6" data-animate="card">
                <div class="mb-4 pb-3 border-b border-white/5">
                    <h2 class="font-bold text-base text-white">Estadísticas de Actividad</h2>
                    <p class="text-xs text-gray-400">Resumen general de rendimiento</p>
                </div>

                @php
                    $stats = [
                        ['label' => 'Entrenamientos realizados', 'valor' => '12', 'delta' => '+20% este mes'],
                        ['label' => 'Calorías estimadas', 'valor' => '4,850 kcal', 'delta' => '+15% este mes'],
                        ['label' => 'Tiempo acumulado', 'valor' => '18h 30m', 'delta' => '+12% este mes'],
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    @foreach ($stats as $s)
                        <div class="bg-black/40 border border-white/5 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white">{{ $s['valor'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $s['label'] }}</p>
                            <span class="inline-flex items-center text-[11px] font-semibold text-yellow-400 mt-2 bg-yellow-400/10 px-2 py-0.5 rounded-md">
                                ▲ {{ $s['delta'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
    </div>

    <script>
        function alphaToggleClave(boton) {
            const input = boton.previousElementSibling;
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function alphaToggleTema() {
            document.documentElement.classList.toggle('light');
            localStorage.setItem('alphaTema', document.documentElement.classList.contains('light') ? 'light' : 'dark');
        }
    </script>

</body>
</html>

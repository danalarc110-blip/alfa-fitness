<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · Alpha Fitness</title>

    @include('partials.appearance')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black text-white min-h-screen">
    <a href="#contenido" class="alpha-skip">Saltar al contenido</a>
    <div class="min-h-screen flex flex-col md:flex-row">
        @include('partials.sidebar', ['active' => $active ?? 'inicio'])
        <main id="contenido" class="flex-1 min-w-0 p-4 sm:p-6 lg:p-10">
            @hasSection('page-header')
                @yield('page-header')
            @else
            <header class="flex items-center justify-between gap-4 mb-8">
                <div><p class="text-xs uppercase tracking-widest text-gray-400 mb-2">Alpha Fitness / @yield('eyebrow', 'Mi espacio')</p><h1 class="text-2xl sm:text-3xl font-bold tracking-tight">@yield('title', 'Inicio')</h1></div>
                <button type="button" onclick="alphaToggleTema()" class="alpha-btn-secondary rounded-xl px-4 py-2 text-sm" aria-label="Cambiar tema">◐ <span class="hidden sm:inline">Tema</span></button>
            </header>
            @endif
            @include('partials.feedback')
            @yield('content')
            <footer class="mt-10 pt-5 border-t border-white/10 text-xs text-gray-400 flex justify-between gap-3"><span>Alpha Fitness · Cada día cuenta.</span><a href="{{ route('informacion') }}">Información del gimnasio ↗</a></footer>
        </main>
    </div>
    @stack('scripts')
</body>
</html>

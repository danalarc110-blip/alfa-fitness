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
<body class="alpha-app font-sans antialiased bg-black text-white min-h-screen">
    <a href="#contenido" class="alpha-skip">Saltar al contenido</a>
    <div class="alpha-shell min-h-screen flex flex-col md:flex-row">
        @include('partials.sidebar', ['active' => $active ?? 'inicio'])
        <div class="alpha-workspace flex-1 min-w-0">
        @include('partials.topbar')
        <main id="contenido" class="alpha-content min-w-0 p-4 sm:p-6 lg:p-10">
            @hasSection('page-header')
                @yield('page-header')
            @else
            <header class="flex items-center justify-between gap-4 mb-8">
                <div><p class="text-xs uppercase tracking-widest text-gray-400 mb-2">Alpha Fitness / @yield('eyebrow', 'Mi espacio')</p><h1 class="text-2xl sm:text-3xl font-bold tracking-tight">@yield('title', 'Inicio')</h1></div>
            </header>
            @endif
            @include('partials.feedback')
            @yield('content')
            <footer class="mt-10 pt-5 border-t border-white/10 text-xs text-gray-400 flex flex-wrap justify-between gap-3"><span>Alpha Fitness · Cada día cuenta.</span><a href="{{ route('informacion') }}">Información del gimnasio ↗</a>@include('partials.legal-links')</footer>
            @include('partials.aviso-academico')
        </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>

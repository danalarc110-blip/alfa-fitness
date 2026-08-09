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

            <header class="flex items-center justify-between gap-4 flex-wrap px-6 sm:px-10 py-6">
                <div>
                    <h1 class="text-xl font-bold leading-tight">¡Bienvenido, {{ $nombre }}!</h1>
                    <p class="text-xs text-gray-500">Rol: {{ $rolEtiqueta }}</p>
                </div>

                <button type="button" onclick="alphaToggleTema()" title="Cambiar a modo claro"
                    class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>
            </header>

            <div class="flex-1 px-6 sm:px-10 py-6">
                <div class="bg-[#141414] border border-white/10 rounded-2xl p-8 text-center">
                    <p class="text-gray-400">Has iniciado sesión correctamente en Alpha Fitness.</p>
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

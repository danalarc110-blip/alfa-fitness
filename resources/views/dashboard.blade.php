<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black min-h-screen text-white">
    <div class="max-w-3xl mx-auto py-16 px-6 text-center">
        <h1 class="text-3xl font-bold mb-2">
            ¡Bienvenido, {{ auth()->user()->name }}!
        </h1>
        <p class="text-gray-400 mb-2">Rol: {{ auth()->user()->rol }}</p>
        <p class="text-gray-400 mb-8">Has iniciado sesión correctamente en Alpha Fitness.</p>

        <div class="flex items-center justify-center gap-3">
            <a href="{{ route('configuracion') }}" class="border border-white/10 hover:border-white/20 hover:bg-white/5 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                Configuración
            </a>
            <form method="POST" action="{{ route('salir') }}">
                @csrf
                <button type="submit" title="Sales del panel, pero tu sesión sigue activa" class="border border-white/10 hover:border-white/20 hover:bg-white/5 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                    Salir
                </button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Cierra tu sesión por completo" class="bg-yellow-400 hover:bg-yellow-300 text-black font-semibold px-6 py-3 rounded-xl">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</body>
</html>

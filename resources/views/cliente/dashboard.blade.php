<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Mi cuenta</title>
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
            <div class="max-w-3xl mx-auto py-16 px-6 text-center">
                <h1 class="text-3xl font-bold mb-2">
                    Bienvenido, {{ $cliente->nombre }}!
                </h1>
                <p class="text-gray-400 mb-8">Has iniciado sesion correctamente como cliente de Alpha Fitness.</p>

                <div class="flex items-center justify-center gap-3">
                    <a href="{{ route('configuracion') }}" class="border border-white/10 hover:border-white/20 hover:bg-white/5 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                        Configuracion
                    </a>
                    <form method="POST" action="{{ route('cliente.salir') }}">
                        @csrf
                        <button type="submit" title="Sales del panel, pero tu sesion sigue activa" class="border border-white/10 hover:border-white/20 hover:bg-white/5 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                            Salir
                        </button>
                    </form>
                    <form method="POST" action="{{ route('cliente.logout') }}">
                        @csrf
                        <button type="submit" title="Cierra tu sesion por completo, tambien en Google si entraste asi" class="bg-yellow-400 hover:bg-yellow-300 text-black font-semibold px-6 py-3 rounded-xl">
                            Cerrar sesion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Información</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black text-white min-h-screen">

    <div class="max-w-2xl mx-auto px-6 py-16">

        <a href="{{ route('login') }}" class="text-yellow-400 text-sm hover:text-yellow-300">&larr; Volver a inicio</a>

        <div class="flex items-center gap-3 mt-6 mb-8">
            <svg class="w-8 h-8 text-yellow-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 9v6M2 10v4M20 9v6M22 10v4M7 12h10M6 8v8M18 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1 class="text-3xl font-bold">Alpha Fitness</h1>
        </div>

        <p class="text-gray-400 mb-8">Tu mejor versión.</p>

        <div class="space-y-6 text-gray-200">
            <div>
                <h2 class="text-yellow-400 font-semibold mb-1">Horario</h2>
                <p class="text-sm text-gray-400">Lunes a viernes: 5:00 a.m. – 10:00 p.m.<br>Sábados: 6:00 a.m. – 6:00 p.m.</p>
            </div>
            <div>
                <h2 class="text-yellow-400 font-semibold mb-1">Ubicación</h2>
                <p class="text-sm text-gray-400">Actualiza aquí la dirección real del gimnasio.</p>
            </div>
            <div>
                <h2 class="text-yellow-400 font-semibold mb-1">Contacto</h2>
                <p class="text-sm text-gray-400">Actualiza aquí el teléfono o correo de contacto.</p>
            </div>
        </div>

    </div>

</body>
</html>

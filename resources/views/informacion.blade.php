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

        <a href="{{ route('login') }}" class="alpha-btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold mb-8">
            &larr; Volver al inicio
        </a>

        <div class="flex items-center gap-3 mb-2" data-animate="header">
            <div class="w-12 h-12 rounded-2xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center anim-icono">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 9v6M2 10v4M20 9v6M22 10v4M7 12h10M6 8v8M18 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight">Alpha Fitness</h1>
                <p class="text-xs text-yellow-400/90 font-medium">Tu mejor versión comienza aquí</p>
            </div>
        </div>

        <div class="space-y-4 text-gray-200 mt-8">
            <div class="alpha-card rounded-2xl p-6 relative overflow-hidden" data-animate="card">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-yellow-400/10 flex items-center justify-center text-yellow-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h2 class="text-white font-bold text-base">Horarios de Atención</h2>
                </div>
                <p class="text-sm text-gray-400 pl-11">
                    <strong class="text-gray-300">Lunes a Viernes:</strong> 5:00 a.m. – 10:00 p.m.<br>
                    <strong class="text-gray-300">Sábados:</strong> 6:00 a.m. – 6:00 p.m.<br>
                    <strong class="text-gray-300">Domingos y Feriados:</strong> 8:00 a.m. – 2:00 p.m.
                </p>
            </div>

            <div class="alpha-card rounded-2xl p-6 relative overflow-hidden" data-animate="card">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-yellow-400/10 flex items-center justify-center text-yellow-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <h2 class="text-white font-bold text-base">Ubicación e Instalaciones</h2>
                </div>
                <p class="text-sm text-gray-400 pl-11">
                    Equipamiento de alto rendimiento, zona de peso libre, cardio y entrenamiento funcional.
                </p>
            </div>

            <div class="alpha-card rounded-2xl p-6 relative overflow-hidden" data-animate="card">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-yellow-400/10 flex items-center justify-center text-yellow-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <h2 class="text-white font-bold text-base">Atención al Cliente</h2>
                </div>
                <p class="text-sm text-gray-400 pl-11">
                    ¿Dudas sobre planes o membresías? Escríbenos o consulta en recepción.
                </p>
            </div>
        </div>

    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Entrenamientos</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-black text-white min-h-screen">
    <div class="min-h-screen flex">
        @include('partials.sidebar', ['active' => 'entrenamientos'])

        <div class="flex-1 flex flex-col min-w-0 px-6 sm:px-10 py-8">
            <h1 class="text-2xl font-bold mb-1">Entrenamientos</h1>
            <p class="text-gray-500 text-sm mb-6">Tus rutinas de entrenamiento</p>

            <form method="POST" action="{{ route('entrenamientos.crear') }}" class="mb-6">
                @csrf
                <button class="bg-yellow-400 hover:bg-yellow-300 text-black font-semibold px-5 py-2.5 rounded-xl text-sm">
                    + Crear nueva rutina
                </button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($rutinas as $rutina)
                    <a href="{{ route('entrenamientos.editar', $rutina) }}"
                        class="bg-[#141414] border border-white/10 rounded-2xl p-5 hover:border-yellow-400/40 transition-colors">
                        <h2 class="font-semibold">{{ $rutina->nombre }}</h2>
                        <p class="text-xs text-gray-500 mb-3">{{ $rutina->objetivo }} · {{ $rutina->nivel }}</p>
                        <p class="text-[11px] text-gray-600">{{ $rutina->dias->count() }} días · {{ $rutina->totalEjercicios() }} ejercicios</p>
                    </a>
                @empty
                    <p class="text-gray-500 text-sm">Aún no tienes rutinas creadas.</p>
                @endforelse
            </div>
        </div>
    </div>
</body>
</html>

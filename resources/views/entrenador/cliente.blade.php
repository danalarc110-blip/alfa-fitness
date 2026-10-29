@extends('layouts.app', ['active' => 'mis-clientes'])
@section('title', 'Rutinas de '.$cliente->nombre)
@section('eyebrow', 'Seguimiento de entrenamiento')

@section('content')
<section class="space-y-5">
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('entrenador.clientes') }}" class="alpha-btn-secondary rounded-xl px-4 py-2.5">{{ $esAdmin ? 'Volver a clientes' : 'Volver a Mis clientes' }}</a>
        <a href="{{ route('entrenador.cliente.progreso', $cliente) }}" class="alpha-btn-primary rounded-xl px-4 py-2.5">Ver progreso</a>
    </div>
    <p class="text-sm text-gray-400">{{ $esAdmin ? 'Rutinas asignadas al cliente.' : 'Rutinas que has asignado a este cliente.' }}</p>
    @forelse ($rutinas as $rutina)
        <article class="alpha-card rounded-2xl p-5 sm:p-6" data-rutina-id="{{ $rutina->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold break-words">{{ $rutina->nombre }}</h2>
                    <p class="text-sm text-gray-400 mt-1">{{ $rutina->objetivo }} · {{ $rutina->nivel }} · {{ $rutina->dias_por_semana }} días por semana</p>
                </div>
                <span class="text-xs rounded-full bg-white/5 px-3 py-1 text-gray-300">{{ $rutina->activa ? 'Activa' : 'Archivada' }}</span>
            </div>
            @foreach ($rutina->dias as $dia)
                <details class="mt-4 border-t border-white/10 pt-3">
                    <summary class="cursor-pointer font-semibold text-sm text-yellow-400">{{ $dia->titulo }} · {{ $dia->ejercicios->count() }} ejercicios</summary>
                    <ul class="mt-3 space-y-2">
                        @forelse ($dia->ejercicios as $item)
                            <li class="text-sm text-gray-300 break-words">
                                <span class="font-semibold">{{ $item->ejercicio?->nombre ?? 'Ejercicio no disponible' }}</span>
                                <span class="text-gray-400">· {{ $item->series }} series · {{ $item->repeticiones }} repeticiones · {{ $item->peso_formateado }}</span>
                            </li>
                        @empty
                            <li class="text-sm text-gray-400">Sin ejercicios en este día.</li>
                        @endforelse
                    </ul>
                </details>
            @endforeach
        </article>
    @empty
        <p class="alpha-card rounded-2xl p-6 text-sm text-gray-400">Sin rutinas asignadas.</p>
    @endforelse
</section>
@endsection

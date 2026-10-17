@extends('layouts.app', ['active' => 'mis-clientes'])
@section('title', $esAdmin ? 'Clientes y entrenadores' : 'Mis clientes')
@section('eyebrow', 'Seguimiento de entrenamiento')

@section('content')
<section class="space-y-5">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4" data-animate="header">
        <div class="max-w-xl">
            <p class="text-sm text-gray-400">{{ $esAdmin ? 'Consulta quién entrena a cada cliente. Filtra por entrenador para revisar sus asignaciones.' : 'Aquí están los clientes a quienes has asignado rutinas. Puedes consultar sus entrenamientos y su progreso.' }}</p>
            <a href="{{ route('entrenamientos.index') }}" class="alpha-btn-secondary inline-block rounded-xl px-4 py-2 mt-3">Asignar una rutina desde Entrenamientos</a>
        </div>
        <form method="GET" action="{{ route('entrenador.clientes') }}" class="alpha-form flex flex-wrap gap-2 w-full lg:max-w-md" role="search">
            <label for="buscar-cliente" class="sr-only">Buscar cliente por nombre</label>
            <input id="buscar-cliente" name="q" type="search" value="{{ $busqueda }}" maxlength="100" placeholder="Buscar cliente por nombre" class="flex-1 min-w-0">
            @if ($esAdmin)
                <label for="filtro-entrenador" class="sr-only">Filtrar clientes por entrenador</label>
                <select id="filtro-entrenador" name="entrenador_id" class="w-full">
                    <option value="">Todos los entrenadores</option>
                    @foreach ($entrenadores as $entrenador)
                        <option value="{{ $entrenador->id }}" @selected($entrenadorId === $entrenador->id)>{{ $entrenador->name }}{{ $entrenador->activo ? '' : ' (Inactivo)' }}</option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="alpha-btn-primary rounded-xl px-4 py-2.5">Buscar</button>
            @if ($busqueda !== '' || $entrenadorId)
                <a href="{{ route('entrenador.clientes') }}" class="alpha-btn-secondary rounded-xl px-4 py-2.5">Limpiar</a>
            @endif
        </form>
    </div>
    <p class="text-xs text-gray-400" aria-live="polite">{{ $clientes->total() }} {{ $clientes->total() === 1 ? 'cliente' : 'clientes' }}</p>
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($clientes as $cliente)
            <article class="alpha-card rounded-2xl p-5 space-y-4" data-cliente-id="{{ $cliente->id }}" data-animate="card">
                <div class="flex gap-3 items-center">
                    <div class="w-12 h-12 rounded-xl bg-yellow-400/10 text-yellow-400 flex items-center justify-center shrink-0 overflow-hidden">
                        @if ($cliente->avatar_url)
                            <img src="{{ $cliente->avatar_url }}" alt="Foto de {{ $cliente->nombre }}" width="48" height="48" loading="lazy" class="w-full h-full object-cover">
                        @else
                            <span aria-hidden="true">{{ mb_strtoupper(mb_substr($cliente->nombre, 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-semibold break-words">{{ $cliente->nombre }}</h2>
                        <p class="text-xs text-gray-400">{{ $cliente->activo ? 'Activo' : 'Inactivo' }} · {{ $cliente->rutinas->count() }} {{ $cliente->rutinas->count() === 1 ? 'rutina asignada' : 'rutinas asignadas' }}</p>
                    </div>
                </div>
                <ul class="text-sm text-gray-300 space-y-1">
                    @forelse ($cliente->rutinas as $rutina)
                        <li class="break-words">
                            {{ $rutina->nombre }} <span class="text-xs text-gray-500">· {{ $rutina->activa ? 'Activa' : 'Archivada' }}</span>
                            @if ($esAdmin)
                                <span class="block text-xs text-gray-400">Asignada por: {{ $responsables->get($rutina->asignado_por_id) ?? 'Sin responsable vinculado' }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="text-gray-400">Sin rutinas asignadas</li>
                    @endforelse
                </ul>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('entrenador.cliente', $cliente) }}" class="alpha-btn-secondary rounded-xl px-3 py-2 text-sm" aria-label="Ver rutinas de {{ $cliente->nombre }}">Ver rutinas</a>
                    <a href="{{ route('entrenador.cliente.progreso', $cliente) }}" class="alpha-btn-primary rounded-xl px-3 py-2 text-sm" aria-label="Ver progreso de {{ $cliente->nombre }}">Ver progreso</a>
                </div>
            </article>
        @empty
            <div class="alpha-card rounded-2xl p-8 text-center sm:col-span-2 xl:col-span-3">
                <h2 class="font-semibold">{{ $busqueda !== '' ? 'No se encontraron clientes' : ($esAdmin ? 'No hay clientes para esta selección' : 'Todavía no tienes clientes asignados') }}</h2>
                <p class="text-sm text-gray-400 mt-2">{{ $busqueda !== '' ? 'Prueba con otra búsqueda.' : 'Al asignar una rutina desde la cuenta del entrenador en Entrenamientos, el cliente aparecerá aquí.' }}</p>
                @if ($busqueda === '' && !$entrenadorId)
                    <a href="{{ route('entrenamientos.index') }}" class="inline-block alpha-btn-secondary rounded-xl px-4 py-2 mt-4">Ir a Entrenamientos</a>
                @endif
            </div>
        @endforelse
    </div>
    @if ($clientes->hasPages())
        <div>{{ $clientes->links() }}</div>
    @endif
</section>
@endsection

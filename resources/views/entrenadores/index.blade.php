@extends('layouts.app', ['active' => 'entrenadores'])

@section('title', 'Entrenadores')
@section('eyebrow', 'Equipo Alpha')

@section('content')
    <section class="space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4" data-animate="header">
            <p class="max-w-2xl text-sm text-gray-400">
                Conoce a quienes acompañan el entrenamiento de nuestros miembros.
            </p>

            <form method="GET" action="{{ route('entrenadores.index') }}" class="alpha-form flex gap-3 w-full lg:max-w-md" role="search">
                <label for="buscar-entrenador" class="sr-only">Buscar entrenador</label>
                <input id="buscar-entrenador" type="search" name="q" value="{{ $busqueda }}" maxlength="100" placeholder="Buscar entrenador" class="flex-1">
                <button type="submit" class="alpha-btn-primary rounded-xl px-4 py-2.5">Buscar</button>
                @if ($busqueda !== '')
                    <a href="{{ route('entrenadores.index') }}" class="alpha-btn-secondary rounded-xl px-4 py-2.5">Limpiar</a>
                @endif
            </form>
        </div>

        @if ($esAdmin)
            <details class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card" @if ($errors->any()) open @endif>
                <summary class="font-semibold cursor-pointer text-white">Agregar entrenador</summary>
                <form method="POST" action="{{ route('entrenadores.store') }}" enctype="multipart/form-data" class="alpha-form grid sm:grid-cols-2 gap-4 mt-5">
                    @csrf
                    <label>
                        Nombre
                        <input name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
                    </label>
                    <label>
                        Correo
                        <input name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                    </label>
                    <label class="sm:col-span-2">
                        Foto opcional
                        <input name="avatar" type="file" accept="image/jpeg,image/png,image/webp" data-image-preview="trainer-new-preview">
                        <span class="block mt-1 text-[11px] text-gray-500">JPG, PNG o WebP · máximo 2 MB</span>
                    </label>
                    <img id="trainer-new-preview" class="hidden w-24 h-24 rounded-2xl object-cover" alt="Vista previa de la foto seleccionada">
                    <p class="sm:col-span-2 text-xs text-gray-400">
                        El entrenador recibirá un enlace privado y de un solo uso para crear su contraseña.
                    </p>
                    <div><button class="alpha-btn-primary px-5 py-3 rounded-xl">Crear e invitar</button></div>
                </form>
            </details>
        @endif

        <div class="flex items-center justify-between gap-4">
            <p class="text-xs text-gray-400" aria-live="polite">
                {{ $entrenadores->total() }} {{ $entrenadores->total() === 1 ? 'entrenador' : 'entrenadores' }}
            </p>
            @if ($entrenadores->lastPage() > 1)
                <span class="text-xs text-gray-500">Página {{ $entrenadores->currentPage() }} de {{ $entrenadores->lastPage() }}</span>
            @endif
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse ($entrenadores as $entrenador)
                <article class="alpha-card alpha-card-interactive rounded-2xl p-6" data-animate="card">
                    <div class="flex items-start justify-between gap-4">
                        <div class="w-20 h-20 rounded-2xl bg-yellow-400/10 flex items-center justify-center text-yellow-400 text-2xl font-bold overflow-hidden shrink-0">
                            @if ($entrenador->avatar_url)
                                <img src="{{ $entrenador->avatar_url }}" alt="{{ $entrenador->name }}" width="80" height="80" loading="lazy" decoding="async" class="w-full h-full object-cover">
                            @else
                                <span aria-hidden="true">{{ mb_strtoupper(mb_substr($entrenador->name, 0, 1)) }}</span>
                            @endif
                        </div>

                        @if ($esAdmin)
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $entrenador->activo ? 'bg-green-500/10 text-green-300' : 'bg-red-500/10 text-red-300' }}">
                                {{ $entrenador->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        @endif
                    </div>

                    <h2 class="text-xl font-semibold mt-5">{{ $entrenador->name }}</h2>
                    <p class="text-sm text-gray-400 mt-2">Entrenador de Alpha Fitness</p>

                    @if ($esAdmin)
                        <details class="mt-5 border-t border-white/10 pt-4">
                            <summary class="text-sm font-semibold text-yellow-400 cursor-pointer">Editar entrenador</summary>
                            <form method="POST" action="{{ route('entrenadores.update', $entrenador) }}" enctype="multipart/form-data" class="alpha-form space-y-3 mt-4">
                                @csrf
                                @method('PUT')
                                <label>Nombre<input name="name" value="{{ $entrenador->name }}" maxlength="255" required></label>
                                <label>Correo<input type="email" name="email" value="{{ $entrenador->email }}" maxlength="255" required></label>
                                <label>
                                    Acceso
                                    <select name="activo" required>
                                        <option value="1" @selected($entrenador->activo)>Activo</option>
                                        <option value="0" @selected(! $entrenador->activo)>Inactivo</option>
                                    </select>
                                </label>
                                <label>
                                    Cambiar foto
                                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-image-preview="trainer-{{ $entrenador->id }}-preview">
                                </label>
                                <img id="trainer-{{ $entrenador->id }}-preview" class="hidden w-24 h-24 rounded-xl object-cover" alt="Vista previa de la foto seleccionada">
                                @if ($entrenador->avatar)
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="eliminar_avatar" value="1">
                                        Eliminar foto actual
                                    </label>
                                @endif
                                <button class="alpha-btn-primary w-full rounded-xl px-4 py-3">Guardar cambios</button>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <div class="alpha-card rounded-2xl p-10 sm:col-span-2 xl:col-span-3 text-center" data-animate="card">
                    <h2 class="font-semibold text-white">No se encontraron entrenadores</h2>
                    <p class="text-sm text-gray-400 mt-2">{{ $busqueda ? 'Prueba con otra búsqueda.' : 'El equipo aparecerá aquí cuando esté registrado.' }}</p>
                </div>
            @endforelse
        </div>

        @if ($entrenadores->hasPages())
            <div>{{ $entrenadores->links() }}</div>
        @endif
    </section>
@endsection

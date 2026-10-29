@extends('layouts.app', ['active' => 'entrenadores'])

@section('title', 'Entrenadores')
@section('eyebrow', 'Equipo Alpha')

@section('content')
    <section class="space-y-5">
        @if ($guard === 'cliente')
            <section class="alpha-card rounded-2xl p-5 sm:p-6" aria-labelledby="mis-entrenadores-titulo">
                <h2 id="mis-entrenadores-titulo" class="text-lg font-semibold">Mis entrenadores</h2>
                <p class="text-sm text-gray-400 mt-1">Estos son los entrenadores que te han asignado rutinas.</p>
                <div class="flex flex-wrap gap-3 mt-4">
                    @forelse ($misEntrenadores as $miEntrenador)
                        <div class="flex items-center gap-3 rounded-xl border border-white/10 px-4 py-3 min-w-0 max-w-full" data-mi-entrenador="{{ $miEntrenador->id }}">
                            @if ($miEntrenador->avatar_url)
                                <img src="{{ $miEntrenador->avatar_url }}" alt="" width="40" height="40" class="w-10 h-10 rounded-full object-cover">
                            @endif
                            <span class="font-semibold break-words min-w-0">{{ $miEntrenador->name }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Todavía no tienes un entrenador vinculado. Solicita en recepción que un entrenador te asigne una rutina.</p>
                    @endforelse
                </div>
                @if ($misEntrenadores->isNotEmpty())
                    <a href="{{ route('entrenamientos.index') }}" class="alpha-btn-primary inline-block rounded-xl px-4 py-2 mt-4">Ver mis rutinas</a>
                @endif
            </section>
        @endif
        @if ($guard === 'web' && auth()->user()->rol === 'Entrenador')
            <div class="alpha-card rounded-2xl p-5 flex flex-wrap justify-between items-center gap-4">
                <div><h2 class="font-semibold">Tu espacio de entrenador</h2><p class="text-sm text-gray-400 mt-1">Consulta tus clientes, sus rutinas y su progreso.</p></div>
                <a href="{{ route('entrenador.clientes') }}" class="alpha-btn-primary rounded-xl px-4 py-2">Ver mis clientes</a>
            </div>
        @endif
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <p class="max-w-2xl text-sm text-gray-400">
                Conoce a quienes acompañan el entrenamiento de nuestros miembros.
            </p>

            <form method="GET" action="{{ route('entrenadores.index') }}" class="alpha-form flex flex-wrap gap-3 w-full lg:max-w-md" role="search">
                <label for="buscar-entrenador" class="sr-only">Buscar entrenador</label>
                <input id="buscar-entrenador" type="search" name="q" value="{{ $busqueda }}" maxlength="100" placeholder="Buscar entrenador" class="flex-1 min-w-0">
                <button type="submit" class="alpha-btn-primary rounded-xl px-4 py-2.5">Buscar</button>
                @if ($busqueda !== '')
                    <a href="{{ route('entrenadores.index') }}" class="alpha-btn-secondary rounded-xl px-4 py-2.5">Limpiar</a>
                @endif
            </form>
        </div>

        @if ($esAdmin)
            @if ($correoSinEntrega)
                <p role="status" class="rounded-xl border border-amber-400/30 bg-amber-400/10 p-4 text-sm">El correo está en modo de prueba: las invitaciones no se entregan. Configura el envío de correo del sistema para que el personal reciba su enlace de acceso.</p>
            @endif
            <div class="alpha-card rounded-2xl p-5 flex flex-wrap justify-between items-center gap-4">
                <div class="max-w-2xl">
                    <h2 class="font-semibold">Cuentas y clientes de los entrenadores</h2>
                    <p class="text-sm text-gray-400 mt-1">Cada entrenador tiene su propia cuenta. Crea su acceso aquí; recibirá un correo para elegir su contraseña e ingresar desde la pestaña Personal.</p>
                    <p class="text-sm text-gray-400 mt-2">Al asignar una rutina desde su cuenta, el cliente aparece en Mis clientes y puede ver quién lo entrena.</p>
                </div>
                <a href="{{ route('entrenador.clientes') }}" class="alpha-btn-secondary rounded-xl px-4 py-2">Ver clientes y entrenadores</a>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                @foreach (['Todos' => 'Todo el personal', 'Entrenador' => 'Entrenadores', 'Secretaria' => 'Secretarías'] as $rVal => $rLabel)
                    <a href="{{ route('entrenadores.index', ['rol' => $rVal, 'q' => $busqueda]) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-all {{ ($filtroRol ?? 'Todos') === $rVal ? 'bg-yellow-400 text-black border-yellow-400 shadow-md shadow-yellow-400/20' : 'bg-white/5 text-gray-300 border-white/10 hover:border-white/20' }}">
                        {{ $rLabel }}
                    </a>
                @endforeach
            </div>

            <details class="alpha-card rounded-2xl p-5 sm:p-6" @if ($errors->any()) open @endif>
                <summary class="font-semibold cursor-pointer text-white">Crear cuenta de entrenador o secretaría</summary>
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
                    <label>
                        Rol en el sistema
                        <select name="rol" required>
                            <option value="Entrenador" @selected(old('rol') === 'Entrenador')>Entrenador</option>
                            <option value="Secretaria" @selected(old('rol') === 'Secretaria')>Secretaria (Recepción y Caja)</option>
                        </select>
                    </label>
                    <label>
                        Foto opcional
                        <input name="avatar" type="file" accept="image/jpeg,image/png,image/webp" data-image-preview="trainer-new-preview">
                        <span class="block mt-1 text-[11px] text-gray-500">JPG, PNG o WebP · máximo 2 MB</span>
                    </label>
                    <img id="trainer-new-preview" class="hidden w-24 h-24 rounded-2xl object-cover" alt="Vista previa de la foto seleccionada">
                    <p class="sm:col-span-2 text-xs text-gray-400">
                        El colaborador recibirá un enlace privado y seguro para crear su contraseña.
                    </p>
                    <div class="sm:col-span-2"><button class="alpha-btn-primary px-5 py-3 rounded-xl">Crear e invitar</button></div>
                </form>
            </details>
        @endif

        <div class="flex items-center justify-between gap-4">
            <p class="text-xs text-gray-400" aria-live="polite">
                {{ $entrenadores->total() }} {{ $entrenadores->total() === 1 ? 'colaborador' : 'colaboradores' }}
            </p>
            @if ($entrenadores->lastPage() > 1)
                <span class="text-xs text-gray-500">Página {{ $entrenadores->currentPage() }} de {{ $entrenadores->lastPage() }}</span>
            @endif
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse ($entrenadores as $entrenador)
                <article class="alpha-card alpha-card-interactive rounded-2xl p-6" data-entrenador-id="{{ $entrenador->id }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="w-20 h-20 rounded-2xl bg-yellow-400/10 flex items-center justify-center text-yellow-400 text-2xl font-bold overflow-hidden shrink-0">
                            @if ($entrenador->avatar_url)
                                <img src="{{ $entrenador->avatar_url }}" alt="{{ $entrenador->name }}" width="80" height="80" loading="lazy" decoding="async" class="w-full h-full object-cover">
                            @else
                                <span aria-hidden="true">{{ mb_strtoupper(mb_substr($entrenador->name, 0, 1)) }}</span>
                            @endif
                        </div>

                        <div class="flex flex-col items-end gap-1.5">
                            @if ($esAdmin)
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $entrenador->rol === 'Secretaria' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-yellow-400/20 text-yellow-300 border border-yellow-400/30' }}">
                                    {{ $entrenador->rol }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $entrenador->activo ? 'bg-green-500/10 text-green-300' : 'bg-red-500/10 text-red-300' }}">
                                    {{ $entrenador->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                                @if (!$entrenador->password_establecida)
                                    <span class="text-[10px] text-amber-400/80 bg-amber-400/10 px-2 py-0.5 rounded-full">Invitación pendiente</span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <h2 class="text-xl font-semibold mt-5">{{ $entrenador->name }}</h2>
                    <p class="text-sm text-gray-400 mt-1">{{ $entrenador->rol === 'Secretaria' ? 'Recepción y Operaciones' : 'Entrenador de Alpha Fitness' }}</p>

                    @if ($guard === 'cliente' && in_array($entrenador->id, $vinculados, true))
                        <p class="inline-block rounded-full bg-yellow-400/10 text-yellow-400 px-3 py-1 text-xs font-semibold mt-3">Tu entrenador</p>
                    @endif

                    @if ($esAdmin)
                        <p class="text-sm text-gray-400 mt-3 break-all">{{ $entrenador->email }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $entrenador->password_establecida ? 'Contraseña establecida · acceso desde Personal' : 'Pendiente de crear su contraseña' }}</p>
                        <div class="flex flex-wrap gap-2 mt-4">
                            @if ($entrenador->rol === 'Entrenador')
                                <a href="{{ route('entrenador.clientes', ['entrenador_id' => $entrenador->id]) }}" class="alpha-btn-secondary rounded-xl px-3 py-2 text-sm" aria-label="Ver clientes de {{ $entrenador->name }}">Ver clientes</a>
                            @endif
                            @if ($entrenador->activo && !$entrenador->password_establecida)
                                <form method="POST" action="{{ route('entrenadores.invitar', $entrenador) }}">
                                    @csrf
                                    <button type="submit" class="alpha-btn-primary rounded-xl px-3 py-2 text-sm" aria-label="Reenviar invitación a {{ $entrenador->name }}">Reenviar invitación</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @if ($entrenador->rol === 'Entrenador')
                        <div class="mt-4 text-sm">
                            @if ($entrenador->conteo_votos > 0)
                                <span class="font-semibold text-yellow-400" aria-label="Promedio {{ number_format($entrenador->promedio_estrellas, 1) }} de 5 estrellas">★ {{ number_format($entrenador->promedio_estrellas, 1) }} / 5</span>
                                <span class="text-gray-400">· {{ $entrenador->conteo_votos }} {{ $entrenador->conteo_votos === 1 ? 'valoración' : 'valoraciones' }}</span>
                            @else
                                <p class="text-gray-400">Sin calificaciones</p>
                            @endif
                        </div>
                        @if ($guard === 'cliente')
                            @if (in_array($entrenador->id, $vinculados, true))
                                @php $miVoto = $misVotos->get($entrenador->id)?->estrellas ?? 0; @endphp
                                <form method="POST" action="{{ route('entrenadores.calificar', $entrenador) }}" class="mt-4">
                                    @csrf
                                    <fieldset>
                                        <legend class="text-xs text-gray-400 mb-2">{{ $miVoto ? 'Actualiza tu valoración' : 'Califica a tu entrenador' }}</legend>
                                        <div class="flex items-center gap-1 star-rating" data-entrenador-id="{{ $entrenador->id }}" data-current="{{ $miVoto }}">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <button type="submit" name="estrellas" value="{{ $star }}"
                                                    class="star-btn w-10 h-10 rounded-lg text-2xl transition-transform hover:scale-110 focus-visible:outline focus-visible:outline-2 focus-visible:outline-yellow-400 {{ $star <= $miVoto ? 'text-yellow-400' : 'text-gray-500 hover:text-yellow-300' }}"
                                                    aria-label="Calificar a {{ $entrenador->name }} con {{ $star }} {{ $star === 1 ? 'estrella' : 'estrellas' }}"
                                                    title="{{ $star }} {{ $star === 1 ? 'estrella' : 'estrellas' }}">★</button>
                                            @endfor
                                        </div>
                                        <p class="text-xs text-gray-400 mt-2">{{ $miVoto ? 'Tu valoración: '.$miVoto.' de 5 estrellas.' : 'Selecciona de 1 a 5 estrellas para guardar tu valoración.' }}</p>
                                    </fieldset>
                                </form>
                            @else
                                <p class="text-xs text-gray-400 mt-4">Podrás valorar a este entrenador cuando te asigne una rutina.</p>
                            @endif
                        @endif
                    @endif

                    @if ($esAdmin)
                        <details class="mt-5 border-t border-white/10 pt-4">
                            <summary class="text-sm font-semibold text-yellow-400 cursor-pointer">Editar personal</summary>
                            <form method="POST" action="{{ route('entrenadores.update', $entrenador) }}" enctype="multipart/form-data" class="alpha-form space-y-3 mt-4">
                                @csrf
                                @method('PUT')
                                <label>Nombre<input name="name" value="{{ $entrenador->name }}" maxlength="255" required></label>
                                <label>Correo<input type="email" name="email" value="{{ $entrenador->email }}" maxlength="255" required></label>
                                <label>
                                    Rol
                                    <select name="rol" required>
                                        <option value="Entrenador" @selected($entrenador->rol === 'Entrenador')>Entrenador</option>
                                        <option value="Secretaria" @selected($entrenador->rol === 'Secretaria')>Secretaria</option>
                                    </select>
                                </label>
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
                <div class="alpha-card rounded-2xl p-10 sm:col-span-2 xl:col-span-3 text-center">
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

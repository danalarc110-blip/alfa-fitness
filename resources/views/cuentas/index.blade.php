@extends('layouts.app', ['active' => 'cuentas'])

@section('title', 'Administrar cuentas')
@section('eyebrow', 'Usuarios')

@section('content')
    <section class="space-y-5">
        <div class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
                <p class="max-w-2xl text-sm text-gray-400">
                    Busca usuarios, consulta cuándo se registraron y controla únicamente su acceso.
                    El baneo no elimina su cuenta ni su historial.
                </p>

                <form method="GET" action="{{ route('cuentas.index') }}" class="alpha-form flex flex-col sm:flex-row gap-3 w-full lg:max-w-xl" role="search">
                    <label class="sr-only" for="buscar-usuario">Buscar usuario</label>
                    <input
                        id="buscar-usuario"
                        type="search"
                        name="q"
                        value="{{ $q }}"
                        maxlength="100"
                        placeholder="Buscar por nombre o correo"
                        class="flex-1"
                    >
                    <button class="alpha-btn-primary px-5 py-3 rounded-xl">Buscar</button>
                    @if ($q !== '')
                        <a href="{{ route('cuentas.index') }}" class="alpha-btn-secondary px-4 py-3 rounded-xl">Limpiar</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="flex items-center justify-between gap-4">
            <p class="text-xs text-gray-400" aria-live="polite">
                {{ $cuentas->total() }} {{ $cuentas->total() === 1 ? 'usuario encontrado' : 'usuarios encontrados' }}
            </p>
            @if ($cuentas->lastPage() > 1)
                <p class="text-xs text-gray-500">Página {{ $cuentas->currentPage() }} de {{ $cuentas->lastPage() }}</p>
            @endif
        </div>

        <div class="grid gap-3">
            @forelse ($cuentas as $cuenta)
                <article class="alpha-card rounded-2xl p-5 sm:p-6" data-animate="card">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <h2 class="font-semibold text-white truncate">{{ $cuenta->nombre }}</h2>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $cuenta->activo ? 'bg-green-500/10 text-green-300' : 'bg-red-500/10 text-red-300' }}">
                                    {{ $cuenta->activo ? 'Activo' : 'Baneado' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-400">
                                Registrado el
                                <time datetime="{{ $cuenta->created_at?->toDateString() }}">
                                    {{ $cuenta->created_at?->translatedFormat('d M Y') ?? 'Fecha no disponible' }}
                                </time>
                            </p>
                        </div>

                        @if ($cuenta->activo)
                            <form
                                method="POST"
                                action="{{ route('cuentas.banear', $cuenta) }}"
                                data-confirm="¿Banear a {{ $cuenta->nombre }}? Perderá el acceso en su próxima solicitud, pero su historial se conservará."
                                class="shrink-0"
                            >
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-semibold border border-red-400/25 bg-red-500/10 text-red-300 hover:bg-red-500/20">
                                    Banear usuario
                                </button>
                            </form>
                        @else
                            <form
                                method="POST"
                                action="{{ route('cuentas.restaurar', $cuenta) }}"
                                data-confirm="¿Restaurar el acceso de {{ $cuenta->nombre }}? Podrá volver a iniciar sesión."
                                class="shrink-0"
                            >
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="alpha-btn-secondary rounded-xl px-4 py-2.5 text-sm font-semibold">
                                    Restaurar acceso
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="alpha-card rounded-2xl p-10 text-center" data-animate="card">
                    <h2 class="font-semibold text-white">No se encontraron usuarios</h2>
                    <p class="mt-2 text-sm text-gray-400">Prueba con otro nombre o correo.</p>
                </div>
            @endforelse
        </div>

        @if ($cuentas->hasPages())
            <div>{{ $cuentas->links() }}</div>
        @endif
    </section>
@endsection

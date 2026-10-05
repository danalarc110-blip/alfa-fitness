<nav aria-label="Gestión" class="flex flex-wrap gap-3 mb-6 text-sm">
    @if(!auth('cliente')->check() && auth('web')->user()?->rol === 'Administrador')
        <a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.usuarios.index') }}">Usuarios</a>
        <a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.planes.index') }}">Planes</a>
    @endif
    @if(!auth('cliente')->check() && in_array(auth('web')->user()?->rol, ['Administrador', 'Secretaria'], true))
        <a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.clientes.index') }}">Clientes</a>
    @endif
    <a class="alpha-btn-secondary rounded-xl px-4 py-2" href="{{ route('gestion.sesiones.index') }}">Sesiones con entrenador</a>
</nav>

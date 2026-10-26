@php
    $guardMovil = $guard ?? (auth('cliente')->check() ? 'cliente' : 'web');
    $usuarioMovil = $guardMovil === 'cliente' ? auth('cliente')->user() : auth('web')->user();
    $inicioMovil = $guardMovil === 'cliente' ? 'cliente.dashboard' : 'dashboard';
    $enlacesMoviles = match ($guardMovil === 'cliente' ? 'Cliente' : $usuarioMovil?->rol) {
        'Administrador' => [[$inicioMovil, 'Inicio', 'inicio', 'home'], ['ventas.index', 'Ventas', 'ventas', 'cart'], ['entrenador.clientes', 'Clientes', 'mis-clientes', 'users'], ['analitica.index', 'Reportes', 'analitica', 'chart']],
        'Secretaria' => [[$inicioMovil, 'Inicio', 'inicio', 'home'], ['ventas.index', 'Ventas', 'ventas', 'cart'], ['asistencia.index', 'Asistencia', 'asistencia', 'check'], ['membresias.index', 'Membresías', 'membresias', 'badge']],
        'Entrenador' => [[$inicioMovil, 'Inicio', 'inicio', 'home'], ['entrenador.clientes', 'Mis clientes', 'mis-clientes', 'users'], ['entrenamientos.index', 'Rutinas', 'entrenamientos', 'activity'], ['ejercicios.index', 'Ejercicios', 'ejercicios', 'badge']],
        'Cliente' => [[$inicioMovil, 'Inicio', 'inicio', 'home'], ['entrenamientos.index', 'Rutinas', 'entrenamientos', 'activity'], ['progreso.index', 'Progreso', 'progreso', 'chart'], ['membresias.index', 'Mi plan', 'membresias', 'badge']],
        default => [[$inicioMovil, 'Inicio', 'inicio', 'home'], ['configuracion', 'Perfil', 'configuracion', 'badge']],
    };
    $iconosMoviles = [
        'home' => '<path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/>',
        'cart' => '<path d="M3 3h2l3 12h11l2-9H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
        'users' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 4a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v2"/>',
        'chart' => '<path d="M3 3v18h18M7 15l4-5 4 3 6-8"/>',
        'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'badge' => '<path d="m12 3 8 4v6c0 4-4 7-8 8-4-1-8-4-8-8V7Z"/><path d="m8 12 3 3 5-6"/>',
    ];
@endphp
<nav class="alpha-mobile-shortcuts" aria-label="Accesos rápidos">
    @foreach ($enlacesMoviles as [$ruta, $texto, $clave, $icono])
        <a href="{{ route($ruta) }}" @if (($active ?? 'inicio') === $clave) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">{!! $iconosMoviles[$icono] !!}</svg><span>{{ $texto }}</span>
        </a>
    @endforeach
</nav>

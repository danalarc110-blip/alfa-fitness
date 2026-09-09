@php
    $guardActual = $guard ?? (auth('web')->check() ? 'web' : 'cliente');
    $userActual = $guardActual === 'cliente' ? auth('cliente')->user() : auth()->user();
    $nombreActual = $nombre ?? ($userActual ? ($guardActual === 'cliente' ? $userActual->nombre : $userActual->name) : 'Usuario');
    $rolActual = $rolEtiqueta ?? ($userActual ? ($guardActual === 'cliente' ? 'Miembro' : ($userActual->rol ?? 'Usuario')) : 'Miembro');
    
    // Resuelve la URL del avatar de forma segura para URLs de Google o archivos locales
    $avatarUrlActual = $avatarUrl ?? ($userActual ? $userActual->avatar_url : null);
    if ($avatarUrlActual && !filter_var($avatarUrlActual, FILTER_VALIDATE_URL) && !str_starts_with($avatarUrlActual, 'http://') && !str_starts_with($avatarUrlActual, 'https://') && !str_starts_with($avatarUrlActual, '/')) {
        $avatarUrlActual = asset('images/avatars/' . $avatarUrlActual);
    }

    $inicioRoute = $guardActual === 'web' ? route('dashboard') : route('cliente.dashboard');
    $logoutRoute = $guardActual === 'web' ? route('logout') : route('cliente.logout');
    $salirRoute = $guardActual === 'web' ? route('salir') : route('cliente.salir');
    $navItems = [
        ['key' => 'inicio', 'label' => 'Inicio', 'href' => $inicioRoute, 'icon' => 'home'],
        ['key' => 'entrenamientos', 'label' => 'Entrenamientos', 'href' => route('entrenamientos.index'), 'icon' => 'dumbbell'],
        ['key' => 'ejercicios', 'label' => 'Ejercicios', 'href' => route('ejercicios.index'), 'icon' => 'activity'],
        ['key' => 'membresias', 'label' => 'Membresías', 'href' => route('membresias.index'), 'icon' => 'badge'],
        ['key' => 'asistencia', 'label' => 'Asistencia', 'href' => route('asistencia.index'), 'icon' => 'check-circle'],
        ['key' => 'entrenadores', 'label' => 'Entrenadores', 'href' => route('entrenadores.index'), 'icon' => 'users'],
        ['key' => 'productos', 'label' => 'Productos', 'href' => route('productos.index'), 'icon' => 'package'],
        ['key' => 'progreso', 'label' => 'Progreso', 'href' => route('progreso.index'), 'icon' => 'trending-up'],
        ['key' => 'configuracion', 'label' => 'Configuración', 'href' => route('configuracion'), 'icon' => 'settings'],
    ];

    $navItems[] = ['key' => 'cuentas', 'label' => 'Cuentas', 'href' => route('cuentas.index'), 'icon' => 'users'];
    $permisosNav = ['cuentas' => 'clientes', 'asistencia' => 'asistencia', 'membresias' => 'membresias', 'progreso' => 'progreso', 'estadisticas' => 'progreso'];
    $navItems = array_filter($navItems, fn ($item) => !isset($permisosNav[$item['key']]) || \App\Support\Acceso::permite($userActual, $permisosNav[$item['key']]));

    $icons = [
        'home' => '<path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>',
        'dumbbell' => '<path d="M6.5 6.5 4 4M4 4l-1.5 1.5M4 4l2.5 2.5M17.5 17.5 20 20m0 0 1.5-1.5M20 20l-2.5-2.5M7 12h10M4.5 9v6M2 10.5v3M19.5 9v6M22 10.5v3M8 8l8 8"/>',
        'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'trending-up' => '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
        'badge' => '<path d="M12 3 4 7v6c0 5 3.4 7.7 8 8 4.6-.3 8-3 8-8V7l-8-4Z"/><path d="M9 12l2 2 4-4"/>',
        'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-5"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'package' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'trophy' => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/><path d="M5 5H3v2a4 4 0 0 0 4 4"/><path d="M19 5h2v2a4 4 0 0 1-4 4"/>',
        'bar-chart' => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
    ];
@endphp

{{-- =========================================================
   BARRA SUPERIOR MÓVIL (Solo visible en celulares/tablets < md)
   ========================================================= --}}
<header id="alpha-mobile-header" class="md:hidden sticky top-0 z-30 flex items-center justify-between px-4 py-3 bg-black/90 backdrop-blur-md border-b border-white/10 select-none">
    <div class="flex items-center gap-3">
        <button type="button" id="alpha-hamburger-btn" onclick="alphaToggleMobileMenu()" aria-label="Abrir menú de navegación"
            class="alpha-hamburger-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="{{ $inicioRoute }}" class="flex items-center">
            <img src="{{ asset('images/logo-sidebar.png') }}" alt="Alpha Fitness" class="h-7 w-auto object-contain">
        </a>
    </div>

    <div class="flex items-center gap-2">
        <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
            class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
        <a href="{{ route('configuracion') }}" class="w-8 h-8 rounded-full overflow-hidden bg-white/10 border border-yellow-400/30 flex items-center justify-center shrink-0">
            @if ($avatarUrlActual)
                <img src="{{ $avatarUrlActual }}" alt="{{ $nombreActual }}" class="w-full h-full object-cover">
            @else
                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
            @endif
        </a>
    </div>
</header>

{{-- =========================================================
   MENÚ DESPLEGABLE MÓVIL (Off-Canvas Drawer con animación fluida)
   ========================================================= --}}
<div id="alpha-mobile-backdrop" onclick="alphaCloseMobileMenu()" class="md:hidden fixed inset-0 bg-black/75 backdrop-blur-sm z-40 opacity-0 pointer-events-none transition-opacity duration-300"></div>

<nav id="alpha-mobile-drawer" class="md:hidden fixed top-0 left-0 bottom-0 w-[280px] max-w-[85vw] bg-black/95 backdrop-blur-xl border-r border-white/10 z-50 p-5 flex flex-col justify-between transform -translate-x-full shadow-2xl overflow-y-auto" aria-label="Menú principal móvil">
    <div>
        {{-- Cabecera del Drawer --}}
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-white/10">
            <img src="{{ asset('images/logo-sidebar.png') }}" alt="Alpha Fitness" class="w-28 object-contain">
            <button type="button" onclick="alphaCloseMobileMenu()" aria-label="Cerrar menú"
                class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white flex items-center justify-center transition-all duration-150 active:scale-95">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Tarjeta de usuario en móvil --}}
        <div class="flex items-center gap-3 p-2.5 mb-4 bg-white/[0.04] rounded-xl border border-white/5">
            <div class="w-10 h-10 rounded-full overflow-hidden bg-white/10 flex items-center justify-center shrink-0 border border-yellow-400/25 shadow-inner">
                @if ($avatarUrlActual)
                    <img src="{{ $avatarUrlActual }}" alt="{{ $nombreActual }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                @endif
            </div>
            <div class="leading-tight min-w-0 flex-1">
                <p class="text-sm font-semibold truncate text-white">{{ $nombreActual }}</p>
                <span class="inline-block text-[10px] uppercase tracking-wider font-bold text-yellow-400 truncate">{{ $rolActual }}</span>
            </div>
        </div>

        {{-- Lista de navegación --}}
        <div class="flex flex-col gap-1">
            @foreach ($navItems as $item)
                @php $isActive = ($active ?? '') === $item['key']; @endphp
                <a href="{{ $item['href'] }}" @if($isActive) aria-current="page" @endif
                    onclick="alphaCloseMobileMenu()"
                    class="mobile-nav-link group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                        {{ $isActive
                            ? 'nav-item-active shadow-sm font-semibold'
                            : 'text-gray-400 hover:text-white hover:bg-white/[0.06] active:translate-x-1' }}">
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $isActive ? 'text-yellow-400' : 'text-gray-400 group-hover:text-yellow-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$item['icon']] !!}</svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Pie del drawer: tema y cerrar sesión --}}
    <div class="pt-4 mt-6 border-t border-white/10 space-y-2">
        <button type="button" onclick="alphaToggleTema()" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold bg-white/[0.04] hover:bg-white/[0.08] text-gray-300 hover:text-yellow-400 border border-white/5 transition-all duration-150">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                <span>Modo Claro / Oscuro</span>
            </span>
            <span class="text-[10px] uppercase font-bold text-yellow-400">Alternar</span>
        </button>

        <form method="POST" action="{{ $salirRoute }}">
            @csrf
            <button type="submit" title="Sales del panel, pero tu sesión sigue activa" class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150 active:scale-95">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Salir
            </button>
        </form>

        <form method="POST" action="{{ $logoutRoute }}">
            @csrf
            <button type="submit" title="Cierra tu sesión por completo" class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-red-400/90 hover:text-red-400 hover:bg-red-500/10 transition-all duration-150 active:scale-95">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</nav>

{{-- =========================================================
   SIDEBAR DE ESCRITORIO (Solo visible en pantallas >= md)
   ========================================================= --}}
<aside class="hidden md:flex flex-col w-[230px] shrink-0 bg-black/95 backdrop-blur-md border-r border-white/10 px-4 pt-6 pb-5 select-none" data-animate="sidebar">

    <div class="mb-8 px-2 flex items-center justify-between">
        <a href="{{ $inicioRoute }}">
            <img src="{{ asset('images/logo-sidebar.png') }}" alt="Alpha Fitness" class="w-32 object-contain -ml-1 hover:scale-105 transition-transform duration-300">
        </a>
    </div>

    <nav class="flex-1 flex flex-col gap-1.5">
        @foreach ($navItems as $item)
            @php $isActive = ($active ?? '') === $item['key']; @endphp
            <a href="{{ $item['href'] }}" @if($isActive) aria-current="page" @endif
                class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                    {{ $isActive
                        ? 'nav-item-active shadow-sm'
                        : 'text-gray-400 hover:text-white hover:bg-white/[0.06] hover:translate-x-1' }}">
                <svg class="w-4 h-4 shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $isActive ? 'text-yellow-400' : 'text-gray-400 group-hover:text-yellow-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$item['icon']] !!}</svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-4 pt-4 border-t border-white/10">
        {{-- Selector rápido de Modo Claro / Oscuro --}}
        <div class="px-2 mb-3">
            <button type="button" onclick="alphaToggleTema()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold bg-white/[0.04] hover:bg-white/[0.08] text-gray-400 hover:text-yellow-400 border border-white/5 transition-all duration-150">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    <span id="label-tema-sidebar">Cambiar Tema</span>
                </span>
                <span class="text-[10px] uppercase font-bold text-yellow-400/80">Claro / Oscuro</span>
            </button>
        </div>

        <div class="flex items-center gap-3 px-2 mb-3 bg-white/[0.03] p-2 rounded-xl border border-white/5">
            <div class="w-9 h-9 rounded-full overflow-hidden bg-white/10 flex items-center justify-center shrink-0 border border-yellow-400/20 shadow-inner">
                @if ($avatarUrlActual)
                    <img src="{{ $avatarUrlActual }}" alt="{{ $nombreActual }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                @endif
            </div>
            <div class="leading-tight min-w-0">
                <p class="text-sm font-semibold truncate text-white">{{ $nombreActual }}</p>
                <span class="inline-block text-[10px] uppercase tracking-wider font-semibold text-yellow-400/90 truncate">{{ $rolActual }}</span>
            </div>
        </div>
        <form method="POST" action="{{ $salirRoute }}">
            @csrf
            <button type="submit" title="Sales del panel, pero tu sesión sigue activa" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-gray-400 hover:text-white hover:bg-white/5 transition-all duration-150 active:scale-95">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Salir
            </button>
        </form>
        <form method="POST" action="{{ $logoutRoute }}">
            @csrf
            <button type="submit" title="Cierra tu sesión por completo" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-red-400/80 hover:text-red-400 hover:bg-red-500/10 transition-all duration-150 active:scale-95">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>

<script>
    function alphaOpenMobileMenu() {
        const drawer = document.getElementById('alpha-mobile-drawer');
        const backdrop = document.getElementById('alpha-mobile-backdrop');
        const btn = document.getElementById('alpha-hamburger-btn');
        if (!drawer || !backdrop) return;

        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        backdrop.classList.add('opacity-100', 'pointer-events-auto');

        drawer.classList.remove('-translate-x-full');
        drawer.classList.add('translate-x-0');

        if (btn) { btn.classList.add('is-active'); btn.setAttribute('aria-expanded', 'true'); }
        drawer.inert = false;
        drawer.querySelector('button, a')?.focus();
        document.body.style.overflow = 'hidden';
    }

    function alphaCloseMobileMenu() {
        const drawer = document.getElementById('alpha-mobile-drawer');
        const backdrop = document.getElementById('alpha-mobile-backdrop');
        const btn = document.getElementById('alpha-hamburger-btn');
        if (!drawer || !backdrop) return;

        backdrop.classList.remove('opacity-100', 'pointer-events-auto');
        backdrop.classList.add('opacity-0', 'pointer-events-none');

        drawer.classList.remove('translate-x-0');
        drawer.classList.add('-translate-x-full');

        const wasOpen = btn?.classList.contains('is-active');
        if (btn) { btn.classList.remove('is-active'); btn.setAttribute('aria-expanded', 'false'); }
        drawer.inert = true;
        if (wasOpen) btn?.focus();
        document.body.style.overflow = '';
    }

    function alphaToggleMobileMenu() {
        const drawer = document.getElementById('alpha-mobile-drawer');
        if (!drawer) return;
        if (drawer.classList.contains('translate-x-0')) {
            alphaCloseMobileMenu();
        } else {
            alphaOpenMobileMenu();
        }
    }

    // Cerrar menú con la tecla Escape o al cambiar a pantalla de escritorio
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') alphaCloseMobileMenu();
        const drawer = document.getElementById('alpha-mobile-drawer');
        if (e.key === 'Tab' && drawer?.classList.contains('translate-x-0')) {
            const items = drawer.querySelectorAll('a[href], button:not([disabled])');
            const first = items[0], last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last?.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first?.focus(); }
        }
    });
    document.getElementById('alpha-mobile-drawer').inert = true;
    document.getElementById('alpha-hamburger-btn').setAttribute('aria-expanded', 'false');
    document.getElementById('alpha-hamburger-btn').setAttribute('aria-controls', 'alpha-mobile-drawer');

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            alphaCloseMobileMenu();
        }
    });
</script>

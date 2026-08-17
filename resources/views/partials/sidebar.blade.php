@php
    $inicioRoute = ($guard ?? 'web') === 'web' ? route('dashboard') : route('cliente.dashboard');
    $logoutRoute = ($guard ?? 'web') === 'web' ? route('logout') : route('cliente.logout');
    $salirRoute = ($guard ?? 'web') === 'web' ? route('salir') : route('cliente.salir');

    $navItems = [
        ['key' => 'inicio', 'label' => 'Inicio', 'href' => $inicioRoute, 'icon' => 'home'],
        ['key' => 'entrenamientos', 'label' => 'Entrenamientos', 'href' => route('entrenamientos.index'), 'icon' => 'dumbbell'],
        ['key' => 'ejercicios', 'label' => 'Ejercicios', 'href' => '#', 'icon' => 'activity'],
        ['key' => 'progreso', 'label' => 'Progreso', 'href' => '#', 'icon' => 'trending-up'],
        ['key' => 'calendario', 'label' => 'Calendario', 'href' => '#', 'icon' => 'calendar'],
        ['key' => 'historial', 'label' => 'Historial', 'href' => '#', 'icon' => 'clock'],
        ['key' => 'estadisticas', 'label' => 'Estadísticas', 'href' => '#', 'icon' => 'bar-chart'],
        ['key' => 'configuracion', 'label' => 'Configuración', 'href' => route('configuracion'), 'icon' => 'settings'],
    ];

    $icons = [
        'home' => '<path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>',
        'dumbbell' => '<path d="M6.5 6.5 4 4M4 4l-1.5 1.5M4 4l2.5 2.5M17.5 17.5 20 20m0 0 1.5-1.5M20 20l-2.5-2.5M7 12h10M4.5 9v6M2 10.5v3M19.5 9v6M22 10.5v3M8 8l8 8"/>',
        'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'trending-up' => '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'bar-chart' => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
    ];
@endphp

<aside class="hidden md:flex flex-col w-[230px] shrink-0 bg-black/95 backdrop-blur-md border-r border-white/10 px-4 pt-6 pb-5 select-none" data-animate="sidebar">

    <div class="mb-8 px-2 flex items-center justify-between">
        <img src="{{ asset('images/logo-sidebar.png') }}" alt="Alpha Fitness" class="w-32 object-contain -ml-1 hover:scale-105 transition-transform duration-300">
    </div>

    <nav class="flex-1 flex flex-col gap-1.5">
        @foreach ($navItems as $item)
            @php $isActive = ($active ?? '') === $item['key']; @endphp
            <a href="{{ $item['href'] }}"
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
                @if ($avatarUrl ?? null)
                    <img src="{{ $avatarUrl }}" alt="{{ $nombre }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                @endif
            </div>
            <div class="leading-tight min-w-0">
                <p class="text-sm font-semibold truncate text-white">{{ $nombre ?? '' }}</p>
                <span class="inline-block text-[10px] uppercase tracking-wider font-semibold text-yellow-400/90 truncate">{{ $rolEtiqueta ?? '' }}</span>
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

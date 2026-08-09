@php
    $inicioRoute = ($guard ?? 'web') === 'web' ? route('dashboard') : route('cliente.dashboard');
    $logoutRoute = ($guard ?? 'web') === 'web' ? route('logout') : route('cliente.logout');

    $navItems = [
        ['key' => 'inicio', 'label' => 'Inicio', 'href' => $inicioRoute, 'icon' => 'home'],
        ['key' => 'entrenamientos', 'label' => 'Entrenamientos', 'href' => '#', 'icon' => 'dumbbell'],
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

<aside class="hidden md:flex flex-col w-[220px] shrink-0 bg-black border-r border-white/10 px-4 pt-6 pb-5">

    <div class="mb-8 px-2">
        <img src="{{ asset('images/logo-sidebar.png') }}" alt="Alpha Fitness" class="w-32 object-contain -ml-1">
    </div>

    <nav class="flex-1 flex flex-col gap-1">
        @foreach ($navItems as $item)
            @php $isActive = ($active ?? '') === $item['key']; @endphp
            <a href="{{ $item['href'] }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                    {{ $isActive
                        ? 'bg-yellow-400/10 text-yellow-400 font-semibold border-l-2 border-yellow-400'
                        : 'text-gray-400 hover:text-white hover:bg-white/5 border-l-2 border-transparent' }}">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$item['icon']] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="mt-4 pt-4 border-t border-white/10">
        <div class="flex items-center gap-3 px-2 mb-3">
            <div class="w-9 h-9 rounded-full overflow-hidden bg-white/10 flex items-center justify-center shrink-0">
                @if ($avatarUrl ?? null)
                    <img src="{{ $avatarUrl }}" alt="{{ $nombre }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
                @endif
            </div>
            <div class="leading-tight min-w-0">
                <p class="text-sm font-semibold truncate">{{ $nombre ?? '' }}</p>
                <p class="text-[11px] text-gray-500 truncate">{{ $rolEtiqueta ?? '' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ $logoutRoute }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>

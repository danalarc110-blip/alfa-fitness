@php
    $panelUser = auth('cliente')->user() ?? auth('web')->user();
    $panelName = $panelUser?->nombre ?? $panelUser?->name ?? 'Mi espacio';
    $panelAvatar = $panelUser?->avatar_url;
@endphp
<header class="alpha-topbar">
    <p>Alpha Fitness <span>/</span> <strong>@yield('title', 'Mi espacio')</strong></p>
    <div class="alpha-topbar-actions">
        <span class="alpha-today">{{ now()->format('d/m/Y') }}</span>
        <button type="button" onclick="alphaToggleTema()" class="alpha-theme-control" aria-label="Cambiar tema" title="Cambiar tema">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l2 2M17 17l2 2M5 19l2-2M17 7l2-2"/></svg>
        </button>
        <a class="alpha-avatar" href="{{ route('configuracion') }}" aria-label="Configuración de {{ $panelName }}">
            @if($panelAvatar)
                <img src="{{ $panelAvatar }}" alt="{{ $panelName }}" class="w-full h-full object-cover rounded-full">
            @else
                {{ mb_strtoupper(mb_substr($panelName, 0, 1)) }}
            @endif
        </a>
    </div>
</header>

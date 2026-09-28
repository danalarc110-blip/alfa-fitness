@php
    $panelUser = auth('cliente')->user() ?? auth('web')->user();
    $panelName = $panelUser?->nombre ?? $panelUser?->name ?? 'Mi espacio';
    $panelAvatar = $panelUser?->avatar_url;
    $topbarAforoActual = \Illuminate\Support\Facades\Cache::remember('aforo_en_vivo', 15, function () {
        return \App\Models\Asistencia::whereNull('fecha_salida')->where('fecha_hora', '>=', now()->subHours(12))->count();
    });
    $topbarCapacidad = 80;
    $topbarPct = min(100, (int) round(($topbarAforoActual / $topbarCapacidad) * 100));
@endphp
<header class="alpha-topbar">
    <p>Alpha Fitness <span>/</span> <strong>@yield('title', 'Mi espacio')</strong></p>
    <div class="alpha-topbar-actions">
        <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs shadow-sm" title="Aforo en vivo: {{ $topbarAforoActual }} de {{ $topbarCapacidad }} personas ({{ $topbarPct }}%)">
            <span class="w-2 h-2 rounded-full {{ $topbarPct >= 85 ? 'bg-red-400 animate-pulse' : ($topbarPct >= 50 ? 'bg-amber-400' : 'bg-emerald-400') }}"></span>
            <span class="text-gray-400 font-medium">Aforo:</span>
            <strong class="text-white font-semibold">{{ $topbarAforoActual }}/{{ $topbarCapacidad }}</strong>
        </div>
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

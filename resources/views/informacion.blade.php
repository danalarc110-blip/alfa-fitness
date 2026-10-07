<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Información General</title>

    @include('partials.appearance')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="alpha-app alpha-public font-sans antialiased bg-black text-white min-h-screen selection:bg-yellow-400 selection:text-black">

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-14">

        {{-- BARRA SUPERIOR --}}
        <header class="flex items-center justify-between gap-4 mb-8 sm:mb-12">
            <a href="{{ route('login') }}" class="alpha-btn-secondary inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold">
                &larr; Iniciar Sesión / Registro
            </a>
            <button type="button" onclick="alphaToggleTema()" title="Cambiar tema"
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-gray-400 hover:text-yellow-400 transition-all duration-150 active:scale-95 shadow-sm">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            </button>
        </header>

        {{-- HERO --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-8 pb-6 border-b border-white/10" data-animate="header">
            <div class="w-14 h-14 rounded-2xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center anim-icono shrink-0 shadow-lg shadow-yellow-400/5">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 9v6M2 10v4M20 9v6M22 10v4M7 12h10M6 8v8M18 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Alpha Fitness</h1>
                <p class="text-sm text-yellow-400 font-medium mt-0.5">Centro Integral de Acondicionamiento Físico y Rendimiento</p>
            </div>
        </div>

        {{-- PLANES DE MEMBRESÍA DISPONIBLES --}}
        @if(isset($planes) && $planes->isNotEmpty())
            <section class="mb-10" data-animate="card">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                        Planes de Membresía Vigentes
                    </h2>
                    <p class="text-xs text-gray-400 mt-1">Tarifas oficiales activas en el sistema. Puedes solicitarlas directamente desde tu cuenta de socio.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($planes as $plan)
                        <div class="alpha-card p-5 border border-white/10 rounded-2xl flex flex-col justify-between relative overflow-hidden group hover:border-yellow-400/30 transition-all">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-bold text-base text-white group-hover:text-yellow-400 transition-colors">{{ $plan->nombre }}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                                        {{ $plan->duracion_dias }} días
                                    </span>
                                </div>
                                <p class="text-2xl sm:text-3xl font-black text-white my-2">${{ number_format((float)$plan->precio, 2) }} <span class="text-xs text-gray-400 font-normal">USD</span></p>
                                @if($plan->condiciones)
                                    <p class="text-xs text-gray-400 leading-relaxed">{{ $plan->condiciones }}</p>
                                @endif
                            </div>
                            <div class="mt-4 pt-3 border-t border-white/5">
                                <a href="{{ route('login') }}" class="alpha-btn-primary w-full py-2 rounded-xl text-xs font-semibold text-center block">
                                    Inscribirme
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- INFORMACIÓN DE OPERACIÓN --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
            <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden" data-animate="card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-yellow-400/10 flex items-center justify-center text-yellow-400 shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h2 class="text-white font-bold text-sm">Horarios de Atención</h2>
                </div>
                <div class="text-xs text-gray-400 space-y-1.5 leading-relaxed">
                    <p><strong class="text-gray-300">Lunes a Viernes:</strong><br>5:00 a.m. – 10:00 p.m.</p>
                    <p><strong class="text-gray-300">Sábados:</strong><br>6:00 a.m. – 6:00 p.m.</p>
                    <p><strong class="text-gray-300">Domingos y Feriados:</strong><br>8:00 a.m. – 2:00 p.m.</p>
                </div>
            </div>

            <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden" data-animate="card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-yellow-400/10 flex items-center justify-center text-yellow-400 shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <h2 class="text-white font-bold text-sm">Instalaciones</h2>
                </div>
                <div class="text-xs text-gray-400 space-y-1.5 leading-relaxed">
                    <p>Zona de musculación y peso libre de alto impacto.</p>
                    <p>Área de cardio y acondicionamiento aeróbico.</p>
                    <p>Espacio de entrenamiento funcional y calistenia.</p>
                    <p>Casilleros y vestidores climatizados.</p>
                </div>
            </div>

            <div class="alpha-card rounded-2xl p-5 border border-white/10 relative overflow-hidden" data-animate="card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-yellow-400/10 flex items-center justify-center text-yellow-400 shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <h2 class="text-white font-bold text-sm">Contacto y Recepción</h2>
                </div>
                <div class="text-xs text-gray-400 space-y-1.5 leading-relaxed">
                    <p><strong class="text-gray-300">Atención en recepción:</strong> Mostrador principal del gimnasio.</p>
                    <p><strong class="text-gray-300">Gestión de membresías:</strong> Pagos y renovaciones presenciales.</p>
                    <p><strong class="text-gray-300">Soporte técnico web:</strong> administracion@alphafitness.local</p>
                </div>
            </div>
        </div>

        {{-- FOOTER / CTA --}}
        <footer class="text-center pt-8 border-t border-white/10">
            <p class="text-xs text-gray-500 mb-3">&copy; {{ date('Y') }} Alpha Fitness. Todos los derechos reservados.</p>
            <a href="{{ route('login') }}" class="alpha-btn-primary px-6 py-2.5 rounded-xl text-xs font-semibold inline-flex items-center gap-2">
                Ingresar a la plataforma &rarr;
            </a>
        </footer>

    </div>

</body>
</html>

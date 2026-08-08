<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Iniciar sesión</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes fondoZoom {
            0%   { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        @keyframes tarjetaEntrada {
            0%   { opacity: 0; transform: translateY(24px) scale(.97); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes campoEntrada {
            0%   { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        @keyframes brilloIcono {
            0%, 100% { filter: drop-shadow(0 0 0 rgba(250,204,21,0)); }
            50%      { filter: drop-shadow(0 0 10px rgba(250,204,21,.55)); }
        }
        .anim-fondo { animation: fondoZoom 12s ease-out forwards; }
        .anim-tarjeta { animation: tarjetaEntrada .55s cubic-bezier(.22,1,.36,1) both; }
        .anim-icono { animation: brilloIcono 3s ease-in-out infinite; }
        .anim-campo { opacity: 0; animation: campoEntrada .45s ease-out forwards; }
        .anim-campo:nth-child(1) { animation-delay: .08s; }
        .anim-campo:nth-child(2) { animation-delay: .16s; }
        .anim-campo:nth-child(3) { animation-delay: .24s; }
        .anim-campo:nth-child(4) { animation-delay: .32s; }

        .form-panel {
            transition: opacity .28s ease, transform .28s ease;
        }
        .form-panel.form-oculto {
            opacity: 0;
            transform: translateX(12px);
            position: absolute;
            pointer-events: none;
        }
        .form-panel.form-visible {
            opacity: 1;
            transform: translateX(0);
            position: relative;
        }

        .tab-btn { transition: color .25s ease, border-color .25s ease; }
        .tab-indicador {
            transition: transform .3s cubic-bezier(.22,1,.36,1);
        }

        .btn-anim {
            transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .btn-anim:hover { transform: translateY(-1px) scale(1.015); }
        .btn-anim:active { transform: translateY(0) scale(.98); }

        input.campo-anim {
            transition: box-shadow .2s ease, border-color .2s ease, transform .15s ease;
        }
        input.campo-anim:focus { transform: translateY(-1px); }

        #panelInfoGimnasio .modal-caja {
            animation: tarjetaEntrada .3s cubic-bezier(.22,1,.36,1) both;
        }
    </style>
</head>
<body class="font-sans antialiased">

    <div class="relative min-h-screen flex items-center justify-center overflow-hidden bg-black">

        {{-- Fondo del gimnasio --}}
        <div
            class="absolute inset-0 bg-cover bg-center anim-fondo"
            style="background-image: url('{{ asset('images/gym-bg.png') }}');"
        ></div>
        <div class="absolute inset-0 bg-black/70"></div>

        {{-- Tarjeta de login --}}
        <div class="relative z-10 w-full max-w-md mx-4 my-10">
            <div class="anim-tarjeta bg-[#141414]/95 border border-white/10 rounded-2xl shadow-2xl px-8 py-10">

                {{-- Icono + encabezado --}}
                <div class="flex flex-col items-center text-center mb-6">
                    <svg class="anim-icono w-9 h-9 text-yellow-400 mb-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 9v6M2 10v4M20 9v6M22 10v4M7 12h10M6 8v8M18 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h1 class="text-2xl font-bold text-white">Bienvenido</h1>
                    <p class="text-gray-400 text-sm mt-1">Inicia sesión para continuar</p>
                </div>

                {{-- Mensajes de error --}}
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-400">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Tabs Usuario / Cliente --}}
                <div class="flex mb-6 border-b border-white/10">
                    <button type="button" data-tab="usuario"
                        class="tab-btn flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-gray-400 border-b-2 border-transparent -mb-px">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zM4 22a8 8 0 1116 0H4z"/></svg>
                        Usuario
                    </button>
                    <button type="button" data-tab="cliente"
                        class="tab-btn flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-yellow-400 border-b-2 border-yellow-400 -mb-px">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        Cliente
                    </button>
                </div>

                {{-- Formulario USUARIO (empleados) --}}
                <form id="form-usuario" method="POST" action="{{ route('login.submit') }}" class="form-panel form-oculto space-y-4">
                    @csrf

                    <div>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zM4 22a8 8 0 1116 0H4z"/></svg>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Usuario o correo electrónico"
                                required
                                autofocus
                                class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60"
                            >
                        </div>
                    </div>

                    <div>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17a2 2 0 002-2 2 2 0 00-2-2 2 2 0 00-2 2 2 2 0 002 2zm6-9a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V10a2 2 0 012-2h1V6a5 5 0 0110 0v2h1zm-6-5a3 3 0 00-3 3v2h6V6a3 3 0 00-3-3z"/></svg>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Contraseña"
                                required
                                class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-11 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60"
                            >
                            <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end text-sm pt-1">
                        <a href="#" class="text-yellow-400 hover:text-yellow-300">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button
                        type="submit"
                        class="btn-anim w-full bg-yellow-400 hover:bg-yellow-300 text-black font-semibold py-3 rounded-xl mt-2"
                    >
                        Iniciar sesión
                    </button>

                    <p class="text-xs text-gray-500 text-center pt-1">Tu sesión se mantendrá iniciada en este dispositivo.</p>
                </form>

                {{-- Formulario CLIENTE (correo/contraseña propia + Google) --}}
                <div id="form-cliente" class="form-panel form-visible space-y-4">

                <form id="form-cliente-login" method="POST" action="{{ route('cliente.login.submit') }}" class="space-y-4 {{ old('nombre') ? 'hidden' : '' }}">
                    @csrf

                    <p class="text-xs text-gray-400 -mt-2 mb-1">
                        Debes crear una cuenta primero (dale a "Crea una cuenta" abajo). Luego inicia sesión con ese mismo correo y contraseña.
                    </p>

                    <div>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zM4 22a8 8 0 1116 0H4z"/></svg>
                            <input
                                type="email"
                                name="correo"
                                placeholder="Correo electrónico"
                                class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60"
                            >
                        </div>
                    </div>

                    <div>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17a2 2 0 002-2 2 2 0 00-2-2 2 2 0 00-2 2 2 2 0 002 2zm6-9a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V10a2 2 0 012-2h1V6a5 5 0 0110 0v2h1zm-6-5a3 3 0 00-3 3v2h6V6a3 3 0 00-3-3z"/></svg>
                            <input
                                id="passwordCliente"
                                type="password"
                                name="password"
                                placeholder="Contraseña"
                                class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-11 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60"
                            >
                            <button type="button" id="toggleServiceCliente" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn-anim w-full bg-yellow-400 hover:bg-yellow-300 text-black font-semibold py-3 rounded-xl mt-2"
                    >
                        Iniciar sesión
                    </button>
                    <p class="text-xs text-gray-500 text-center pt-1">Tu sesión se mantendrá iniciada en este dispositivo.</p>

                    <div class="flex items-center gap-3 my-2">
                        <div class="flex-1 h-px bg-white/10"></div>
                        <span class="text-xs text-gray-500">o</span>
                        <div class="flex-1 h-px bg-white/10"></div>
                    </div>

                    <a href="{{ route('cliente.google') }}" class="btn-anim w-full flex items-center justify-center gap-2 border border-white/10 rounded-xl py-3 text-sm text-gray-200 hover:border-white/20 hover:bg-white/5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.47a5.53 5.53 0 01-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.81z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.92l-3.88-3c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.11A12 12 0 0012 24z"/><path fill="#FBBC05" d="M5.27 14.27a7.2 7.2 0 010-4.54v-3.1H1.27a12 12 0 000 10.75l4-3.11z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 001.27 6.63l4 3.1C6.22 6.86 8.87 4.75 12 4.75z"/></svg>
                        Continuar con Google
                    </a>

                    <p class="text-xs text-gray-500 text-center pt-2">
                        ¿No tienes contraseña todavía?
                        <button type="button" id="btnMostrarRegistro" class="text-yellow-400 hover:text-yellow-300 font-medium">Crea una cuenta</button>
                    </p>
                </form>

                {{-- Registro de cliente nuevo (correo + contraseña propia) --}}
                <form id="form-cliente-registro" method="POST" action="{{ route('cliente.registro') }}" class="space-y-4 {{ old('nombre') ? '' : 'hidden' }}">
                    @csrf

                    <p class="text-xs text-gray-400 -mt-2 mb-1">Crea tu cuenta con una contraseña propia.</p>

                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zM4 22a8 8 0 1116 0H4z"/></svg>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" placeholder="Nombre completo" required
                            class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60">
                    </div>

                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zM4 22a8 8 0 1116 0H4z"/></svg>
                        <input type="email" name="correo" value="{{ old('correo') }}" placeholder="Correo electrónico" required
                            class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60">
                    </div>

                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17a2 2 0 002-2 2 2 0 00-2-2 2 2 0 00-2 2 2 2 0 002 2zm6-9a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V10a2 2 0 012-2h1V6a5 5 0 0110 0v2h1zm-6-5a3 3 0 00-3 3v2h6V6a3 3 0 00-3-3z"/></svg>
                        <input id="passwordRegistro" type="password" name="password" placeholder="Contraseña (mín. 8 caracteres)" required minlength="8"
                            class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-11 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60">
                        <button type="button" id="toggleRegistroPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>

                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17a2 2 0 002-2 2 2 0 00-2-2 2 2 0 00-2 2 2 2 0 002 2zm6-9a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V10a2 2 0 012-2h1V6a5 5 0 0110 0v2h1zm-6-5a3 3 0 00-3 3v2h6V6a3 3 0 00-3-3z"/></svg>
                        <input id="passwordRegistroConfirm" type="password" name="password_confirmation" placeholder="Confirmar contraseña" required minlength="8"
                            class="w-full bg-black/40 border border-white/10 rounded-xl py-3 pl-11 pr-11 text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/60 focus:border-yellow-400/60">
                        <button type="button" id="toggleRegistroPasswordConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>

                    <button type="submit" class="btn-anim w-full bg-yellow-400 hover:bg-yellow-300 text-black font-semibold py-3 rounded-xl mt-2">
                        Crear cuenta
                    </button>

                    <p class="text-xs text-gray-500 text-center pt-1">
                        ¿Ya tienes cuenta?
                        <button type="button" id="btnMostrarLogin" class="text-yellow-400 hover:text-yellow-300 font-medium">Inicia sesión</button>
                    </p>
                </form>

                </div>

                {{-- Acceso rápido --}}
                <div class="flex items-center gap-3 my-6">
                    <div class="flex-1 h-px bg-white/10"></div>
                    <span class="text-xs text-gray-500">Acceso rápido</span>
                    <div class="flex-1 h-px bg-white/10"></div>
                </div>

                <button type="button" id="btnInfoGimnasio" class="btn-anim w-full flex items-center justify-center gap-2 border border-white/10 rounded-xl py-3 text-sm text-gray-200 hover:border-white/20 hover:bg-white/5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    Información del gimnasio
                </button>

            </div>
        </div>

        {{-- Panel de información del gimnasio (modal) --}}
        <div id="panelInfoGimnasio" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
            <div id="panelInfoOverlay" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

            <div class="modal-caja relative w-full max-w-sm bg-gradient-to-b from-[#1a1a1a] to-[#0d0d0d] border border-white/10 rounded-2xl p-6 shadow-2xl">
                <button type="button" id="btnCerrarInfo" class="absolute top-4 right-4 text-gray-500 hover:text-gray-300 hover:bg-white/5 rounded-lg p-1 transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl bg-yellow-400/10 border border-yellow-400/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 9v6M2 10v4M20 9v6M22 10v4M7 12h10M6 8v8M18 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white leading-tight">Alpha Fitness</h2>
                        <p class="text-xs text-gray-500">Tu mejor versión</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="w-9 h-9 shrink-0 rounded-lg bg-white/5 flex items-center justify-center text-yellow-400">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Horario</p>
                            <p class="text-xs text-gray-400 mt-0.5">Lunes a viernes: 5:00 a.m. – 10:00 p.m.<br>Sábados: 6:00 a.m. – 6:00 p.m.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-9 h-9 shrink-0 rounded-lg bg-white/5 flex items-center justify-center text-yellow-400">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Ubicación</p>
                            <p class="text-xs text-gray-400 mt-0.5">Actualiza aquí la dirección real del gimnasio.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-9 h-9 shrink-0 rounded-lg bg-white/5 flex items-center justify-center text-yellow-400">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.8 19.8 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.8 19.8 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.12.9.34 1.79.65 2.65a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.43-1.22a2 2 0 012.11-.45c.86.31 1.75.53 2.65.65A2 2 0 0122 16.92z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Contacto</p>
                            <p class="text-xs text-gray-400 mt-0.5">Actualiza aquí el teléfono o correo de contacto.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mostrar / ocultar contraseña
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        toggleBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
        });

        // Mismo mostrar / ocultar para el resto de campos de contraseña
        function activarToggle(botonId, inputId) {
            const boton = document.getElementById(botonId);
            const input = document.getElementById(inputId);
            if (!boton || !input) return;
            boton.addEventListener('click', () => {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
            });
        }
        activarToggle('toggleServiceCliente', 'passwordCliente');
        activarToggle('toggleRegistroPassword', 'passwordRegistro');
        activarToggle('toggleRegistroPasswordConfirm', 'passwordRegistroConfirm');

        // Tabs Usuario / Cliente: cada uno muestra su propio formulario
        const tabButtons = document.querySelectorAll('.tab-btn');
        const formUsuario = document.getElementById('form-usuario');
        const formCliente = document.getElementById('form-cliente');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                tabButtons.forEach(b => {
                    b.classList.remove('text-yellow-400', 'border-yellow-400');
                    b.classList.add('text-gray-400', 'border-transparent');
                });
                btn.classList.remove('text-gray-400', 'border-transparent');
                btn.classList.add('text-yellow-400', 'border-yellow-400');

                if (btn.dataset.tab === 'cliente') {
                    formUsuario.classList.remove('form-visible');
                    formUsuario.classList.add('form-oculto');
                    formCliente.classList.remove('form-oculto');
                    formCliente.classList.add('form-visible');
                } else {
                    formCliente.classList.remove('form-visible');
                    formCliente.classList.add('form-oculto');
                    formUsuario.classList.remove('form-oculto');
                    formUsuario.classList.add('form-visible');
                }
            });
        });
        // Cliente: alternar entre iniciar sesión y crear cuenta
        const btnMostrarRegistro = document.getElementById('btnMostrarRegistro');
        const btnMostrarLogin = document.getElementById('btnMostrarLogin');
        const formClienteLogin = document.getElementById('form-cliente-login');
        const formClienteRegistro = document.getElementById('form-cliente-registro');
        btnMostrarRegistro.addEventListener('click', () => {
            formClienteLogin.classList.add('hidden');
            formClienteRegistro.classList.remove('hidden');
        });
        btnMostrarLogin.addEventListener('click', () => {
            formClienteRegistro.classList.add('hidden');
            formClienteLogin.classList.remove('hidden');
        });

        // Panel de información del gimnasio
        const panelInfo = document.getElementById('panelInfoGimnasio');
        document.getElementById('btnInfoGimnasio').addEventListener('click', () => {
            panelInfo.classList.remove('hidden');
        });
        document.getElementById('btnCerrarInfo').addEventListener('click', () => {
            panelInfo.classList.add('hidden');
        });
        document.getElementById('panelInfoOverlay').addEventListener('click', () => {
            panelInfo.classList.add('hidden');
        });
    </script>
</body>
</html>

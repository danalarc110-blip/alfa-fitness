<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Iniciar Sesión</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* =========================================================
           Animaciones de fondo y efectos luminosos
           ========================================================= */
        @keyframes zoomFondo {
            0%, 100% { transform: scale(1.04); }
            50%      { transform: scale(1.08); }
        }

        @keyframes floatLuz {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
            50%      { transform: translate(15px, -15px) scale(1.1); opacity: 0.5; }
        }

        @keyframes shineBoton {
            0%   { transform: translateX(-150%) rotate(25deg); }
            100% { transform: translateX(250%) rotate(25deg); }
        }

        .anim-fondo {
            animation: zoomFondo 20s ease-in-out infinite;
        }

        .luz-ambiental {
            animation: floatLuz 8s ease-in-out infinite;
        }

        /* Tarjeta principal con cristal ahumado y relieve */
        .card-login-alpha {
            background: rgba(18, 18, 18, 0.94);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.9), 0 0 35px -5px rgba(250, 204, 21, 0.12);
        }

        /* Selector de rol con pastilla animada */
        .switch-container {
            background: rgba(0, 0, 0, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }

        .switch-slider {
            position: absolute;
            top: 4px;
            bottom: 4px;
            width: calc(50% - 4px);
            background: linear-gradient(135deg, rgba(250, 204, 21, 0.18) 0%, rgba(250, 204, 21, 0.05) 100%);
            border: 1px solid rgba(250, 204, 21, 0.45);
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(250, 204, 21, 0.15);
            transition: transform 0.3s cubic-bezier(0.25, 1, 0.35, 1);
            pointer-events: none;
        }

        .switch-slider.pos-usuarios {
            transform: translateX(0);
        }

        .switch-slider.pos-clientes {
            transform: translateX(100%);
        }

        /* Inputs estilizados */
        .input-group {
            background: rgba(0, 0, 0, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }

        .input-group:focus-within {
            border-color: rgba(250, 204, 21, 0.6);
            background: rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
            transform: translateY(-1px);
        }

        .input-group:focus-within .input-icon {
            color: #facc15;
            transform: scale(1.08);
        }

        .input-icon {
            transition: all 0.2s ease;
        }

        /* Botón de acción con efecto de brillo */
        .btn-alpha-submit {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #facc15 0%, #eab308 100%);
            color: #000;
            font-weight: 700;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 18px -2px rgba(250, 204, 21, 0.4);
        }

        .btn-alpha-submit:hover {
            transform: translateY(-1.5px) scale(1.01);
            box-shadow: 0 8px 25px -2px rgba(250, 204, 21, 0.55);
        }

        .btn-alpha-submit:active {
            transform: translateY(0.5px) scale(0.98);
        }

        .btn-alpha-submit::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 40%;
            height: 200%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
            transform: rotate(25deg);
            opacity: 0;
        }

        .btn-alpha-submit:hover::after {
            opacity: 1;
            animation: shineBoton 0.85s ease-in-out forwards;
        }
    </style>
</head>
<body class="font-sans antialiased text-white bg-black selection:bg-yellow-400 selection:text-black">

    <div class="relative min-h-screen flex items-center justify-center overflow-hidden p-4 sm:p-6">

        {{-- Fotografía del gimnasio de fondo --}}
        <div
            class="absolute inset-0 bg-cover bg-center anim-fondo filter brightness-75 scale-105"
            style="background-image: url('{{ asset('images/gym-bg.png') }}');"
        ></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/85 to-black/65"></div>

        {{-- Luces ambientales --}}
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-yellow-500/15 rounded-full blur-3xl pointer-events-none luz-ambiental"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-yellow-400/10 rounded-full blur-3xl pointer-events-none luz-ambiental" style="animation-delay: -4s;"></div>

        {{-- Tarjeta de Login --}}
        <div class="relative z-10 w-full max-w-[430px] my-6">
            <div class="card-login-alpha rounded-3xl p-7 sm:p-9 relative overflow-hidden">
                
                {{-- Encabezado y Logo --}}
                <div class="flex flex-col items-center text-center mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-yellow-500/20 to-yellow-400/5 border border-yellow-400/30 flex items-center justify-center mb-3 shadow-lg shadow-yellow-400/10">
                        <svg class="w-8 h-8 text-yellow-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 9v6M2 10v4M20 9v6M22 10v4M7 12h10M6 8v8M18 8v8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
                        Alpha <span class="text-yellow-400">Fitness</span>
                    </h1>
                    <p id="portal-subtitulo" class="text-xs sm:text-sm text-gray-400 mt-1">
                        Acceso para trabajadores, entrenadores y administración
                    </p>
                </div>

                {{-- Mensajes de error / status --}}
                @if ($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-500/30 bg-red-500/15 p-3.5 text-xs sm:text-sm text-red-300 flex items-start gap-2.5 shadow-lg backdrop-blur-sm">
                        <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div class="flex-1 font-medium">{{ $errors->first() }}</div>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-5 rounded-2xl border border-green-500/30 bg-green-500/15 p-3.5 text-xs sm:text-sm text-green-300 flex items-start gap-2.5 shadow-lg backdrop-blur-sm">
                        <svg class="w-5 h-5 text-green-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <div class="flex-1 font-medium">{{ session('status') }}</div>
                    </div>
                @endif

                {{-- Selector de Rol: Usuarios vs Clientes --}}
                <div class="switch-container p-1 rounded-2xl flex items-center mb-6 relative">
                    <div id="switch-slider" class="switch-slider pos-usuarios"></div>

                    <button type="button" id="tab-btn-usuarios"
                        class="flex-1 py-2.5 px-3 rounded-xl text-xs sm:text-sm font-semibold text-yellow-400 relative z-10 flex items-center justify-center gap-2 transition-colors duration-200">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Usuarios</span>
                    </button>

                    <button type="button" id="tab-btn-clientes"
                        class="flex-1 py-2.5 px-3 rounded-xl text-xs sm:text-sm font-semibold text-gray-400 relative z-10 flex items-center justify-center gap-2 transition-colors duration-200">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>Clientes</span>
                    </button>
                </div>

                {{-- ========================================================================= --}}
                {{-- 1. SECCIÓN USUARIOS (Trabajadores: Entrenadores, Administración, Recepción) --}}
                {{-- ========================================================================= --}}
                <div id="seccion-usuarios" class="space-y-4">
                    <form id="form-usuario" method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                        @csrf

                        <div class="flex items-center justify-between px-1">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-yellow-400/90 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span>
                                Acceso Empleados / Staff
                            </span>
                            <span class="text-[11px] text-gray-500">Entrenadores & Admin</span>
                        </div>

                        {{-- Correo / Usuario --}}
                        <div>
                            <div class="input-group rounded-2xl flex items-center px-4 py-3">
                                <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="Correo de empleado o usuario"
                                    required
                                    autofocus
                                    class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none"
                                >
                            </div>
                        </div>

                        {{-- Contraseña --}}
                        <div>
                            <div class="input-group rounded-2xl flex items-center px-4 py-3">
                                <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Contraseña"
                                    required
                                    class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none pr-2"
                                >
                                <button type="button" id="togglePassword" class="text-gray-500 hover:text-yellow-400 transition-colors p-1" title="Ver/ocultar contraseña">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="btn-alpha-submit w-full py-3.5 px-4 rounded-2xl text-sm mt-2 flex items-center justify-center gap-2"
                        >
                            <span>Iniciar Sesión como Usuario</span>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </form>
                </div>

                {{-- ========================================================================= --}}
                {{-- 2. SECCIÓN CLIENTES (Miembros / Atletas) --}}
                {{-- ========================================================================= --}}
                <div id="seccion-clientes" class="space-y-4 hidden">
                    
                    {{-- 2A. Login Cliente --}}
                    <form id="form-cliente-login" method="POST" action="{{ route('cliente.login.submit') }}" class="space-y-4 {{ old('nombre') ? 'hidden' : '' }}">
                        @csrf

                        {{-- Botón Google --}}
                        <a href="{{ route('cliente.google') }}" class="w-full flex items-center justify-center gap-3 bg-white/[0.05] hover:bg-white/[0.09] border border-white/10 hover:border-white/20 rounded-2xl py-3 px-4 text-sm font-semibold text-white transition-all duration-200 active:scale-95 shadow-sm group">
                            <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.47a5.53 5.53 0 01-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.81z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.92l-3.88-3c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.11A12 12 0 0012 24z"/><path fill="#FBBC05" d="M5.27 14.27a7.2 7.2 0 010-4.54v-3.1H1.27a12 12 0 000 10.75l4-3.11z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 001.27 6.63l4 3.1C6.22 6.86 8.87 4.75 12 4.75z"/></svg>
                            <span>Continuar con Google</span>
                        </a>

                        <div class="flex items-center gap-3 my-2">
                            <div class="flex-1 h-px bg-white/10"></div>
                            <span class="text-[11px] uppercase tracking-wider text-gray-500 font-medium">o con correo</span>
                            <div class="flex-1 h-px bg-white/10"></div>
                        </div>

                        {{-- Correo Cliente --}}
                        <div>
                            <div class="input-group rounded-2xl flex items-center px-4 py-3">
                                <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <input
                                    type="email"
                                    name="correo"
                                    placeholder="tu@correo.com"
                                    class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none"
                                >
                            </div>
                        </div>

                        {{-- Contraseña Cliente --}}
                        <div>
                            <div class="input-group rounded-2xl flex items-center px-4 py-3">
                                <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <input
                                    id="passwordCliente"
                                    type="password"
                                    name="password"
                                    placeholder="Tu contraseña"
                                    class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none pr-2"
                                >
                                <button type="button" id="toggleServiceCliente" class="text-gray-500 hover:text-yellow-400 transition-colors p-1" title="Ver/ocultar contraseña">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="btn-alpha-submit w-full py-3.5 px-4 rounded-2xl text-sm mt-2 flex items-center justify-center gap-2"
                        >
                            <span>Iniciar Sesión como Cliente</span>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>

                        <div class="pt-2 text-center">
                            <p class="text-xs text-gray-400">
                                ¿No tienes cuenta?
                                <button type="button" id="btnMostrarRegistro" class="text-yellow-400 hover:text-yellow-300 font-bold ml-1 transition-colors underline-offset-4 hover:underline">
                                    Crea una cuenta aquí
                                </button>
                            </p>
                        </div>
                    </form>

                    {{-- 2B. Registro de Cliente --}}
                    <form id="form-cliente-registro" method="POST" action="{{ route('cliente.registro') }}" class="space-y-3 {{ old('nombre') ? '' : 'hidden' }}">
                        @csrf

                        <div class="text-center mb-1">
                            <h2 class="text-base font-bold text-white">Registro de Nuevo Miembro</h2>
                            <p class="text-[11px] text-gray-400">Crea tu cuenta para acceder a tus entrenamientos</p>
                        </div>

                        {{-- Nombre --}}
                        <div class="input-group rounded-2xl flex items-center px-4 py-2.5">
                            <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input type="text" name="nombre" value="{{ old('nombre') }}" placeholder="Nombre completo" required
                                class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none">
                        </div>

                        {{-- Correo --}}
                        <div class="input-group rounded-2xl flex items-center px-4 py-2.5">
                            <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" name="correo" value="{{ old('correo') }}" placeholder="Correo electrónico" required
                                class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none">
                        </div>

                        {{-- Contraseña --}}
                        <div class="input-group rounded-2xl flex items-center px-4 py-2.5">
                            <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input id="passwordRegistro" type="password" name="password" placeholder="Contraseña (mín. 8 caracteres)" required minlength="8"
                                class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none pr-2">
                            <button type="button" id="toggleRegistroPassword" class="text-gray-500 hover:text-yellow-400 transition-colors p-1">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>

                        {{-- Confirmar Contraseña --}}
                        <div class="input-group rounded-2xl flex items-center px-4 py-2.5">
                            <svg class="input-icon w-5 h-5 text-gray-500 shrink-0 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <input id="passwordRegistroConfirm" type="password" name="password_confirmation" placeholder="Confirmar contraseña" required minlength="8"
                                class="w-full bg-transparent text-sm text-white placeholder-gray-500 outline-none pr-2">
                            <button type="button" id="toggleRegistroPasswordConfirm" class="text-gray-500 hover:text-yellow-400 transition-colors p-1">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>

                        <button type="submit" class="btn-alpha-submit w-full py-3.5 px-4 rounded-2xl text-sm mt-2 flex items-center justify-center gap-2">
                            <span>Crear Cuenta</span>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>

                        <div class="pt-2 text-center">
                            <p class="text-xs text-gray-400">
                                ¿Ya tienes cuenta?
                                <button type="button" id="btnMostrarLogin" class="text-yellow-400 hover:text-yellow-300 font-bold ml-1 transition-colors underline-offset-4 hover:underline">
                                    Inicia sesión aquí
                                </button>
                            </p>
                        </div>
                    </form>

                </div>

                {{-- Botón Información Gimnasio --}}
                <div class="mt-6 pt-5 border-t border-white/10 flex items-center justify-between">
                    <span class="text-[11px] text-gray-400 font-medium">¿Dudas sobre el club?</span>
                    <button type="button" id="btnInfoGimnasio" class="inline-flex items-center gap-1.5 text-xs font-semibold text-yellow-400 hover:text-yellow-300 transition-colors py-1 px-2.5 rounded-lg hover:bg-yellow-400/10">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        <span>Información y Horarios</span>
                    </button>
                </div>

            </div>
        </div>

        {{-- Modal de Información del Gimnasio --}}
        <div id="panelInfoGimnasio" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
            <div id="panelInfoOverlay" class="absolute inset-0 bg-black/80 backdrop-blur-md"></div>

            <div class="modal-caja relative w-full max-w-md bg-gradient-to-b from-[#1c1c1c] via-[#141414] to-[#0d0d0d] border border-white/15 rounded-3xl p-7 shadow-2xl z-10">
                <button type="button" id="btnCerrarInfo" class="absolute top-5 right-5 text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-full p-2 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>

                <div class="flex items-center gap-3.5 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-400/10 border border-yellow-400/30 flex items-center justify-center shadow-md shadow-yellow-400/10">
                        <svg class="w-6 h-6 text-yellow-400 anim-icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 9v6M2 10v4M20 9v6M22 10v4M7 12h10M6 8v8M18 8v8"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Alpha Fitness Club</h2>
                        <p class="text-xs text-yellow-400/90 font-medium">Instalaciones de clase mundial</p>
                    </div>
                </div>

                <div class="space-y-4 text-gray-300 text-sm">
                    <div class="flex items-start gap-3.5 bg-white/[0.03] p-3.5 rounded-2xl border border-white/5">
                        <div class="w-9 h-9 rounded-xl bg-yellow-400/10 flex items-center justify-center text-yellow-400 shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-white text-xs uppercase tracking-wider">Horario de Apertura</p>
                            <p class="text-xs text-gray-400 mt-1">Lunes a Viernes: 5:00 a.m. – 10:00 p.m.<br>Sábados y Domingos: 6:00 a.m. – 6:00 p.m.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5 bg-white/[0.03] p-3.5 rounded-2xl border border-white/5">
                        <div class="w-9 h-9 rounded-xl bg-yellow-400/10 flex items-center justify-center text-yellow-400 shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-white text-xs uppercase tracking-wider">Ubicación</p>
                            <p class="text-xs text-gray-400 mt-1">Área de musculación, peso libre, cross-training, cardio y asesoría personalizada.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('informacion') }}" class="w-full alpha-btn-primary py-3 rounded-xl text-xs font-bold text-center block">
                        Ver página completa de información →
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- Script de control e interactividad --}}
    <script>
        // Ver / Ocultar contraseñas
        function setupPasswordToggle(btnId, inputId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            if (!btn || !input) return;
            btn.addEventListener('click', () => {
                const isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                btn.classList.toggle('text-yellow-400', isPass);
            });
        }
        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleServiceCliente', 'passwordCliente');
        setupPasswordToggle('toggleRegistroPassword', 'passwordRegistro');
        setupPasswordToggle('toggleRegistroPasswordConfirm', 'passwordRegistroConfirm');

        // Alternar entre pestaña Usuarios y Clientes
        const btnTabUsuarios = document.getElementById('tab-btn-usuarios');
        const btnTabClientes = document.getElementById('tab-btn-clientes');
        const switchSlider = document.getElementById('switch-slider');
        const seccionUsuarios = document.getElementById('seccion-usuarios');
        const seccionClientes = document.getElementById('seccion-clientes');
        const subtitulo = document.getElementById('portal-subtitulo');

        function activarPestana(tipo) {
            if (tipo === 'usuarios') {
                switchSlider.classList.remove('pos-clientes');
                switchSlider.classList.add('pos-usuarios');

                btnTabUsuarios.classList.add('text-yellow-400');
                btnTabUsuarios.classList.remove('text-gray-400');
                btnTabClientes.classList.add('text-gray-400');
                btnTabClientes.classList.remove('text-yellow-400');

                subtitulo.textContent = 'Acceso para trabajadores, entrenadores y administración';

                seccionClientes.classList.add('hidden');
                seccionUsuarios.classList.remove('hidden');

                if (window.gsap) {
                    gsap.fromTo(seccionUsuarios, { opacity: 0, y: 10 }, { opacity: 1, y: 0, duration: 0.28, ease: 'power2.out' });
                }
            } else {
                switchSlider.classList.remove('pos-usuarios');
                switchSlider.classList.add('pos-clientes');

                btnTabClientes.classList.add('text-yellow-400');
                btnTabClientes.classList.remove('text-gray-400');
                btnTabUsuarios.classList.add('text-gray-400');
                btnTabUsuarios.classList.remove('text-yellow-400');

                subtitulo.textContent = 'Acceso a planes y entrenamientos para miembros';

                seccionUsuarios.classList.add('hidden');
                seccionClientes.classList.remove('hidden');

                if (window.gsap) {
                    gsap.fromTo(seccionClientes, { opacity: 0, y: 10 }, { opacity: 1, y: 0, duration: 0.28, ease: 'power2.out' });
                }
            }
        }

        btnTabUsuarios.addEventListener('click', () => activarPestana('usuarios'));
        btnTabClientes.addEventListener('click', () => activarPestana('clientes'));

        // Alternar Login Cliente / Registro Cliente
        const btnMostrarRegistro = document.getElementById('btnMostrarRegistro');
        const btnMostrarLogin = document.getElementById('btnMostrarLogin');
        const formClienteLogin = document.getElementById('form-cliente-login');
        const formClienteRegistro = document.getElementById('form-cliente-registro');

        if (btnMostrarRegistro && btnMostrarLogin) {
            btnMostrarRegistro.addEventListener('click', () => {
                formClienteLogin.classList.add('hidden');
                formClienteRegistro.classList.remove('hidden');
                if (window.gsap) {
                    gsap.fromTo(formClienteRegistro, { opacity: 0, y: 10 }, { opacity: 1, y: 0, duration: 0.28, ease: 'power2.out' });
                }
            });

            btnMostrarLogin.addEventListener('click', () => {
                formClienteRegistro.classList.add('hidden');
                formClienteLogin.classList.remove('hidden');
                if (window.gsap) {
                    gsap.fromTo(formClienteLogin, { opacity: 0, y: 10 }, { opacity: 1, y: 0, duration: 0.28, ease: 'power2.out' });
                }
            });
        }

        // Modal de Información
        const modalInfo = document.getElementById('panelInfoGimnasio');
        const btnOpenInfo = document.getElementById('btnInfoGimnasio');
        const btnCloseInfo = document.getElementById('btnCerrarInfo');
        const overlayInfo = document.getElementById('panelInfoOverlay');

        if (btnOpenInfo) {
            btnOpenInfo.addEventListener('click', () => {
                if (window.alphaAnimateModalOpen) {
                    window.alphaAnimateModalOpen('#panelInfoGimnasio');
                } else {
                    modalInfo.classList.remove('hidden');
                }
            });
        }

        if (btnCloseInfo) {
            btnCloseInfo.addEventListener('click', () => {
                if (window.alphaAnimateModalClose) {
                    window.alphaAnimateModalClose('#panelInfoGimnasio');
                } else {
                    modalInfo.classList.add('hidden');
                }
            });
        }

        if (overlayInfo) {
            overlayInfo.addEventListener('click', () => {
                if (window.alphaAnimateModalClose) {
                    window.alphaAnimateModalClose('#panelInfoGimnasio');
                } else {
                    modalInfo.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>

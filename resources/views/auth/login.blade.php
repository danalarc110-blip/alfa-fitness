<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alpha Fitness') }} - Iniciar sesión</title>
    @include('partials.appearance')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="alpha-app">
<a href="#login-content" class="alpha-skip">Saltar al acceso</a>
<main class="alpha-login">
    <section class="alpha-login-visual" aria-label="Alpha Fitness">
        <img src="{{ asset('images/logo-sidebar.webp') }}" alt="Alpha Fitness" width="135" height="115">
        <div class="alpha-login-story"><span class="alpha-eyebrow">Tu mejor versión</span><h2>Disciplina hoy.<br><em>Resultados mañana.</em></h2><p>Tu espacio para entrenar con propósito y hacer que cada día cuente.</p></div>
        <footer>ALPHA FITNESS CLUB · FUERZA · CONSTANCIA · PROGRESO</footer>
    </section>
    <section class="alpha-login-panel" id="login-content">
        <button class="alpha-theme-control text-xs" type="button" onclick="alphaToggleTema()" aria-label="Cambiar tema">◐ Claro / Oscuro</button>
        <div class="alpha-login-inner">
            <p class="alpha-eyebrow">Bienvenido a Alpha Fitness</p><h1>Un nuevo día.<br>Una mejor versión.</h1><p class="alpha-login-subtitle">Ingresa a tu cuenta para continuar.</p>
            @include('partials.feedback')
            @php($clientTab = $errors->has('correo') || $errors->has('nombre') || $errors->has('aceptacion_legal') || old('correo') || old('nombre'))
            <div class="alpha-login-tabs" role="tablist" aria-label="Tipo de cuenta">
                <button id="tab-btn-usuarios" type="button" role="tab" aria-controls="seccion-usuarios" aria-selected="{{ $clientTab ? 'false' : 'true' }}" tabindex="{{ $clientTab ? '-1' : '0' }}">Personal</button>
                <button id="tab-btn-clientes" type="button" role="tab" aria-controls="seccion-clientes" aria-selected="{{ $clientTab ? 'true' : 'false' }}" tabindex="{{ $clientTab ? '0' : '-1' }}">Clientes</button>
            </div>
            <p id="portal-subtitulo" class="text-xs text-gray-400 mb-5" aria-live="polite">{{ $clientTab ? 'Acceso a planes y entrenamientos para miembros.' : 'Acceso para trabajadores, entrenadores y administración.' }}</p>
            <div id="seccion-usuarios" role="tabpanel" aria-labelledby="tab-btn-usuarios" @class(['hidden' => $clientTab])>
                <form id="form-usuario" method="POST" action="{{ route('login.submit') }}" class="alpha-login-form">
                    @csrf
                    <label>Correo del personal<input type="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required autocomplete="username"></label>
                    <label>Contraseña<span class="alpha-password block"><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Ingresa tu contraseña"><button type="button" data-password="password" aria-label="Mostrar contraseña" aria-pressed="false">Ver</button></span></label>
                    <button class="alpha-btn-primary px-4 py-3" type="submit">Iniciar Sesión como Usuario <span aria-hidden="true">→</span></button>
                </form>
            </div>
            <div id="seccion-clientes" role="tabpanel" aria-labelledby="tab-btn-clientes" @class(['hidden' => !$clientTab])>
                <form id="form-cliente-login" method="POST" action="{{ route('cliente.login.submit') }}" @class(['alpha-login-form', 'hidden' => old('nombre')])>
                    @csrf
                    @if(config('services.google.client_id') && config('services.google.client_secret'))<a href="{{ route('cliente.google') }}" class="alpha-btn-secondary px-4 py-3">Continuar con Google</a><p class="text-xs text-gray-400 text-center">o ingresa con tu correo</p>@endif
                    <label>Correo del cliente<input type="email" name="correo" value="{{ old('correo') }}" placeholder="tu@correo.com" required autocomplete="username"></label>
                    <label>Contraseña<span class="alpha-password block"><input id="passwordCliente" type="password" name="password" required autocomplete="current-password" placeholder="Ingresa tu contraseña"><button type="button" data-password="passwordCliente" aria-label="Mostrar contraseña" aria-pressed="false">Ver</button></span></label>
                    <button class="alpha-btn-primary px-4 py-3" type="submit">Iniciar Sesión como Cliente <span aria-hidden="true">→</span></button>
                    <p class="text-xs text-gray-400 text-center">¿No tienes cuenta? <button type="button" id="btnMostrarRegistro" class="text-yellow-400 font-semibold">Crea una cuenta aquí</button></p>
                </form>
                <form id="form-cliente-registro" method="POST" action="{{ route('cliente.registro') }}" @class(['alpha-login-form', 'hidden' => !old('nombre')])>
                    @csrf
                    <h2 class="text-lg font-semibold">Registro de nuevo miembro</h2>
                    <label>Nombre completo<input type="text" name="nombre" value="{{ old('nombre') }}" maxlength="100" required autocomplete="name"></label>
                    <label>Correo del cliente<input type="email" name="correo" value="{{ old('correo') }}" required autocomplete="email"></label>
                    <label>Contraseña<span class="alpha-password block"><input id="passwordRegistro" type="password" name="password" required minlength="12" autocomplete="new-password"><button type="button" data-password="passwordRegistro" aria-label="Mostrar contraseña" aria-pressed="false">Ver</button></span></label>
                    <label>Confirmar contraseña<span class="alpha-password block"><input id="passwordRegistroConfirm" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"><button type="button" data-password="passwordRegistroConfirm" aria-label="Mostrar contraseña" aria-pressed="false">Ver</button></span></label>
                    <p class="text-xs text-gray-400">Usa al menos 12 caracteres, mayúsculas, minúsculas, un número y un símbolo.</p>
                    @include('partials.consentimiento')
                    <button type="submit" class="alpha-btn-primary px-4 py-3">Crear cuenta</button>
                    <p class="text-xs text-center text-gray-400">¿Ya tienes cuenta? <button id="btnMostrarLogin" type="button" class="text-yellow-400 font-semibold">Inicia sesión</button></p>
                </form>
            </div>
            <div class="alpha-login-help"><span>¿Dudas sobre el club?</span><a href="{{ route('informacion') }}">Información y horarios ↗</a></div><p class="alpha-login-caption">ALPHA FITNESS · CADA DÍA CUENTA</p>
            <nav aria-label="Información legal" class="text-xs mt-4 flex flex-wrap gap-3">@include('partials.legal-links')</nav>
        </div>
    </section>
</main>
</body>
</html>

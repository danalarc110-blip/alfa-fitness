<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Recuperar contraseña - {{ config('app.name', 'Alpha Fitness') }}</title>
    @include('partials.appearance')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="alpha-app alpha-public min-h-screen bg-black text-white flex items-center justify-center p-5">
    <main class="alpha-card rounded-2xl p-7 w-full max-w-md shadow-2xl border border-white/10" data-animate="card">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-yellow-400/10 border border-yellow-400/20 text-yellow-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight">Recuperar contraseña</h1>
                <p class="text-xs text-gray-400">Alpha Fitness</p>
            </div>
        </div>

        <p class="text-sm text-gray-400 mb-6">
            Ingresa tu correo electrónico registrado. Si coincide con una cuenta activa, te enviaremos un enlace seguro para restablecer tu contraseña.
        </p>

        @if (session('status'))
            <div class="mb-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-medium">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="alpha-form space-y-4">
            @csrf
            <label>
                Correo electrónico
                <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required placeholder="tu-correo@ejemplo.com">
            </label>

            <button type="submit" class="alpha-btn-primary w-full rounded-xl px-4 py-3 text-sm font-semibold">
                Enviar enlace de recuperación
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-white/10 text-center">
            <a href="{{ route('login') }}" class="text-xs text-yellow-400 hover:text-yellow-300 transition-colors">
                &larr; Volver a iniciar sesión
            </a>
        </div>
    </main>
</body>
</html>

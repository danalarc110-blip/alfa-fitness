@extends('layouts.app', ['active' => 'configuracion'])
@section('title', 'Configuración')
@section('content')
<p class="text-sm text-gray-400 mb-5">Tu cuenta, tu apariencia y la información que necesitas para usar Alpha Fitness.</p>
<nav aria-label="Secciones de configuración" class="flex flex-wrap gap-3 mb-8">
    @foreach(['perfil' => 'Mi perfil', 'apariencia' => 'Apariencia', 'seguridad' => 'Seguridad', 'acerca-de' => 'Acerca de'] as $id => $etiqueta)
        <a href="#{{ $id }}" class="alpha-btn-secondary rounded-xl px-4 py-2 text-sm">{{ $etiqueta }}</a>
    @endforeach
    @if(\App\Support\Acceso::permite($usuarioActual, 'administrar'))
        <a href="#gimnasio" class="alpha-btn-secondary rounded-xl px-4 py-2 text-sm">Información del gimnasio</a>
    @endif
</nav>
<div class="grid lg:grid-cols-2 gap-6">
    <section id="perfil" class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0 scroll-mt-24" aria-labelledby="perfil-titulo">
        <h2 id="perfil-titulo" class="font-semibold text-lg mb-4">Datos personales</h2>
        <p class="text-sm text-gray-400 mb-5 break-words">{{ $correo }} · {{ $rolEtiqueta }}<br>{{ $miembroDesde ? 'Cuenta desde '.$miembroDesde : 'Fecha de registro no disponible' }}</p>
        <form method="POST" action="{{ route('configuracion.perfil') }}" class="alpha-form space-y-4">
            @csrf
            <label for="perfil-nombre">Nombre *<input id="perfil-nombre" name="nombre" value="{{ is_string(old('nombre')) ? old('nombre') : $nombre }}" maxlength="100" autocomplete="name" required @error('nombre') aria-invalid="true" aria-describedby="perfil-nombre-error" @enderror></label>
            @error('nombre')<p id="perfil-nombre-error" class="text-sm text-red-400">{{ $message }}</p>@enderror
            <button type="submit" class="alpha-btn-primary rounded-xl px-5 py-3 text-sm">Guardar perfil</button>
        </form>
    </section>
    <section class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0" aria-labelledby="foto-titulo">
        <h2 id="foto-titulo" class="font-semibold text-lg mb-4">Foto de perfil</h2>
        <div class="w-16 h-16 rounded-2xl overflow-hidden bg-white/5 flex items-center justify-center mb-4">
            @if($avatarUrl)<img src="{{ $avatarUrl }}" alt="Tu foto de perfil" width="64" height="64" decoding="async" class="w-full h-full object-cover">
            @else<span class="text-2xl" aria-label="Inicial de tu nombre">{{ mb_substr($nombre, 0, 1) }}</span>@endif
        </div>
        <form method="POST" action="{{ route('configuracion.avatar') }}" enctype="multipart/form-data" class="alpha-form space-y-4">
            @csrf
            <label for="perfil-avatar">Seleccionar imagen *<input id="perfil-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-image-preview="profile-avatar-preview" aria-describedby="avatar-ayuda{{ $errors->has('avatar') ? ' avatar-error' : '' }}" @error('avatar') aria-invalid="true" @enderror required></label>
            <img id="profile-avatar-preview" class="hidden w-24 h-24 rounded-2xl object-cover" alt="Vista previa de la foto seleccionada">
            <p id="avatar-ayuda" class="text-xs text-gray-400">JPG, PNG o WebP, máximo 2 MB y 4096 × 4096 píxeles. La foto será visible en tu perfil. Evita imágenes con información privada.</p>
            @error('avatar')<p id="avatar-error" class="text-sm text-red-400">{{ $message }}</p>@enderror
            <button type="submit" class="alpha-btn-primary rounded-xl px-5 py-3 text-sm">Guardar foto</button>
        </form>
        @if($usuarioActual->avatar)
            <form method="POST" action="{{ route('configuracion.avatar.eliminar') }}" data-confirm="¿Quitar tu foto de perfil? Se mostrará la inicial de tu nombre." class="mt-4">
                @csrf @method('DELETE')
                <button type="submit" class="alpha-btn-secondary rounded-xl px-5 py-3 text-sm">Quitar foto</button>
            </form>
        @endif
    </section>
    @include('partials.appearance-settings')
    @if($guard === 'cliente' && !$esGoogle && !$usuarioActual->google_id)
        <section class="alpha-card rounded-2xl p-5 sm:p-6 lg:col-span-2" aria-labelledby="google-titulo">
            <h2 id="google-titulo" class="font-semibold text-lg mb-3">Vincular Google</h2>
            @if(config('services.google.client_id') && config('services.google.client_secret'))
                <p class="text-sm text-gray-400 mb-4">Confirma tu contraseña y elige en Google la misma dirección de correo que usas aquí. Después podrás acceder de ambas formas.</p>
                <form method="POST" action="{{ route('configuracion.google') }}" class="alpha-form flex flex-col sm:flex-row gap-4 sm:items-end">
                    @csrf
                    <label class="flex-1" for="google-password">Contraseña actual *<input id="google-password" name="password_actual" type="password" autocomplete="current-password" required></label>
                    <button type="submit" class="alpha-btn-secondary rounded-xl px-5 py-3">Continuar con Google</button>
                </form>
            @else
                <p class="text-sm text-gray-400">El acceso con Google aún no está habilitado. Puedes seguir entrando con tu correo y contraseña.</p>
            @endif
        </section>
    @endif
    <section id="seguridad" class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0 scroll-mt-24" aria-labelledby="seguridad-titulo">
        <h2 id="seguridad-titulo" class="font-semibold text-lg mb-4">Seguridad</h2>
        @unless($esGoogle)
            <form method="POST" action="{{ route('configuracion.password') }}" class="alpha-form space-y-4">
                @csrf
                <label for="password-actual">Contraseña actual *<input id="password-actual" name="password_actual" type="password" autocomplete="current-password" required></label>
                <label for="password-nueva">Nueva contraseña *<input id="password-nueva" name="password" type="password" autocomplete="new-password" minlength="12" aria-describedby="password-ayuda" required></label>
                <label for="password-confirmacion">Confirmar nueva contraseña *<input id="password-confirmacion" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required></label>
                <p id="password-ayuda" class="text-xs text-gray-400">Usa al menos 12 caracteres, mayúsculas, minúsculas, un número y un símbolo. Las demás sesiones se cerrarán.</p>
                <button type="submit" class="alpha-btn-primary rounded-xl px-5 py-3 text-sm">Actualizar contraseña</button>
            </form>
        @else
            <p class="text-sm text-gray-400">El acceso de esta cuenta se gestiona con Google.</p>
        @endunless
    </section>
    <section class="alpha-card rounded-2xl p-5 sm:p-6" aria-labelledby="sesion-titulo">
        <h2 id="sesion-titulo" class="font-semibold text-lg mb-4">Sesión</h2>
        <p class="text-sm text-gray-400">Cierra tu sesión al terminar si utilizas un equipo compartido. Tu apariencia y tus entrenamientos seguirán guardados en tu cuenta.</p>
        <form method="POST" action="{{ route($guard === 'web' ? 'logout' : 'cliente.logout') }}" class="mt-6 pt-5 border-t border-white/10">
            @csrf
            <button type="submit" class="alpha-btn-secondary rounded-xl px-5 py-3 text-sm">Cerrar sesión en este dispositivo</button>
        </form>
    </section>
    <section id="acerca-de" class="alpha-card rounded-2xl p-5 sm:p-6 lg:col-span-2 scroll-mt-24" aria-labelledby="acerca-titulo">
        <h2 id="acerca-titulo" class="font-semibold text-lg">Acerca de Alpha Fitness</h2>
        <p class="text-sm text-gray-400 mt-2">Desarrollado por Apex Logic para este proyecto académico.</p>
        <p class="text-sm text-gray-400 mt-3 max-w-prose">Un espacio para organizar entrenamientos, consultar tu progreso y gestionar la actividad del gimnasio. Las funciones disponibles dependen de tu tipo de cuenta.</p>
        <dl class="grid sm:grid-cols-2 gap-6 mt-6 text-sm">
            <div class="min-w-0"><dt class="font-semibold">Tu gimnasio</dt><dd class="text-gray-400 mt-2 break-words">{{ $gimnasio['nombre'] }}<br>{{ $gimnasio['descripcion'] }}</dd></div>
            <div><dt class="font-semibold">Hecho a tu medida</dt><dd class="text-gray-400 mt-2">Puedes cambiar tu nombre, foto, diseño, colores y movimiento desde esta página. Tus preferencias son independientes de las de otras cuentas.</dd></div>
        </dl>
        @if(config('privacy.demo'))
            <p class="text-sm text-gray-400 mt-6">Esta instalación es una demostración estudiantil. La información de ejemplo puede adaptarse desde la cuenta del administrador.</p>
        @endif
        <div class="flex flex-wrap gap-3 mt-6">
            <a href="{{ route('informacion') }}" class="alpha-btn-secondary rounded-xl px-4 py-3 text-sm">Información y contacto del gimnasio</a>
            <a href="{{ route('privacidad') }}" class="alpha-btn-secondary rounded-xl px-4 py-3 text-sm">Política de privacidad</a>
        </div>
    </section>
    @if(\App\Support\Acceso::permite($usuarioActual, 'administrar'))
        @include('partials.gym-settings')
    @endif
</div>
@endsection

@php
    $gymValue = static function (string $campo) use ($gimnasio) {
        $previous = old('gimnasio.'.$campo, $gimnasio[$campo]);

        return is_string($previous) || is_numeric($previous) ? $previous : $gimnasio[$campo];
    };
@endphp
<section id="gimnasio" class="alpha-card rounded-2xl p-5 sm:p-6 min-w-0 scroll-mt-24 lg:col-span-2" aria-labelledby="gym-settings-title">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h2 id="gym-settings-title" class="font-semibold text-lg">Información del gimnasio</h2>
            <p class="text-sm text-gray-400 mt-2 max-w-2xl">Estos datos son públicos y se comparten con todas las cuentas. Solo el administrador puede modificarlos.</p>
        </div>
        <a class="alpha-btn-secondary rounded-xl px-4 py-2 text-sm" href="{{ route('informacion') }}">Ver página pública</a>
    </div>
    <form method="POST" action="{{ route('configuracion.gimnasio') }}" class="alpha-form grid sm:grid-cols-2 gap-5">
        @csrf
        <label class="sm:col-span-2">Nombre del gimnasio *
            <input name="gimnasio[nombre]" @error('gimnasio.nombre') aria-invalid="true" aria-describedby="gym-nombre-error" @enderror value="{{ $gymValue('nombre') }}" maxlength="100" required>
            @error('gimnasio.nombre')<span id="gym-nombre-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label class="sm:col-span-2">Descripción *
            <textarea name="gimnasio[descripcion]" @error('gimnasio.descripcion') aria-invalid="true" aria-describedby="gym-descripcion-error" @enderror rows="3" maxlength="1200" required>{{ $gymValue('descripcion') }}</textarea>
            @error('gimnasio.descripcion')<span id="gym-descripcion-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label class="sm:col-span-2">Aforo máximo (personas) *
            <input type="number" name="gimnasio[capacidad]" @error('gimnasio.capacidad') aria-invalid="true" @enderror value="{{ $gymValue('capacidad') }}" min="1" max="10000" step="1" inputmode="numeric" aria-describedby="gym-capacity-help{{ $errors->has('gimnasio.capacidad') ? ' gym-capacidad-error' : '' }}" required>
            <span id="gym-capacity-help" class="text-sm text-gray-400">Se usa para calcular la ocupación que aparece en Inicio.</span>
            @error('gimnasio.capacidad')<span id="gym-capacidad-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label>Horarios *
            <textarea name="gimnasio[horarios]" @error('gimnasio.horarios') aria-invalid="true" aria-describedby="gym-horarios-error" @enderror rows="5" maxlength="2000" required>{{ $gymValue('horarios') }}</textarea>
            @error('gimnasio.horarios')<span id="gym-horarios-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label>Instalaciones y servicios *
            <textarea name="gimnasio[instalaciones]" @error('gimnasio.instalaciones') aria-invalid="true" aria-describedby="gym-instalaciones-error" @enderror rows="5" maxlength="3000" required>{{ $gymValue('instalaciones') }}</textarea>
            @error('gimnasio.instalaciones')<span id="gym-instalaciones-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label class="sm:col-span-2">Dirección
            <input name="gimnasio[direccion]" @error('gimnasio.direccion') aria-invalid="true" aria-describedby="gym-direccion-error" @enderror value="{{ $gymValue('direccion') }}" maxlength="500" autocomplete="street-address">
            @error('gimnasio.direccion')<span id="gym-direccion-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label>Teléfono de contacto
            <input type="tel" name="gimnasio[telefono]" @error('gimnasio.telefono') aria-invalid="true" aria-describedby="gym-telefono-error" @enderror value="{{ $gymValue('telefono') }}" maxlength="40" autocomplete="tel">
            @error('gimnasio.telefono')<span id="gym-telefono-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label>Correo de contacto
            <input type="email" name="gimnasio[correo]" @error('gimnasio.correo') aria-invalid="true" aria-describedby="gym-correo-error" @enderror value="{{ $gymValue('correo') }}" maxlength="254" autocomplete="email">
            @error('gimnasio.correo')<span id="gym-correo-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <label class="sm:col-span-2">Atención en recepción *
            <textarea name="gimnasio[atencion]" @error('gimnasio.atencion') aria-invalid="true" aria-describedby="gym-atencion-error" @enderror rows="3" maxlength="2000" required>{{ $gymValue('atencion') }}</textarea>
            @error('gimnasio.atencion')<span id="gym-atencion-error" class="text-sm text-red-400">{{ $message }}</span>@enderror
        </label>
        <div class="sm:col-span-2 flex flex-wrap items-center gap-4">
            <button class="alpha-btn-primary rounded-xl px-5 py-3 text-sm">Guardar información del gimnasio</button>
            <p class="text-sm text-gray-400">* Obligatorio. Los datos de contacto vacíos no se muestran.</p>
        </div>
    </form>
</section>

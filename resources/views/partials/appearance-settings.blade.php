@php
    $appearance = \App\Support\Apariencia::preferencia($usuarioActual);
    $selectedDesign = in_array(old('design'), \App\Support\Apariencia::DISENOS, true) ? old('design') : $appearance['design'];
    $selectedMode = in_array(old('mode'), ['light', 'dark', 'custom'], true) ? old('mode') : $appearance['mode'];
    $selectedMotion = in_array(old('motion'), \App\Support\Apariencia::MOVIMIENTOS, true) ? old('motion') : $appearance['motion'];
@endphp
<section class="alpha-card rounded-2xl p-5 sm:p-6 lg:col-span-2 scroll-mt-24" id="apariencia">
    <h2 class="font-semibold text-lg">Apariencia</h2>
    <p class="text-sm text-gray-400 mt-1 mb-6">Tu preferencia se guarda en tu cuenta y se conserva al volver a iniciar sesión.</p>
    <form method="POST" action="{{ route('configuracion.apariencia') }}" id="appearance-form" class="alpha-form">
        @csrf
        <fieldset class="appearance-designs mb-6">
            <legend class="font-medium text-sm mb-3">Diseño de la interfaz</legend>
            @foreach(['elegant' => ['Elegante', 'El diseño original, refinado. Dorado, blanco y gris.'], 'green' => ['Verde', 'Carbón, marfil y amarillo suave. Un estilo más moderno.']] as $design => [$label, $description])
                <label class="appearance-design" data-design-choice="{{ $design }}">
                    <input type="radio" name="design" value="{{ $design }}" @checked($selectedDesign === $design)>
                    <span class="design-swatch" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                </label>
            @endforeach
        </fieldset>
        <fieldset class="appearance-modes">
            <legend class="sr-only">Modo de apariencia</legend>
            @foreach(['light' => ['Claro', 'Limpio y luminoso'], 'dark' => ['Oscuro', 'Cómodo con poca luz'], 'custom' => ['Personalizado', 'Tus propios colores']] as $mode => [$label, $description])
                <label class="appearance-mode"><input type="radio" name="mode" value="{{ $mode }}" @checked($selectedMode === $mode)><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span></label>
            @endforeach
        </fieldset>
        <div class="mt-6">
            <label for="appearance-motion" class="font-medium">Movimiento de la interfaz</label>
            <select id="appearance-motion" name="motion" aria-describedby="appearance-motion-help" class="mt-2">
                <option value="system" @selected($selectedMotion === 'system')>Seguir la preferencia del dispositivo</option>
                <option value="reduced" @selected($selectedMotion === 'reduced')>Reducir movimiento</option>
            </select>
            <p id="appearance-motion-help" class="text-xs text-gray-400 mt-2">Reduce animaciones y transiciones. Se conserva en tu cuenta y siempre se respeta la preferencia de movimiento reducido del dispositivo.</p>
        </div>
        <div class="grid md:grid-cols-2 gap-6 mt-6">
            <fieldset id="appearance-colors" class="space-y-3">
                <legend class="font-medium text-sm mb-3">Paleta personalizada</legend>
                @foreach(['primary' => 'Color principal', 'accent' => 'Color secundario / acento', 'background' => 'Fondo principal', 'surface' => 'Tarjetas y paneles', 'text' => 'Texto principal'] as $key => $label)
                    @php($previousColor = old('colors.'.$key))
                    <label class="appearance-color" for="color-{{ $key }}"><span>{{ $label }}</span><input id="color-{{ $key }}" type="color" name="colors[{{ $key }}]" value="{{ is_string($previousColor) && preg_match('/^#[0-9a-fA-F]{6}$/D', $previousColor) ? $previousColor : $appearance['colors'][$key] }}" required><output for="color-{{ $key }}"></output></label>
                @endforeach
                <p class="text-xs text-gray-400">Estos colores se aplican al elegir Personalizado. El texto necesita contraste con ambos fondos; los botones ajustan el color de sus etiquetas. Al volver a Claro u Oscuro, se conserva la última paleta válida.</p>
            </fieldset>
            <div class="appearance-preview" id="appearance-preview" role="region" aria-label="Vista previa de la apariencia">
                <p class="appearance-preview-label">VISTA PREVIA</p>
                <div class="preview-layout"><div class="preview-nav" aria-hidden="true"><b>ALPHA</b><span>Inicio</span><span>Entrenamientos</span><span>Membresías</span></div><div class="appearance-preview-card"><span class="appearance-preview-badge">Tu espacio</span><h3>Un buen día para entrenar</h3><p>Así se verán tus paneles, textos y acciones.</p><span class="appearance-preview-button">Continuar entrenamiento</span></div></div>
            </div>
        </div>
        <p id="appearance-message" role="status" aria-live="polite" class="text-sm mt-4">Los cambios se muestran en la vista previa hasta que los guardes.</p>
        <div class="flex flex-wrap gap-3 mt-5"><button type="submit" class="alpha-btn-primary rounded-xl px-5 py-3 text-sm">Guardar apariencia</button><button type="button" id="appearance-reset" class="alpha-btn-secondary rounded-xl px-5 py-3 text-sm">Restaurar predeterminados</button></div>
    </form>
</section>

@push('scripts')
@vite('resources/js/appearance-settings.js')
@endpush

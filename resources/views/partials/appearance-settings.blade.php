@php($appearance = \App\Support\Apariencia::preferencia($usuarioActual))
<section class="alpha-card rounded-2xl p-5 sm:p-6 lg:col-span-2" id="apariencia">
    <h2 class="font-semibold text-lg">Apariencia</h2>
    <p class="text-sm text-gray-400 mt-1 mb-6">Tu preferencia se guarda en tu cuenta y se conserva al volver a iniciar sesión.</p>
    <form method="POST" action="{{ route('configuracion.apariencia') }}" id="appearance-form" class="alpha-form">
        @csrf
        <fieldset class="appearance-modes">
            <legend class="sr-only">Modo de apariencia</legend>
            @foreach(['light' => ['Claro', 'Limpio y luminoso'], 'dark' => ['Oscuro', 'Cómodo con poca luz'], 'custom' => ['Personalizado', 'Tus propios colores']] as $mode => [$label, $description])
                <label class="appearance-mode"><input type="radio" name="mode" value="{{ $mode }}" @checked(old('mode', $appearance['mode']) === $mode)><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span></label>
            @endforeach
        </fieldset>
        <div class="grid md:grid-cols-2 gap-6 mt-6">
            <fieldset id="appearance-colors" class="space-y-3">
                <legend class="font-medium text-sm mb-3">Paleta personalizada</legend>
                @foreach(['primary' => 'Color principal', 'accent' => 'Color secundario / acento', 'background' => 'Fondo principal', 'surface' => 'Tarjetas y paneles', 'text' => 'Texto principal'] as $key => $label)
                    <label class="appearance-color" for="color-{{ $key }}"><span>{{ $label }}</span><input id="color-{{ $key }}" type="color" name="colors[{{ $key }}]" value="{{ old('colors.'.$key, $appearance['colors'][$key]) }}" required><output for="color-{{ $key }}"></output></label>
                @endforeach
                <p class="text-xs text-gray-400">El texto necesita contraste con ambos fondos. Los botones ajustan automáticamente el color de sus etiquetas.</p>
            </fieldset>
            <div class="appearance-preview" id="appearance-preview" role="region" aria-label="Vista previa de la apariencia">
                <p class="appearance-preview-label">VISTA PREVIA</p>
                <div class="appearance-preview-card"><span class="appearance-preview-badge">Tu espacio</span><h3>Un buen día para entrenar</h3><p>Así se verán tus paneles, textos y acciones.</p><span class="appearance-preview-button">Continuar entrenamiento</span></div>
            </div>
        </div>
        <p id="appearance-message" role="status" aria-live="polite" class="text-sm mt-4">Los cambios se muestran en la vista previa hasta que los guardes.</p>
        <div class="flex flex-wrap gap-3 mt-5"><button type="submit" class="alpha-btn-primary rounded-xl px-5 py-3 text-sm">Guardar apariencia</button><button type="button" id="appearance-reset" class="alpha-btn-secondary rounded-xl px-5 py-3 text-sm">Restaurar predeterminados</button></div>
    </form>
</section>

@php
    $appearanceUser = ($guestAppearance ?? false) ? null : (auth('cliente')->user() ?? auth('web')->user());
    $appearanceConfig = [
        'authenticated' => (bool) $appearanceUser,
        'preference' => \App\Support\Apariencia::preferencia($appearanceUser),
        'palettes' => \App\Support\Apariencia::PALETAS,
        'designPalettes' => ['elegant' => \App\Support\Apariencia::PALETAS, 'green' => \App\Support\Apariencia::PALETAS_VERDE],
        'url' => route('configuracion.apariencia'),
        'csrf' => ($guestAppearance ?? false) ? '' : csrf_token(),
    ];
@endphp
<script>{!! file_get_contents(resource_path('js/theme-core.js')) !!}
AlphaAppearance.init({{ \Illuminate\Support\Js::from($appearanceConfig) }});
</script>

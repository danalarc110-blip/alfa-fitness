@php
    $appearanceUser = auth('cliente')->user() ?? auth('web')->user();
    $appearanceConfig = [
        'authenticated' => (bool) $appearanceUser,
        'preference' => \App\Support\Apariencia::preferencia($appearanceUser),
        'palettes' => \App\Support\Apariencia::PALETAS,
        'url' => route('configuracion.apariencia'),
        'csrf' => csrf_token(),
    ];
@endphp
<script>{!! file_get_contents(resource_path('js/theme-core.js')) !!}
AlphaAppearance.init({{ \Illuminate\Support\Js::from($appearanceConfig) }});
</script>

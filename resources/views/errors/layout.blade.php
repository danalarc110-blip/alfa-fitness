<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · Alpha Fitness</title>
    @include('partials.appearance', ['guestAppearance' => true])
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--alpha-background,#f3f4f6);color:var(--alpha-text,#18212f);font:16px/1.8 'Segoe UI',system-ui,sans-serif}
        main{max-width:520px;margin:20px;padding:38px;background:var(--alpha-surface,#fff);border:1px solid color-mix(in srgb,var(--alpha-text,#18212f) 15%,transparent);border-top:2px solid var(--alpha-primary,#8a6200);border-radius:12px}
        small{color:var(--alpha-primary-text,#8a6200);letter-spacing:.15em}h1{font:34px/1.25 Georgia,serif;letter-spacing:-.8px}html[data-design=green] h1{font-family:inherit;font-weight:600}
        p{opacity:.8}a{display:inline-block;margin-top:16px;padding:12px 20px;background:var(--alpha-primary,#8a6200);color:var(--alpha-on-primary,#fff);border-radius:7px;text-decoration:none;font-weight:600}
        a:focus-visible{outline:3px solid var(--alpha-primary-text,#8a6200);outline-offset:4px}
    </style>
</head>
<body><main><small>ALPHA FITNESS · @yield('code')</small><h1>@yield('title')</h1><p>@yield('message')</p><a href="{{ url('/login') }}">Volver al inicio</a></main></body>
</html>

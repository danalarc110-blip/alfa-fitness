<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Información legal') · Alpha Fitness</title>
    @include('partials.appearance')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>.alpha-legal{overflow-wrap:anywhere;min-width:0}.alpha-legal pre{white-space:pre-wrap;overflow-wrap:anywhere}.alpha-legal h1{font-size:1.65rem}.alpha-legal h2{font-size:1.25rem}.alpha-legal h1,.alpha-legal h2,.alpha-legal h3{font-weight:700;margin:1.5rem 0 .75rem}.alpha-legal p,.alpha-legal ul,.alpha-legal ol{margin-bottom:1rem}.alpha-legal ul,.alpha-legal ol{padding-left:1.5rem;list-style:revert}.alpha-legal a{text-decoration:underline}.alpha-legal table{display:block;overflow:auto}.alpha-legal td,.alpha-legal th{padding:.5rem;border:1px solid #8884}</style>
</head>
<body class="alpha-app">
    <main class="max-w-4xl mx-auto p-4 sm:p-8">
        <header class="flex flex-wrap justify-between gap-4 mb-6"><a href="{{ route('login') }}">← Volver al acceso</a><button type="button" onclick="alphaToggleTema()">◐ Claro / Oscuro</button></header>
        @include('partials.aviso-academico')
        @include('partials.feedback')
        <section class="alpha-card p-5 sm:p-8 alpha-legal">@yield('content')</section>
        <footer class="mt-6 text-sm flex flex-wrap gap-4">@include('partials.legal-links')</footer>
    </main>
</body>
</html>

@extends('legal.publico')
@section('title', 'Documentos de tu cuenta')
@section('content')
<h1>Documentos de tu cuenta</h1><p>El personal no puede aceptar estos documentos por ti. Revisa la información antes de usar tu nueva cuenta.</p>
<form method="POST" action="{{ route('cliente.legal.aceptar') }}" class="alpha-form space-y-5">@csrf @include('partials.consentimiento')<button class="alpha-btn-primary px-4 py-3">Aceptar y continuar</button></form>
<form method="POST" action="{{ route('cliente.logout') }}" class="mt-5">@csrf<button class="alpha-btn-secondary px-4 py-3">Cerrar sesión</button></form>
@endsection

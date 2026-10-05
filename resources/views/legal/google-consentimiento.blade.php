@extends('legal.publico')
@section('title', 'Registro con Google')
@section('content')
<h1>Completar registro con Google</h1>
<p>Antes de crear tu nueva cuenta, revisa los documentos del servicio. No se ha creado una cuenta todavía.</p>
<form method="POST" action="{{ route('cliente.google.aceptar') }}" class="alpha-form space-y-5">
    @csrf
    @include('partials.consentimiento')
    <button type="submit" class="alpha-btn-primary px-4 py-3">Crear cuenta con Google</button>
</form>
@endsection

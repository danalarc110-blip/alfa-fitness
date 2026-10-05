@extends('legal.publico')
@section('content')
{{-- HTML generated only from repository Markdown with raw HTML and unsafe links disabled. --}}
{!! $html !!}
@if($documento === 'derechos')
<h2>Enviar una solicitud</h2>
<p>No adjuntes documentos de identidad, contraseñas ni información médica. El responsable verificará tu identidad por separado.</p>
<form method="POST" action="{{ route('legal.solicitud') }}" class="alpha-form space-y-4">
    @csrf
    <label>Nombre<input name="nombre" maxlength="255" value="{{ old('nombre') }}" required></label>
    <label>Correo de contacto<input name="correo" type="email" maxlength="255" value="{{ old('correo') }}" required></label>
    <label>Solicitud<select name="tipo" required>@foreach(\App\Http\Controllers\LegalController::TIPOS as $tipo)<option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ ucfirst($tipo) }}</option>@endforeach</select></label>
    <label>Descripción<textarea name="detalle" maxlength="3000" required>{{ old('detalle') }}</textarea></label>
    <p>Tu solicitud se tratará conforme al <a href="{{ route('legal.documento', 'privacidad') }}">aviso de privacidad</a>. Ejercer tus derechos no requiere aceptar tratamientos opcionales.</p>
    <button type="submit" class="alpha-btn-primary px-4 py-3">Enviar solicitud</button>
</form>
@endif
@endsection

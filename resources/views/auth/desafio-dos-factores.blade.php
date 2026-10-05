@extends('legal.publico')
@section('title', 'Verificar acceso')
@section('content')
<h1>Verificar acceso del administrador</h1><p>Introduce un código del autenticador o un código de recuperación.</p><form method="POST" action="{{ route('dos-factores.verificar') }}" class="alpha-form space-y-4">@csrf<label>Código<input name="codigo" maxlength="32" autocomplete="one-time-code" required autofocus></label><button class="alpha-btn-primary px-4 py-3">Verificar e ingresar</button></form>
@endsection

@extends('layouts.app')
@section('title', 'Verificación en dos pasos')
@section('content')
<section class="alpha-card p-6 max-w-2xl"><p class="mb-5">Opcional para el administrador. Usa una aplicación autenticadora con clave manual, SHA-1, seis dígitos y períodos de 30 segundos. Mantén la hora automática del teléfono. No compartas la clave ni los códigos.</p>
@if(session('two_factor_recovery_plain'))
<div class="mb-5"><h2 class="font-bold">Códigos de recuperación: se muestran solo esta vez</h2><p>Guárdalos en un lugar privado fuera de este equipo. Cada código sirve una sola vez.</p><pre class="my-3">{{ implode("\n", session('two_factor_recovery_plain')) }}</pre></div>
@endif
@if(auth()->user()->two_factor_confirmed_at)
<p class="mb-4">Estado: activada. Los accesos nuevos pedirán un código.</p>
<form method="POST" action="{{ route('dos-factores.desactivar') }}" class="alpha-form space-y-4" onsubmit="return confirm('¿Desactivar la verificación en dos pasos?')">@csrf @method('DELETE')<label>Contraseña actual<input type="password" name="password_actual" autocomplete="current-password" required></label><label>Código actual o de recuperación<input name="codigo" maxlength="32" required autocomplete="one-time-code"></label><button class="alpha-btn-secondary px-4 py-3">Desactivar</button></form>
@elseif(session('two_factor_setup') && session('two_factor_setup.expires') >= now()->timestamp)
<p>En tu aplicación autenticadora pulsa «Añadir cuenta» y «Introducir clave». Nombre de cuenta: Alpha Fitness. Clave:</p><code class="block break-all my-4 select-all">{{ session('two_factor_setup.secret') }}</code>
<form method="POST" action="{{ route('dos-factores.activar') }}" class="alpha-form space-y-4">@csrf<label>Contraseña actual<input type="password" name="password_actual" required autocomplete="current-password"></label><label>Código de seis dígitos<input name="codigo" pattern="[0-9]{6}" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required></label><button class="alpha-btn-primary px-4 py-3">Confirmar y activar</button></form>
@else
<p class="mb-4">Estado: desactivada. El acceso actual no ha cambiado.</p><form method="POST" action="{{ route('dos-factores.preparar') }}" class="alpha-form space-y-4">@csrf<label>Contraseña actual<input type="password" name="password_actual" required autocomplete="current-password"></label><button class="alpha-btn-primary px-4 py-3">Preparar autenticador</button></form>
@endif
</section>
@endsection

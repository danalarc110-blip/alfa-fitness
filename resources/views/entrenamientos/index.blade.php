@extends('layouts.app', ['active' => 'entrenamientos'])
@section('title', 'Entrenamientos')
@section('page-header')
<header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8" data-animate="header">
    <div><h1 class="text-2xl font-semibold mb-2">Entrenamientos</h1><p class="text-gray-400 text-sm">Gestiona, crea y visualiza tus planes de entrenamiento.</p></div>
    <form method="POST" action="{{ route('entrenamientos.crear') }}">@csrf<button class="alpha-btn-primary px-5 py-3"><span aria-hidden="true">＋</span> Crear nueva rutina</button></form>
</header>
@endsection
@section('content')
<div class="flex justify-between items-center mb-5"><h2 class="text-lg font-semibold">Mis rutinas</h2><span class="text-xs text-gray-400">{{ $rutinas->total() }} {{ $rutinas->total() === 1 ? 'rutina' : 'rutinas' }}</span></div>
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
    @forelse($rutinas as $rutina)
    <a class="alpha-card alpha-card-interactive alpha-routine-card" href="{{ route('entrenamientos.editar', $rutina) }}" data-animate="card">
        <div class="alpha-routine-cover" aria-hidden="true"><span>{{ str_pad($rutinas->firstItem() + $loop->index, 2, '0', STR_PAD_LEFT) }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"><path d="M3 8v8m4-11v14m10-14v14m4-11v8M7 12h10"/></svg></div>
        <div class="alpha-routine-body"><span class="alpha-routine-level">{{ $rutina->nivel ?? 'General' }}</span><h2>{{ $rutina->nombre }}</h2><p>{{ $rutina->objetivo ?: 'Sin objetivo definido' }}</p><div class="alpha-routine-stats"><span>{{ $rutina->dias->count() }} días</span><span>{{ $rutina->totalEjercicios() }} ejercicios</span></div><span class="alpha-routine-edit">Editar rutina <span aria-hidden="true">→</span></span></div>
    </a>
    @empty
    <section class="col-span-full alpha-card p-12 text-center"><h2 class="text-lg font-semibold mb-2">Aún no tienes rutinas creadas</h2><p class="text-gray-400 text-sm">Haz clic en Crear nueva rutina para comenzar tu plan de entrenamiento.</p></section>
    @endforelse
</div>
<div class="mt-5">{{ $rutinas->links() }}</div>
@endsection

@extends('layouts.app', ['active' => 'mis-clientes'])
@section('title', 'Progreso: '.$cliente->nombre)
@section('eyebrow', 'Seguimiento de clientes')
@section('content')
    <a href="{{ route('entrenador.clientes') }}" class="alpha-btn-secondary inline-block px-4 py-2 mb-5">Volver a clientes</a>
    @include('progreso.panel')
@endsection

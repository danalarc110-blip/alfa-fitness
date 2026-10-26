@extends('layouts.app', ['active' => 'progreso'])
@section('title', 'Mi progreso')
@section('eyebrow', 'Cada registro cuenta')
@section('content')
    @include('progreso.panel')
@endsection

@extends('layouts.app')

@section('title','Acceso no permitido')

@section('content')
    <section class="panel">
        <h1 class="fs-3">Acceso no permitido</h1>
        <p>No tienes permiso para acceder a este recurso.</p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

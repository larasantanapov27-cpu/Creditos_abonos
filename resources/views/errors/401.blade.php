@extends('layouts.app')

@section('title','Inicia sesión')

@section('content')
    <section class="panel">
        <h1 class="fs-3">Inicia sesión</h1>
        <p>Necesitas iniciar sesión para continuar.</p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

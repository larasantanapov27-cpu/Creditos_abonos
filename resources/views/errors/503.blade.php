@extends('layouts.app')

@section('title','Sistema en mantenimiento')

@section('content')
    <section class="panel">
        <h1 class="fs-3">Sistema en mantenimiento</h1>
        <p>El sistema está temporalmente en mantenimiento. Intenta de nuevo más tarde.</p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

@extends('layouts.app')

@section('title','Espera un momento')

@section('content')
    <section class="panel">
        <h1 class="fs-3">Espera un momento</h1>
        <p>Se recibieron demasiadas solicitudes. Espera un minuto antes de intentar de nuevo.</p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

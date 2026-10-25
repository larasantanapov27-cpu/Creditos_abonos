@extends('layouts.app')

@section('title','Operación no disponible')

@section('content')
    <section class="panel">
        <h1 class="fs-3">Operación no disponible</h1>
        <p>Abre el formulario correspondiente para realizar esta operación.</p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

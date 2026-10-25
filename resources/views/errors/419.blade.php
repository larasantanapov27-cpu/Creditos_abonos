@extends('layouts.app')

@section('title','Sesión vencida')

@section('content')
    <section class="panel">
        <h1 class="fs-3">Tu sesión venció</h1>
        <p>
            Abre el formulario de nuevo antes de continuar. Si estabas registrando un abono, comprueba primero el historial para evitar repetirlo.
        </p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

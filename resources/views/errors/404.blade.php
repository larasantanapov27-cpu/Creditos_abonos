@extends('layouts.app')

@section('title','Página no encontrada')

@section('content')
    <section class="panel">
        <h1 class="fs-3">No encontramos esta página</h1>
        <p>El registro o la dirección que buscas no está disponible.</p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

@extends('layouts.app')

@section('title','No se pudo completar la operación')

@section('content')
    <section class="panel">
        <h1 class="fs-3">No se pudo completar la operación</h1>
        <p>
            Comprueba que la base de datos esté encendida e intenta de nuevo. Si el problema continúa, revisa el registro local del sistema.
        </p>
        <a class="btn btn-primary" href="{{ url('/') }}">Volver al inicio</a>
    </section>
@endsection

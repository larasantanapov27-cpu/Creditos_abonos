@extends('layouts.app')

@section('title','Bienvenido')

@section('content')
    <section class="panel">
        <h1>Créditos y Abonos</h1>
        <a class="btn btn-primary" href="{{ route('login') }}">Iniciar sesión</a>
    </section>
@endsection

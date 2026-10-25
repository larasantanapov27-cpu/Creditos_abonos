@extends('layouts.app')

@section('title','Iniciar sesión')

@section('content')
    <div class="auth-grid">
        <section class="auth-story">
            <span class="eyebrow">CADA MOVIMIENTO, EN SU LUGAR</span>
            <h1>
                Un espacio claro
                <br>
                para tus
                <span>cuentas.</span>
            </h1>
            <p>Administra clientes, registra créditos y lleva el control de cada abono desde tu equipo.</p>
            <div class="story-card">
                <div class="d-flex justify-content-between">
                    <span>Tu negocio, organizado</span>
                    <x-icon name="wallet" />
                </div>
                <strong>
                    Menos pendientes.
                    <br>
                    Más claridad.
                </strong>
                <div class="story-bars">
                    <i></i>
                    <i></i>
                    <i></i>
                    <i></i>
                    <i></i>
                    <i></i>
                    <i></i>
                </div>
                <small>Clientes · Créditos · Abonos</small>
            </div>
            <span class="story-foot">
                <span class="online-dot"></span>
                Recursos disponibles sin internet
            </span>
        </section>
        <section class="auth-form">
            <span class="auth-symbol">
                <x-icon name="wallet" />
            </span>
            <p class="eyebrow mt-4">BIENVENIDO A TU ESPACIO</p>
            <h2>Inicia sesión</h2>
            <p class="text-muted">Ingresa tus datos para continuar.</p>
            <form action="{{ route('login.store') }}" method="POST" class="mt-4">
                @csrf
                <x-field name="email" type="email" label="Correo electrónico" autocomplete="email" maxlength="255" autofocus/>
                <div class="password-field">
                    <x-field name="password" label="Contraseña" type="password" autocomplete="current-password"/>
                    <button class="password-toggle" type="button" data-password="password" aria-pressed="false">
                        Mostrar
                    </button>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">
                    Entrar a mi espacio
                    <x-icon name="arrow" />
                </button>
            </form>
            <p class="auth-switch">
                ¿Primera vez aquí?
                <a href="{{ route('register') }}">Crear una cuenta</a>
            </p>
            <p class="small text-muted">Acceso para el administrador o encargado.</p>
        </section>
    </div>
@endsection

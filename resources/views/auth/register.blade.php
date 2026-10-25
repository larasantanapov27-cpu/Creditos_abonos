@extends('layouts.app')

@section('title','Crear cuenta')

@section('content')
    <div class="auth-grid">
        <section class="auth-story">
            <span class="eyebrow">TODO EMPIEZA CON ORDEN</span>
            <h1>
                Tus cuentas,
                <br>
                con una nueva
                <br>
                <span>perspectiva.</span>
            </h1>
            <p>Crea tu cuenta de encargado y reúne la información de tu negocio en un solo lugar.</p>
            <div class="story-card compact">
                <x-icon name="check" />
                <strong>
                    Un historial claro.
                    <br>
                    En cada paso.
                </strong>
                <small>Consulta saldos y conserva tus movimientos.</small>
            </div>
            <p class="story-foot">Versión académica local · Cuentas de encargado</p>
        </section>
        <section class="auth-form">
            <p class="eyebrow">TU NUEVO ESPACIO DE TRABAJO</p>
            <h2>Crea tu cuenta</h2>
            <p class="text-muted">Completa tus datos para comenzar.</p>
            <form action="{{ route('register.store') }}" method="POST">
                @csrf
                <x-field name="name" label="Nombre completo" autocomplete="name" maxlength="100"/>
                <div class="row">
                    <x-field
                        class="col-md-6"
                        name="username"
                        label="Nombre de usuario"
                        autocomplete="username"
                        minlength="3"
                        maxlength="50"
                    />
                    <x-field
                        class="col-md-6"
                        name="email"
                        label="Correo electrónico"
                        type="email"
                        autocomplete="email"
                        maxlength="255"
                    />
                </div>
                <div class="password-field">
                    <x-field
                        name="password"
                        label="Contraseña (mínimo 8 caracteres)"
                        type="password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="72"
                    />
                    <button type="button" class="password-toggle" data-password="password" aria-pressed="false">
                        Mostrar
                    </button>
                </div>
                <x-field
                    name="password_confirmation"
                    label="Confirma tu contraseña"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    maxlength="72"
                />
                <button class="btn btn-primary w-100" type="submit">
                    Crear cuenta
                    <x-icon name="arrow" />
                </button>
            </form>
            <p class="auth-switch">
                ¿Ya tienes una cuenta?
                <a href="{{ route('login') }}">Iniciar sesión</a>
            </p>
        </section>
    </div>
@endsection

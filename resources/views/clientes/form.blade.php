@extends('layouts.app')

@section('title',$cliente->exists?'Editar cliente':'Registrar cliente')

@section('content')
    <x-page-heading
        :title="$cliente->exists?'Editar cliente':'Un nuevo cliente'"
        description="Completa los datos personales y de contacto. Los campos con * son obligatorios."
    >
        <a class="btn btn-light" href="{{ route('clientes.index') }}">Volver a clientes</a>
    </x-page-heading>

    <form
        method="POST"
        action="{{ $cliente->exists?route('clientes.update',$cliente):route('clientes.store') }}"
        class="panel form-panel"
    >
        @csrf
        @if($cliente->exists)
            @method('PUT')
        @endif
        <h2 class="form-section-title">Datos personales</h2>
        <div class="row">
            <x-field class="col-md-4" name="nombre" label="Nombre(s)" :value="$cliente->nombre" maxlength="25"/>
            <x-field
                class="col-md-4"
                name="apellido_paterno"
                label="Apellido paterno"
                :value="$cliente->apellido_paterno"
                maxlength="25"
            />
            <x-field
                class="col-md-4"
                name="apellido_materno"
                label="Apellido materno"
                :value="$cliente->apellido_materno"
                maxlength="25"
            />
            <x-field class="col-md-6" name="curp" label="CURP" :value="$cliente->curp" minlength="18" maxlength="18"/>
            <x-field
                class="col-md-6"
                name="telefono"
                label="Teléfono"
                type="tel"
                :value="$cliente->telefono"
                maxlength="20"
            />
            <x-field
                class="col-12"
                name="correo_electronico"
                label="Correo electrónico"
                type="email"
                :value="$cliente->correo_electronico"
                maxlength="50"
            />
        </div>
        <h2 class="form-section-title">Domicilio</h2>
        @if($estados->isEmpty()||$municipios->isEmpty())
            <div class="alert alert-info">
                Primero agrega el estado y sus municipios en
                <a href="{{ route('catalogos.index') }}">Catálogos</a>
                .
            </div>
        @endif
        <div class="row">
            <div class="col-md-6 field-group">
                <label for="id_estado" class="form-label">Estado *</label>
                <select id="id_estado" name="id_estado" class="form-select" required data-state-select>
                    <option value="">Selecciona un estado</option>
                    @foreach($estados as $e)
                        <option value="{{ $e->id_estado }}" @selected(old('id_estado',$cliente->id_estado)==$e->id_estado)>
                            {{ $e->nombre_estado }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 field-group">
                <label for="id_municipio" class="form-label">Municipio *</label>
                <select id="id_municipio" name="id_municipio" class="form-select" required data-city-select>
                    <option value="">Selecciona un municipio</option>
                    @foreach($municipios as $m)
                        <option
                            value="{{ $m->id_municipio }}"
                            data-state="{{ $m->id_estado }}"
                            @selected(old('id_municipio',$cliente->id_municipio)==$m->id_municipio)
                        >
                            {{ $m->nombre_municipio }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-field class="col-md-8" name="direccion" label="Dirección" :value="$cliente->direccion" maxlength="50"/>
            <x-field
                class="col-md-4"
                name="codigo_postal"
                label="Código postal"
                :value="$cliente->codigo_postal"
                inputmode="numeric"
                pattern="[0-9]{5}"
                maxlength="5"
            />
        </div>
        <div class="form-actions">
            <a href="{{ route('clientes.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                Guardar cliente
                <x-icon name="check"/>
            </button>
        </div>
    </form>
@endsection

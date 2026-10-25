@extends('layouts.app')

@section('title','Clientes')

@section('content')
    <x-page-heading
        title="Tus clientes"
        description="La información y el historial de cada persona, en un solo lugar."
    >
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <x-icon name="plus" />
            Registrar cliente
        </a>
    </x-page-heading>

    <section class="panel">
        <form class="filter-bar" method="GET">
            <label class="search-field">
                <x-icon name="search"/>
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Nombre, teléfono, CURP o identificador"
                    aria-label="Buscar cliente"
                    maxlength="100"
                >
            </label>
            <select name="activo" class="form-select filter-select" aria-label="Estado del cliente">
                <option value="">Todos los clientes</option>
                <option value="1" @selected(request('activo')==='1')>Activos</option>
                <option value="0" @selected(request('activo')==='0')>Inactivos</option>
            </select>
            <button class="btn btn-dark" type="submit">Buscar</button>
            <a class="btn btn-light" href="{{ route('clientes.index') }}">Limpiar</a>
        </form>
        <p class="d-md-none small text-muted no-print mb-2">Desliza la tabla para ver todas las columnas.</p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>CURP</th>
                        <th>Créditos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $c)
                        <tr>
                            <td>
                                <a class="fw-semibold" href="{{ route('clientes.show',$c) }}">
                                    {{ $c->nombre_completo }}
                                </a>
                                <small class="d-block text-muted">
                                    Cliente #{{ $c->id_cliente }}
                                </small>
                            </td>
                            <td>
                                {{ $c->telefono }}
                                <small class="d-block text-muted">
                                    {{ $c->correo_electronico }}
                                </small>
                            </td>
                            <td>
                                {{ $c->curp }}
                            </td>
                            <td>
                                {{ $c->creditos_count }}
                            </td>
                            <td>
                                <x-status :value="$c->activo?'Activo':'Inactivo'" />
                            </td>
                            <td>
                                <a class="btn btn-sm btn-light" href="{{ route('clientes.show',$c) }}">
                                    Ver historial
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <x-icon name="users"/>
                                <p>No hay clientes para mostrar.</p>
                                <a class="btn btn-primary" href="{{ route('clientes.create') }}">
                                    Registrar mi primer cliente
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $clientes->links() }}
    </section>
@endsection

@extends('layouts.app')

@section('title','Catálogos')

@section('content')
    <x-page-heading
        title="Las bases de tu información"
        description="Administra catálogos y conserva los registros que ya tienen movimientos."
    />
    <div class="catalog-tabs no-print">
        @foreach($map as $key=>$cfg)
            <a class="btn {{ $tipo===$key?'btn-dark':'btn-light' }}" href="{{ route('catalogos.index',['tipo'=>$key]) }}">
                {{ $cfg[2] }}
            </a>
        @endforeach
    </div>
    <div class="alert alert-info small">
        Los estados de abono “Aplicado” y “Cancelado” están protegidos por el sistema. Las tasas son referencias: no incrementan el total pactado.
    </div>
    <section class="panel">
        <h2 class="form-section-title">{{ $titulo }}</h2>
        <form method="POST" action="{{ route('catalogos.store',$tipo) }}" class="filter-bar no-print">
            @csrf
            <div class="flex-grow-1">
                <label for="valor" class="form-label">{{ $tipo==='tasa_intereses'?'Tasa (%)':'Nombre' }} *</label>
                <input
                    class="form-control"
                    name="valor"
                    id="valor"
                    value="{{ old('valor') }}"
                    required
                    maxlength="{{ $tipo==='tipo_pagos'?25:50 }}"
                    @if($tipo==='tasa_intereses')type="number"
                    step="0.0001"
                    min="0"
                    max="999.9999"@endif
                >
            </div>
            @if($tipo==='municipios')
                <div>
                    <label for="id_estado" class="form-label">Estado *</label>
                    <select name="id_estado" id="id_estado" class="form-select" required>
                        <option value="">Selecciona</option>
                        @foreach($estados as $e)
                            <option value="{{ $e->id_estado }}">
                                {{ $e->nombre_estado }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button class="btn btn-primary align-self-end" type="submit">
                <x-icon name="plus"/>
                Agregar
            </button>
        </form>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre / valor</th>
                        @if($tipo==='municipios')
                            <th>Estado</th>
                        @endif
                        <th class="no-print">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filas as $f)
                        <tr>
                            <td>
                                {{ $f->$pk }}
                            </td>
                            <td>
                                {{ $f->$campo }}
                            </td>
                            @if($tipo==='municipios')
                                <td>
                                    {{ $estados->firstWhere('id_estado',$f->id_estado)?->nombre_estado ?? 'Sin asignar' }}
                                </td>
                            @endif
                            <td class="no-print">
                                <div class="d-flex gap-2">
                                    <button
                                        class="btn btn-sm btn-light"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editCatalog{{ $f->$pk }}"
                                    >
                                        Editar
                                    </button>
                                    <form
                                        action="{{ route('catalogos.destroy',[$tipo,$f->$pk]) }}"
                                        method="POST"
                                        data-confirm="¿Eliminar este elemento del catálogo? Solo se eliminará si no está utilizado."
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">Todavía no hay elementos. Agrega el primero.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $filas->links() }}
    </section>
    @foreach($filas as $f)
        <div
            class="modal fade"
            id="editCatalog{{ $f->$pk }}"
            tabindex="-1"
            aria-labelledby="editTitle{{ $f->$pk }}"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('catalogos.update',[$tipo,$f->$pk]) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="editTitle{{ $f->$pk }}">
                            Editar elemento
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <label for="valor{{ $f->$pk }}" class="form-label">
                            Nombre / valor *
                        </label>
                        <input
                            id="valor{{ $f->$pk }}"
                            name="valor"
                            class="form-control"
                            value="{{ $f->$campo }}"
                            required
                            maxlength="{{ $tipo==='tipo_pagos'?25:50 }}"
                            @if($tipo==='tasa_intereses')type="number"
                            min="0"
                            max="999.9999"
                            step="0.0001"@endif
                        >
                        @if($tipo==='municipios')
                            <label class="form-label mt-3" for="estado{{ $f->$pk }}">
                                Estado *
                            </label>
                            <select class="form-select" id="estado{{ $f->$pk }}" name="id_estado" required>
                                @foreach($estados as $e)
                                    <option value="{{ $e->id_estado }}" @selected($e->id_estado==$f->id_estado)>
                                        {{ $e->nombre_estado }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <p class="small text-muted mt-3">
                            Los elementos utilizados están protegidos para conservar el historial.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Volver</button>
                        <button class="btn btn-primary" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

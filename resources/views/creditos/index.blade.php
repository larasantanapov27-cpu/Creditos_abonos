@extends('layouts.app')

@section('title',request()->routeIs('consultas')?'Consultas e historiales':'Créditos')

@section('content')
    <x-page-heading
        :title="request()->routeIs('consultas')?'Consultas e historiales':'Tus créditos'"
        description="Consulta los montos pactados, los abonos y los saldos de cada crédito."
    >
        <button class="btn btn-light" type="button" data-print>
            <x-icon name="print"/>
            Imprimir
        </button>
        <a href="{{ route('creditos.create') }}" class="btn btn-primary">
            <x-icon name="plus"/>
            Nuevo crédito
        </a>
    </x-page-heading>

    <section class="panel">
        <form class="filter-bar no-print" method="GET">
            <label class="search-field">
                <x-icon name="search"/>
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Nombre o identificador del cliente, folio del crédito"
                    aria-label="Buscar crédito"
                    maxlength="100"
                >
            </label>
            <select name="estado" class="form-select filter-select" aria-label="Estado del crédito">
                <option value="">Todos los estados</option>
                @foreach(['Pendiente','Liquidado','Vencido'] as $e)
                    <option @selected(request('estado')===$e)>{{ $e }}</option>
                @endforeach
            </select>
            @if(request('id_cliente'))
                <input type="hidden" name="id_cliente" value="{{ request('id_cliente') }}">
            @endif
            <button type="submit" class="btn btn-dark">Consultar</button>
            <a href="{{ url()->current() }}" class="btn btn-light">
                Limpiar
            </a>
        </form>
        <x-credit-table :creditos="$creditos" />
        {{ $creditos->links() }}
    </section>
@endsection

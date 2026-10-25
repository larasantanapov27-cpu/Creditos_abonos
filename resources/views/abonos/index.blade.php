@extends('layouts.app')

@section('title','Abonos')

@section('content')
    <x-page-heading
        title="Cada abono cuenta"
        description="Consulta los movimientos aplicados y cancelados, sin perder su historial."
    >
        <button type="button" class="btn btn-light" data-print>
            <x-icon name="print"/>
            Imprimir
        </button>
        <a href="{{ route('abonos.create') }}" class="btn btn-primary">
            <x-icon name="plus"/>
            Registrar abono
        </a>
    </x-page-heading>

    <section class="panel">
        <form method="GET" class="filter-bar no-print">
            <div>
                <label class="form-label" for="desde">Desde</label>
                <input type="date" name="desde" id="desde" value="{{ request('desde') }}" class="form-control">
            </div>
            <div>
                <label class="form-label" for="hasta">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}" class="form-control">
            </div>
            <button type="submit" class="btn btn-dark align-self-end">Filtrar</button>
            <a class="btn btn-light align-self-end" href="{{ route('abonos.index') }}">Limpiar</a>
        </form>
        <x-payment-table :abonos="$abonos" />
        {{ $abonos->links() }}
    </section>
@endsection

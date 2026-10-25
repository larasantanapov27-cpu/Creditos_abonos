@extends('layouts.app')

@section('title','Detalle de crédito')

@section('content')
    <x-page-heading
        :title="'Crédito CR-'.str_pad($credito->id_credito,5,'0',STR_PAD_LEFT)"
        :description="$credito->cliente->nombre_completo.' · '.$credito->motivo_credito"
    >
        <button type="button" class="btn btn-light" data-print>
            <x-icon name="print"/>
            Estado de cuenta
        </button>
        <a class="btn btn-dark" href="{{ route('creditos.edit',$credito) }}">Editar</a>
        @if((float)$credito->saldo>0)
            <a class="btn btn-primary" href="{{ route('abonos.create',['id_credito'=>$credito->id_credito]) }}">
                <x-icon name="plus"/>
                Registrar abono
            </a>
        @endif
    </x-page-heading>

    <section class="panel mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <a href="{{ route('clientes.show',$credito->cliente) }}">
                Ver historial del cliente
                <x-icon name="arrow"/>
            </a>
            <x-status :value="$credito->estado"/>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <p class="text-muted">Total pactado</p>
                <h2 class="metric">
                    ${{ number_format($credito->monto_credito,2) }}
                </h2>
            </div>
            <div class="col-md-4">
                <p class="text-muted">Total abonado</p>
                <h2 class="metric">
                    ${{ number_format($credito->total_abonado,2) }}
                </h2>
            </div>
            <div class="col-md-4">
                <p class="text-muted">Saldo pendiente</p>
                <h2 class="metric text-primary">
                    ${{ number_format($credito->saldo,2) }}
                </h2>
            </div>
        </div>
        <hr>
        <div class="d-flex flex-wrap gap-4 small">
            <span>
                Inicio:
                <strong>
                    {{ $credito->fecha_aprobacion->format('d/m/Y') }}
                </strong>
            </span>
            <span>
                Vencimiento:
                <strong>
                    {{ $credito->fecha_vencimiento->format('d/m/Y') }}
                </strong>
            </span>
            <span>
                {{ $credito->cantidad_pagos }} pagos · {{ $credito->periodicidad }}
            </span>
        </div>
    </section>
    <section class="panel mb-3">
        <div class="section-heading">
            <h2>Calendario pactado</h2>
            <span class="small text-muted">Las cuotas no equivalen a abonos recibidos</span>
        </div>
        @php($acumulado=0)
        <div class="table-responsive calendar-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cuota</th>
                        <th>Fecha</th>
                        <th>Importe</th>
                        <th>Cobertura por abonos aplicados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($credito->cuotas as $cuota)
                        @php($acumulado+=\App\Services\CreditoService::centavos($cuota->importe))
                        @php($cubierta=$credito->abonadoCentavos()>=$acumulado)
                        <tr>
                            <td>
                                {{ $cuota->numero }}
                            </td>
                            <td>
                                {{ $cuota->fecha->format('d/m/Y') }}
                            </td>
                            <td>
                                ${{ number_format($cuota->importe,2) }}
                            </td>
                            <td>
                                <x-status :value="$cubierta?'Liquidado':($cuota->fecha->lt(today())?'Cuota atrasada':'Programada')"/>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="small text-muted mb-0">
            La cobertura se asigna a las cuotas más antiguas. Una cuota atrasada no implica que el plazo completo del crédito haya vencido.
        </p>
    </section>
    <section class="panel">
        <div class="section-heading">
            <h2>Historial de abonos</h2>
            <span>
                {{ $credito->abonos->count() }} movimientos
            </span>
        </div>
        <x-payment-table :abonos="$credito->abonos->sortByDesc('numero_pago')" />
    </section>
@endsection

@extends('layouts.app')

@section('title','Detalle de cliente')

@section('content')
    <x-page-heading
        :title="$cliente->nombre_completo"
        :description="'Cliente #'.$cliente->id_cliente.' · Registrado el '.$cliente->fecha_registro_cliente->format('d/m/Y')"
    >
        <button class="btn btn-light" type="button" data-print>
            <x-icon name="print"/>
            Imprimir
        </button>
        <a class="btn btn-dark" href="{{ route('clientes.edit',$cliente) }}">Editar datos</a>
        @if($cliente->activo)
            <a class="btn btn-primary" href="{{ route('creditos.create',['id_cliente'=>$cliente->id_cliente]) }}">
                Nuevo crédito
            </a>
        @endif
    </x-page-heading>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <section class="panel h-100">
                <div class="section-heading">
                    <h2>Información personal</h2>
                    <x-status :value="$cliente->activo?'Activo':'Inactivo'" />
                </div>
                <dl class="data-list">
                    <dt>Teléfono</dt>
                    <dd>
                        {{ $cliente->telefono }}
                    </dd>
                    <dt>Correo</dt>
                    <dd>
                        {{ $cliente->correo_electronico }}
                    </dd>
                    <dt>CURP</dt>
                    <dd>
                        {{ $cliente->curp }}
                    </dd>
                    <dt>Domicilio</dt>
                    <dd>
                        {{ $cliente->direccion }}, C.P. {{ $cliente->codigo_postal }}
                    </dd>
                </dl>
                <div class="d-flex flex-wrap gap-2 no-print">
                    <form
                        method="POST"
                        action="{{ route('clientes.toggle',$cliente) }}"
                        data-confirm="{{ $cliente->activo?'¿Desactivar este cliente? Se conservará su historial y podrás recibir abonos a sus deudas.':'¿Reactivar este cliente?' }}"
                    >
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-light">
                            {{ $cliente->activo?'Desactivar':'Reactivar' }}
                        </button>
                    </form>
                    @if($cliente->creditos->isEmpty())
                        <form
                            method="POST"
                            action="{{ route('clientes.destroy',$cliente) }}"
                            data-confirm="¿Eliminar este cliente sin créditos? Esta operación no se puede deshacer."
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">Eliminar cliente</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-lg-7">
            <section class="panel h-100">
                <p class="eyebrow">ESTADO DE CUENTA ACUMULADO</p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <p class="text-muted mb-1">Saldo pendiente</p>
                        <h2 class="metric">
                            ${{ number_format($cliente->creditos->sum(fn($c)=>(float)$c->saldo),2) }}
                        </h2>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-muted mb-1">Total abonado</p>
                        <h2 class="metric">
                            ${{ number_format($cliente->creditos->sum(fn($c)=>(float)$c->total_abonado),2) }}
                        </h2>
                    </div>
                </div>
                <p class="small text-muted mt-4 mb-0">
                    Los importes consideran únicamente abonos aplicados. Los movimientos cancelados permanecen en el historial.
                </p>
            </section>
        </div>
    </div>
    <section class="panel">
        <div class="section-heading">
            <h2>Historial de créditos</h2>
            <span>
                {{ $cliente->creditos->count() }} registros
            </span>
        </div>
        <x-credit-table :creditos="$cliente->creditos" />
    </section>
@endsection

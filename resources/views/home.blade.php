@extends('layouts.app')

@section('title','Inicio')

@section('content')
    <x-page-heading
        :title="'Hola, '.auth()->user()->name"
        description="Una mirada a tus cuentas, para empezar el día."
    >
        <a class="btn btn-dark" href="{{ route('abonos.create') }}">
            <x-icon name="plus" />
            Registrar abono
        </a>
    </x-page-heading>

    <div class="row g-3 overview-row">
        <div class="col-xl-5">
            <section class="panel balance-panel">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="eyebrow">SALDO PENDIENTE GLOBAL</span>
                    <span class="circle-icon">
                        <x-icon name="wallet" />
                    </span>
                </div>
                <h2>
                    ${{ number_format($stats['saldo']/100,2) }}
                    <small>MXN</small>
                </h2>
                <p>Por cobrar en créditos pendientes y vencidos</p>
                <div class="balance-bottom">
                    <span>
                        <i class="online-dot"></i>
                        Total pactado
                        <strong>${{ number_format($stats['monto']/100,2) }}</strong>
                    </span>
                    <a href="{{ route('consultas') }}" aria-label="Consultar créditos">
                        <x-icon name="arrow" />
                    </a>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="panel h-100">
                <div class="section-heading">
                    <h2>Estado de los créditos</h2>
                    <x-icon name="clock" />
                </div>
                @foreach(['Pendiente','Liquidado','Vencido'] as $estado)
                    <a class="state-row" href="{{ route('creditos.index',['estado'=>$estado]) }}">
                        <x-status :value="$estado"/>
                        <strong>{{ $stats[$estado] }}</strong>
                    </a>
                @endforeach
            </section>
        </div>
        <div class="col-xl-3">
            <section class="panel h-100 collected-panel">
                <span class="circle-icon">
                    <x-icon name="receipt" />
                </span>
                <p class="mt-3 mb-1">Total abonado</p>
                <h2>${{ number_format($stats['abonado']/100,2) }}</h2>
                <small>Solo movimientos aplicados</small>
                <div class="collected-line"></div>
                <span class="small">{{ $stats['activos'] }} clientes activos</span>
            </section>
        </div>
    </div>
    <div class="quick-actions no-print">
        <span>¿Qué hacemos hoy?</span>
        <a href="{{ route('clientes.create') }}">
            <x-icon name="users" />
            Registrar cliente
            <x-icon name="plus" />
        </a>
        <a href="{{ route('creditos.create') }}">
            <x-icon name="wallet" />
            Nuevo crédito
            <x-icon name="plus" />
        </a>
        <a href="{{ route('abonos.create') }}">
            <x-icon name="receipt" />
            Registrar abono
            <x-icon name="plus" />
        </a>
    </div>
    <div class="row g-3">
        <div class="col-xxl-8">
            <section class="panel">
                <div class="section-heading">
                    <div>
                        <h2>Últimos abonos</h2>
                        <p>Los movimientos más recientes de tu negocio</p>
                    </div>
                    <a class="text-link" href="{{ route('abonos.index') }}">
                        Ver todos
                        <x-icon name="arrow" />
                    </a>
                </div>
                <x-payment-table :abonos="$ultimos" />
            </section>
        </div>
        <div class="col-xxl-4">
            <section class="panel">
                <div class="section-heading">
                    <div>
                        <h2>Próximos pagos</h2>
                        <p>Calendario pactado, no dinero recibido</p>
                    </div>
                    <span class="yellow-dot"></span>
                </div>
                @forelse($proximos as $cuota)
                    <a href="{{ route('creditos.show',$cuota->credito) }}" class="upcoming-row">
                        <span class="date-box">
                            <strong>
                                {{ $cuota->fecha->format('d') }}
                            </strong>
                            <small>
                                {{ $cuota->fecha->format('m/Y') }}
                            </small>
                        </span>
                        <span>
                            {{ $cuota->credito->cliente->nombre_completo }}
                            <small>
                                Cuota {{ $cuota->numero }} · CR-{{ $cuota->id_credito }}
                            </small>
                        </span>
                        <strong>
                            ${{ number_format($cuota->importe,2) }}
                        </strong>
                    </a>
                @empty
                    <div class="empty-state">
                        <x-icon name="clock" />
                        <p>
                            No hay pagos programados.
                            <br>
                            Se mostrarán al registrar créditos.
                        </p>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
@endsection

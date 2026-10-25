@extends('layouts.app')

@section('title','Comprobante de abono')

@section('content')
    <x-page-heading
        :title="'Comprobante AB-'.str_pad($abono->id_abono,5,'0',STR_PAD_LEFT)"
        description="Registro del movimiento y su saldo al momento de aplicarse."
    >
        <a class="btn btn-light" href="{{ route('creditos.show',$abono->credito) }}">
            Ver crédito
        </a>
        <button class="btn btn-primary" type="button" data-print>
            <x-icon name="print"/>
            Imprimir comprobante
        </button>
    </x-page-heading>
    <article class="panel receipt-panel">
        <div class="receipt-header">
            <div>
                <p class="eyebrow">CRÉDITOS Y ABONOS</p>
                <h2>Comprobante de abono</h2>
                <p class="text-muted">
                    AB-{{ str_pad($abono->id_abono,5,'0',STR_PAD_LEFT) }}
                </p>
            </div>
            <x-status :value="$abono->cancelado_at?'Cancelado':'Aplicado'"/>
        </div>
        <dl class="receipt-data">
            <dt>Cliente</dt>
            <dd>
                {{ $abono->credito->cliente->nombre_completo }}
            </dd>
            <dt>Crédito</dt>
            <dd>
                CR-{{ str_pad($abono->id_credito,5,'0',STR_PAD_LEFT) }}
            </dd>
            <dt>Fecha y hora</dt>
            <dd>
                {{ $abono->fecha_registro_abono->format('d/m/Y H:i:s') }} (Ciudad de México)
            </dd>
            <dt>Número de abono</dt>
            <dd>
                {{ $abono->numero_pago }}
            </dd>
            <dt>Total pactado</dt>
            <dd>
                ${{ number_format($abono->credito->monto_credito,2) }} MXN
            </dd>
            <dt>Importe recibido</dt>
            <dd class="fs-3 fw-semibold">
                ${{ number_format($abono->monto_abono,2) }} MXN
            </dd>
            <dt>Saldo resultante en ese momento</dt>
            <dd>
                ${{ number_format($abono->saldo_resultante,2) }} MXN
            </dd>
            <dt>Registró</dt>
            <dd>
                {{ $abono->usuario?->name ?? 'Registro anterior' }}
            </dd>
        </dl>
        @if($abono->cancelado_at)
            <div class="alert alert-danger mt-3">
                <strong>ABONO CANCELADO</strong>
                <p class="mb-1">
                    {{ $abono->motivo_cancelacion }}
                </p>
                <small>
                    Canceló: {{ $abono->cancelador?->name }} · {{ $abono->cancelado_at->format('d/m/Y H:i:s') }}
                </small>
                <p class="mb-0 mt-2">Este movimiento no se incluye en los abonos aplicados del crédito.</p>
            </div>
        @endif
        <p class="small text-muted border-top pt-3">
            Conserva este comprobante para tu control. El saldo actual puede cambiar por movimientos posteriores.
        </p>
    </article>
    @unless($abono->cancelado_at)
        <section class="panel mt-3 no-print">
            <h2 class="form-section-title">Cancelar este abono</h2>
            <p class="text-muted">El movimiento se conserva y su importe vuelve al saldo pendiente del crédito.</p>
            <form
                action="{{ route('abonos.cancel',$abono) }}"
                method="POST"
                data-confirm="¿Cancelar este abono? El saldo del crédito se reabrirá por el importe cancelado y se conservará la trazabilidad."
            >
                @csrf
                <div class="field-group">
                    <label for="motivo" class="form-label">Motivo de cancelación *</label>
                    <textarea id="motivo" name="motivo" class="form-control" required minlength="5" maxlength="500" rows="2">{{ old('motivo') }}</textarea>
                </div>
                <button class="btn btn-outline-danger" type="submit">Cancelar abono</button>
            </form>
        </section>
    @endunless
@endsection

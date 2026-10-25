@extends('layouts.app')

@section('title','Registrar abono')

@section('content')
    <x-page-heading
        title="Un paso más cerca de liquidar"
        description="Selecciona el crédito y revisa el saldo antes de aplicar el abono."
    >
        <a class="btn btn-light" href="{{ route('abonos.index') }}">Volver a abonos</a>
    </x-page-heading>

    <div class="row g-3">
        <div class="col-lg-7">
            <form
                class="panel form-panel"
                method="POST"
                action="{{ route('abonos.store') }}"
                data-payment-form
                data-confirm="¿Aplicar este abono al crédito seleccionado? Revisa el importe y el saldo resultante."
            >
                @csrf
                <input type="hidden" name="token" value="{{ old('token',$token) }}">
                <h2 class="form-section-title">Datos del abono</h2>
                <div class="field-group">
                    <label for="id_credito" class="form-label">Cliente y crédito *</label>
                    <select class="form-select" id="id_credito" name="id_credito" required>
                        <option value="">Selecciona un crédito pendiente</option>
                        @foreach($creditos as $c)
                            <option
                                value="{{ $c->id_credito }}"
                                data-total="{{ $c->monto_credito }}"
                                data-paid="{{ $c->total_abonado }}"
                                data-balance="{{ $c->saldo }}"
                                @selected(old('id_credito',$seleccionado)==$c->id_credito)
                            >
                                {{ $c->cliente->nombre_completo }} · CR-{{ str_pad($c->id_credito,5,'0',STR_PAD_LEFT) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($creditos->isEmpty())
                    <div class="alert alert-info">
                        No hay créditos con saldo pendiente. Puedes
                        <a href="{{ route('creditos.create') }}">registrar un crédito</a>
                        .
                    </div>
                @endif
                <x-field
                    name="monto_abono"
                    label="Importe del nuevo abono (MXN)"
                    type="number"
                    min="0.01"
                    max="999999999999.99"
                    step="0.01"
                />
                <p class="small text-muted">
                    La fecha y hora, el consecutivo y el encargado se registran automáticamente. El servidor comprueba nuevamente el saldo al guardar.
                </p>
                <div class="form-actions">
                    <a class="btn btn-light" href="{{ route('abonos.index') }}">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="savePayment" disabled>
                        Revisar y aplicar abono
                        <x-icon name="check"/>
                    </button>
                </div>
            </form>
        </div>
        <div class="col-lg-5">
            <section class="panel payment-summary">
                <p class="eyebrow">ANTES DE CONFIRMAR</p>
                <h2>Así quedará tu crédito</h2>
                <p class="text-muted small" id="paymentCredit">Selecciona un crédito para consultar su saldo.</p>
                <dl class="data-list">
                    <dt>Total pactado</dt>
                    <dd id="paymentTotal">—</dd>
                    <dt>Total abonado</dt>
                    <dd id="paymentPaid">—</dd>
                    <dt>Saldo actual</dt>
                    <dd id="paymentBalance">—</dd>
                    <dt>Nuevo abono</dt>
                    <dd id="paymentAmount">—</dd>
                </dl>
                <div class="resulting-balance">
                    <span>Saldo resultante</span>
                    <strong id="paymentResult">—</strong>
                </div>
                <p class="small text-muted mt-3 mb-0" id="paymentHint" role="status">
                    Solo se admiten importes mayores que cero y hasta el saldo disponible.
                </p>
            </section>
        </div>
    </div>
@endsection

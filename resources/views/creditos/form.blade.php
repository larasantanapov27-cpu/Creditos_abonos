@extends('layouts.app')

@section('title',$credito->exists?'Editar crédito':'Nuevo crédito')

@section('content')
    <x-page-heading
        :title="$credito->exists?'Editar crédito':'Un nuevo crédito'"
        description="Define las condiciones y revisa el calendario antes de confirmar."
    >
        <a class="btn btn-light" href="{{ route('creditos.index') }}">Volver a créditos</a>
    </x-page-heading>

    <form
        action="{{ $credito->exists?route('creditos.update',$credito):route('creditos.store') }}"
        method="POST"
        class="panel form-panel"
        data-credit-form
        data-preview-url="{{ route('creditos.preview') }}"
        data-locked="{{ $bloqueado?'1':'0' }}"
        data-confirm="¿Guardar este crédito con las condiciones y el calendario mostrados?"
    >
        @csrf
        @if($credito->exists)
            @method('PUT')
        @endif
        @if($bloqueado)
            <div class="alert alert-info">
                Este crédito tiene movimientos. Solo puedes modificar el motivo; sus montos y condiciones conservan el historial.
            </div>
        @endif
        @if($clientes->isEmpty())
            <div class="alert alert-warning">
                Necesitas
                <a href="{{ route('clientes.create') }}">registrar un cliente activo</a>
                antes de crear un crédito.
            </div>
        @endif
        <div class="row">
            <div class="col-md-8 field-group">
                <label class="form-label" for="id_cliente">Cliente *</label>
                <select class="form-select" name="id_cliente" id="id_cliente" required @if($bloqueado) data-readonly @endif>
                    <option value="">Selecciona un cliente</option>
                    @foreach($clientes as $c)
                        <option
                            value="{{ $c->id_cliente }}"
                            @selected(old('id_cliente',$credito->id_cliente??request('id_cliente'))==$c->id_cliente)
                        >
                            {{ $c->nombre_completo }} · #{{ $c->id_cliente }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-field
                class="col-md-4"
                name="monto_credito"
                label="Total pactado por cobrar (MXN)"
                type="number"
                :value="$credito->monto_credito"
                step="0.01"
                min="0.01"
                max="999999999999.99"
                :readonly="$bloqueado"
            />
            <x-field
                class="col-12"
                name="motivo_credito"
                label="Motivo"
                :value="$credito->motivo_credito"
                maxlength="50"
            />
            <x-field
                class="col-md-4"
                name="fecha_aprobacion"
                label="Fecha de inicio"
                type="date"
                :value="$credito->fecha_aprobacion?->format('Y-m-d')??today()->toDateString()"
                :readonly="$bloqueado"
            />
            <x-field
                class="col-md-4"
                name="fecha_pago"
                label="Primer pago pactado"
                type="date"
                :value="$credito->fecha_pago?->format('Y-m-d')"
                :readonly="$bloqueado"
            />
            <x-field
                class="col-md-4"
                name="fecha_vencimiento"
                label="Fecha de vencimiento"
                type="date"
                :value="$credito->fecha_vencimiento?->format('Y-m-d')"
                :readonly="$bloqueado"
            />
            <x-field
                class="col-md-4"
                name="cantidad_pagos"
                label="Cantidad de pagos"
                type="number"
                :value="$credito->cantidad_pagos??1"
                min="1"
                max="600"
                :readonly="$bloqueado"
            />
            <div class="col-md-4 field-group">
                <label for="periodicidad" class="form-label">Periodicidad *</label>
                <select
                    name="periodicidad"
                    id="periodicidad"
                    class="form-select"
                    required
                    @if($bloqueado)
                    data-readonly
                    @endif
                >
                    @foreach(['semanal'=>'Semanal (7 días)','quincenal'=>'Quincenal (15 días)','mensual'=>'Mensual'] as $v=>$t)
                        <option value="{{ $v }}" @selected(old('periodicidad',$credito->periodicidad)==$v)>
                            {{ $t }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 field-group">
                <label for="id_tipo_pago" class="form-label">Tipo de pago *</label>
                <select
                    name="id_tipo_pago"
                    id="id_tipo_pago"
                    class="form-select"
                    required
                    @if($bloqueado)
                    data-readonly
                    @endif
                >
                    @foreach($tipos as $t)
                        <option
                            value="{{ $t->id_tipo_pago }}"
                            @selected(old('id_tipo_pago',$credito->id_tipo_pago)==$t->id_tipo_pago)
                        >
                            {{ $t->tipo }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 field-group">
                <label for="id_tasa_interes" class="form-label">Tasa de referencia *</label>
                <select
                    name="id_tasa_interes"
                    id="id_tasa_interes"
                    class="form-select"
                    required
                    @if($bloqueado)
                    data-readonly
                    @endif
                >
                    @foreach($tasas as $t)
                        <option
                            value="{{ $t->id_tasa_interes }}"
                            @selected(old('id_tasa_interes',$credito->id_tasa_interes)==$t->id_tasa_interes)
                        >
                            {{ $t->tasa }}%
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="small text-muted">
            El total capturado es el total a cobrar. La tasa se conserva como referencia y no agrega intereses. Las mensualidades mantienen el día original o el último día disponible del mes.
        </p>
        <section class="calendar-preview">
            <div class="section-heading">
                <h2>Calendario pactado</h2>
                @unless($bloqueado)
                    <button class="btn btn-light" id="previewCalendar" type="button">
                        Calcular y revisar calendario
                    </button>
                @endunless
            </div>
            <p class="small text-muted" id="calendarHelp">
                {{ $bloqueado?'Las condiciones y cuotas están protegidas.':'Al calcular se ajusta el vencimiento a la última cuota. Revisa los importes antes de guardar.' }}
            </p>
            <div id="calendarError" class="alert alert-danger" hidden role="alert"></div>
            <div class="table-responsive calendar-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cuota</th>
                            <th>Fecha</th>
                            <th>Importe pactado</th>
                        </tr>
                    </thead>
                    <tbody id="calendarRows">
                        @if($bloqueado)
                            @foreach($credito->cuotas as $cuota)
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
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-muted">El calendario aparecerá aquí.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
        <div class="form-actions">
            <a href="{{ route('creditos.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-primary" id="saveCredit" @disabled(!$bloqueado)>
                {{ $credito->exists?'Guardar cambios':'Confirmar crédito' }}
                <x-icon name="check"/>
            </button>
        </div>
    </form>
@endsection

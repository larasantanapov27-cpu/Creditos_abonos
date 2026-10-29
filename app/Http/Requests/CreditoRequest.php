<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'prohibited',
            'id_cliente' => ['required', 'integer', \Illuminate\Validation\Rule::exists('registro_clientes', 'id_cliente')->where('user_id', $this->user()->id)],
            'motivo_credito' => 'required|string|max:50',
            'monto_credito' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2'],
            'fecha_aprobacion' => 'required|date_format:Y-m-d',
            'fecha_pago' => 'required|date_format:Y-m-d|after_or_equal:fecha_aprobacion',
            'fecha_vencimiento' => 'required|date_format:Y-m-d|after_or_equal:fecha_pago',
            'cantidad_pagos' => 'required|integer|min:1|max:600',
            'periodicidad' => 'required|in:semanal,quincenal,mensual',
            'id_tasa_interes' => 'required|exists:tasa_intereses,id_tasa_interes',
            'id_tipo_pago' => 'required|exists:tipo_pagos,id_tipo_pago',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbonoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'prohibited',
            'id_credito' => ['bail', 'required', 'integer', function ($attribute, $value, $fail) {
                if (! \App\Models\Credito::whereKey($value)->exists()) {
                    $fail('El crédito seleccionado no está disponible.');
                }
            }],
            'monto_abono' => 'required|numeric|min:0.01|max:999999999999.99|decimal:0,2',
            'token' => 'required|uuid',
        ];
    }
}

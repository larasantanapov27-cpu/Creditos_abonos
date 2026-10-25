<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['curp' => strtoupper(trim((string) $this->curp))]);
    }

    public function rules(): array
    {
        $id = $this->route('cliente')?->getKey();

        return [
            'nombre' => 'required|string|max:25',
            'apellido_paterno' => 'required|string|max:25',
            'apellido_materno' => 'required|string|max:25',
            'telefono' => [
                'required',
                'regex:/^[0-9+ ()-]{7,20}$/',
                Rule::unique('registro_clientes', 'telefono')->ignore($id, 'id_cliente'),
            ],
            'correo_electronico' => 'required|email|max:50',
            'id_estado' => 'required|exists:estados,id_estado',
            'id_municipio' => ['required', Rule::exists('municipios', 'id_municipio')->where('id_estado', $this->id_estado)],
            'codigo_postal' => 'required|digits:5',
            'direccion' => 'required|string|max:50',
            'curp' => [
                'required',
                'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/',
                Rule::unique('registro_clientes', 'curp')->ignore($id, 'id_cliente'),
            ],
        ];
    }
}

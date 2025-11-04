<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotaFiscalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'numero_nota' => ['nullable', 'string', 'max:255'],
            'data_emissao' => ['nullable', 'date'],
            'natureza_operacao' => ['nullable', 'string', 'max:255'],
            'prestador_razao_social' => ['nullable', 'string', 'max:255'],
            'prestador_cnpj' => ['nullable', 'string', 'max:18'],
            'tomador_razao_social' => ['nullable', 'string', 'max:255'],
            'tomador_cnpj' => ['nullable', 'string', 'max:18'],
            'valor_total' => ['nullable', 'numeric', 'min:0'],
            'status_pagamento' => ['nullable', 'string', 'max:255'],
            'data_pagamento' => ['nullable', 'date'],
            'municipio_tributacao' => ['nullable', 'string', 'max:255'],
            'competencia' => ['nullable', 'string', 'max:255'],
        ];
    }
}

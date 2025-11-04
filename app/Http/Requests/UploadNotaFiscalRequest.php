<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadNotaFiscalRequest extends FormRequest
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
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'arquivo.required' => 'Por favor, selecione um arquivo PDF.',
            'arquivo.file' => 'O arquivo enviado não é válido.',
            'arquivo.mimes' => 'O arquivo deve ser um PDF.',
            'arquivo.max' => 'O arquivo não pode ser maior que 10MB.',
        ];
    }
}

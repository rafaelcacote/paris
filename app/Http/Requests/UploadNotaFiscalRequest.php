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
            'arquivos' => ['required', 'array', 'min:1'],
            'arquivos.*' => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB max per file
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'arquivos.required' => 'Por favor, selecione pelo menos um arquivo PDF.',
            'arquivos.array' => 'Os arquivos devem ser enviados como um array.',
            'arquivos.min' => 'Por favor, selecione pelo menos um arquivo PDF.',
            'arquivos.*.required' => 'Um dos arquivos enviados não é válido.',
            'arquivos.*.file' => 'Um dos arquivos enviados não é válido.',
            'arquivos.*.mimes' => 'Todos os arquivos devem ser PDFs.',
            'arquivos.*.max' => 'Cada arquivo não pode ser maior que 10MB.',
        ];
    }
}

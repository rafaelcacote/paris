<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'cpf' => [
                'required',
                'string',
                'size:14',
                'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/',
            ],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => $this->cleanCpf($this->cpf),
            ]);
        }
    }

    /**
     * Clean CPF (remove non-numeric characters and format for validation).
     */
    private function cleanCpf(?string $cpf): ?string
    {
        if (! $cpf) {
            return null;
        }

        $cleaned = preg_replace('/\D/', '', $cpf);

        if (strlen($cleaned) === 11) {
            // Format for validation display (with dots and dash)
            return substr($cleaned, 0, 3).'.'.substr($cleaned, 3, 3).'.'.substr($cleaned, 6, 3).'-'.substr($cleaned, 9, 2);
        }

        return $cpf;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('cpf')) {
                // Validate CPF format and digits
                if (! $this->isValidCpf($this->cpf)) {
                    $validator->errors()->add('cpf', 'O CPF informado é inválido.');
                }

                // Check if CPF already exists (without formatting)
                $cpfCleaned = preg_replace('/\D/', '', $this->cpf);
                $exists = User::where('cpf', $cpfCleaned)->exists();

                if ($exists) {
                    $validator->errors()->add('cpf', 'Este CPF já está cadastrado.');
                }
            }
        });
    }

    /**
     * Validate CPF format and digits.
     */
    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}

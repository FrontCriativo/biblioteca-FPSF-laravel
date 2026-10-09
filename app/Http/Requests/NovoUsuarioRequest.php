<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NovoUsuarioRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
        // Qualquer valor diferente de "admin" vira aluno.
        $this->merge(['papel' => $this->papel === 'admin' ? 'admin' : 'aluno']);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:usuarios,email'],
            'senha' => ['required', 'string', 'min:6', 'max:255'],
            'papel' => ['required', 'in:aluno,admin'],
            'turma' => ['nullable', 'string', 'max:40'],
        ];
    }
}

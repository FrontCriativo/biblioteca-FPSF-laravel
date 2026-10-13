<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Troca da própria senha na API v1 (PUT /api/v1/usuario/senha). */
class AlterarSenhaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'senha_atual' => ['required', 'string'],
            'nova_senha' => ['required', 'string', 'min:8', 'max:255', 'confirmed', 'different:senha_atual'],
        ];
    }
}

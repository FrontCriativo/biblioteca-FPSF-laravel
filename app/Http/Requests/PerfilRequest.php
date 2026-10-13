<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Atualização do próprio perfil na API v1 (PUT e PATCH /api/v1/usuario).
 *
 * Só nome e e-mail podem mudar por aqui. Qualquer outro campo do corpo
 * (papel, turma_id, ativo, senha...) fica fora do validated() e é ignorado.
 * No PUT os dois campos são obrigatórios; no PATCH só é validado o que vier.
 */
class PerfilRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        // PATCH: o campo pode faltar, mas se vier não pode ser vazio.
        $presenca = $this->isMethod('PATCH') ? ['sometimes', 'required'] : ['required'];

        return [
            'nome' => [...$presenca, 'string', 'max:80'],
            'email' => [
                ...$presenca, 'string', 'email', 'max:120',
                Rule::unique('usuarios', 'email')->ignore($this->user()->id),
            ],
        ];
    }
}

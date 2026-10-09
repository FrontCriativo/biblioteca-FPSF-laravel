<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Criação (POST) e edição (PUT) de livro. Só a criação aceita "exemplares".
 */
class LivroRequest extends FormRequest
{
    public function rules(): array
    {
        $regras = [
            'titulo' => ['required', 'string', 'max:255'],
            'autor' => ['required', 'string', 'max:255'],
            'materia_slug' => ['required', 'string', 'exists:materias,slug'],
            // Só o nome do arquivo dentro de public/assets.
            'capa' => ['nullable', 'string', 'max:255', 'regex:/^[\w.\-]+$/'],
            'sinopse' => ['nullable', 'string', 'max:5000'],
            'paginas' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'idioma' => ['nullable', 'string', 'max:60'],
        ];

        if ($this->isMethod('post')) {
            $regras['exemplares'] = ['nullable', 'integer', 'min:1', 'max:200'];
        }

        return $regras;
    }

    /** Dados prontos para gravar, com os padrões aplicados. */
    public function dadosDoLivro(): array
    {
        $dados = $this->safe()->except('exemplares');
        $dados['idioma'] ??= 'Português';
        $dados['paginas'] = isset($dados['paginas']) ? (int) $dados['paginas'] : null;

        return $dados + ['capa' => null, 'sinopse' => null];
    }
}

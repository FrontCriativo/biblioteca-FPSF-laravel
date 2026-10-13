<?php

// Mensagens de validação em português. As regras não listadas aqui caem
// no inglês padrão do Laravel (APP_FALLBACK_LOCALE).
return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'integer' => 'O campo :attribute deve ser um número inteiro positivo.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'email' => 'Informe um e-mail válido.',
    'in' => 'O valor do campo :attribute é inválido.',
    'exists' => 'O valor do campo :attribute não existe.',
    'unique' => 'Já existe um registro com esse :attribute.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'different' => 'O campo :attribute deve ser diferente do campo :other.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute precisa ter ao menos :min caracteres.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute deve ser no máximo :max.',
        'string' => 'O campo :attribute pode ter no máximo :max caracteres.',
    ],

    'custom' => [
        'email' => [
            'unique' => 'Já existe um usuário com esse e-mail.',
        ],
        'senha' => [
            'min' => 'A senha precisa ter ao menos :min caracteres.',
        ],
        'nova_senha' => [
            'min' => 'A nova senha precisa ter ao menos :min caracteres.',
            'different' => 'A nova senha precisa ser diferente da senha atual.',
        ],
    ],

    'attributes' => [
        'email' => 'e-mail',
        'senha' => 'senha',
        'senha_atual' => 'senha atual',
        'nova_senha' => 'nova senha',
        'device_name' => 'nome do dispositivo',
        'nome' => 'nome',
        'turma' => 'turma',
        'papel' => 'papel',
        'titulo' => 'título',
        'autor' => 'autor',
        'materia_slug' => 'matéria',
        'capa' => 'capa',
        'sinopse' => 'sinopse',
        'paginas' => 'páginas',
        'idioma' => 'idioma',
        'exemplares' => 'exemplares',
        'quantidade' => 'quantidade',
        'livro_id' => 'livro',
        'dados' => 'imagem',
    ],
];

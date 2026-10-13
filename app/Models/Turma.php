<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Turma da escola ("8º ano B"), a que cada aluno pertence. */
#[Table(name: 'turmas')]
#[Fillable(['nome', 'ano_letivo', 'ativa'])]
class Turma extends Model
{
    public const CREATED_AT = 'criada_em';

    public const UPDATED_AT = 'atualizada_em';

    protected function casts(): array
    {
        return [
            'ano_letivo' => 'integer',
            'ativa' => 'boolean',
            'criada_em' => 'datetime',
            'atualizada_em' => 'datetime',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }
}

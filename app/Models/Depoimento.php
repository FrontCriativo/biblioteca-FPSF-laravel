<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Depoimento enviado pela página public/depoimentos.html. Só aparece no site
 * (e na API pública) depois de aprovado pela coordenação.
 */
#[Table(name: 'depoimentos')]
#[Fillable(['usuario_id', 'nome', 'descricao_autor', 'texto', 'nota', 'status'])]
class Depoimento extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return [
            'nota' => 'integer',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    #[Scope]
    protected function aprovados(Builder $query): void
    {
        $query->where('status', 'aprovado');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'reservas')]
#[Fillable(['exemplar_id', 'usuario_id', 'status', 'expira_em', 'encerrada_em'])]
class Reserva extends Model
{
    public const CREATED_AT = 'criada_em';

    public const UPDATED_AT = null;

    /** Status em que o exemplar continua preso à reserva. */
    public const ABERTAS = ['ativa', 'retirada'];

    /** Para cada status de destino, de quais status é possível chegar nele. */
    public const TRANSICOES = [
        'retirada' => ['ativa'],
        'devolvida' => ['retirada'],
        'cancelada' => ['ativa', 'retirada'],
    ];

    protected function casts(): array
    {
        return [
            'criada_em' => 'datetime',
            'expira_em' => 'datetime',
            'encerrada_em' => 'datetime',
        ];
    }

    public function exemplar(): BelongsTo
    {
        return $this->belongsTo(Exemplar::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    #[Scope]
    protected function abertas(Builder $query): void
    {
        $query->whereIn('status', self::ABERTAS);
    }

    /** Relações que o ReservaResource precisa. */
    #[Scope]
    protected function comDetalhes(Builder $query): void
    {
        $query->with(['exemplar.livro', 'usuario']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'exemplares', timestamps: false)]
#[Fillable(['livro_id', 'codigo', 'estado'])]
class Exemplar extends Model
{
    public function livro(): BelongsTo
    {
        return $this->belongsTo(Livro::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    #[Scope]
    protected function emBomEstado(Builder $query): void
    {
        $query->where('estado', 'ok');
    }

    /** Em bom estado e sem nenhuma reserva aberta. */
    #[Scope]
    protected function disponiveis(Builder $query): void
    {
        $query->emBomEstado()
            ->whereDoesntHave('reservas', fn (Builder $q) => $q->abertas());
    }
}

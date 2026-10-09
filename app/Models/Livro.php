<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Table(name: 'livros')]
#[Fillable(['slug', 'titulo', 'autor', 'materia_slug', 'capa', 'sinopse', 'paginas', 'idioma'])]
class Livro extends Model
{
    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'paginas' => 'integer',
            'criado_em' => 'datetime',
        ];
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'materia_slug', 'slug');
    }

    public function exemplares(): HasMany
    {
        return $this->hasMany(Exemplar::class);
    }

    /**
     * Carrega a matéria e as contagens usadas pelo catálogo:
     * total_exemplares (em bom estado) e disponiveis (sem reserva aberta).
     */
    #[Scope]
    protected function comDisponibilidade(Builder $query): void
    {
        $query->with('materia')->withCount([
            'exemplares as total_exemplares' => fn (Builder $q) => $q->emBomEstado(),
            'exemplares as disponiveis' => fn (Builder $q) => $q->disponiveis(),
        ]);
    }

    /** Recarrega o livro com matéria e contagens atualizadas. */
    public function comContagens(): static
    {
        return static::query()->comDisponibilidade()->findOrFail($this->getKey());
    }

    public function temReservasAbertas(): bool
    {
        return Reserva::query()->abertas()
            ->whereHas('exemplar', fn (Builder $q) => $q->where('livro_id', $this->id))
            ->exists();
    }

    /** Cria cópias físicas com códigos únicos. */
    public function adicionarExemplares(int $quantidade): void
    {
        for ($i = 0; $i < $quantidade; $i++) {
            $this->exemplares()->create([
                'codigo' => sprintf('L%d-%s', $this->id, Str::upper(Str::random(8))),
            ]);
        }
    }

    public static function gerarSlug(string $titulo): string
    {
        return Str::slug($titulo);
    }
}

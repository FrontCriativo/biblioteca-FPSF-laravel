<?php

namespace App\Models;

use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Table(name: 'usuarios')]
#[Fillable(['nome', 'email', 'senha', 'papel', 'turma_id', 'ativo'])]
#[Hidden(['senha'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory;

    public const CREATED_AT = 'criado_em';

    public const UPDATED_AT = null;

    /** Coluna onde o Laravel procura o hash da senha no login. */
    protected $authPasswordName = 'senha';

    /** Sem "lembrar de mim": a sessão já dura 7 dias (SESSION_LIFETIME). */
    protected $rememberTokenName = '';

    /**
     * A turma aparece em todo JSON de usuário (UsuarioResource, ReservaResource).
     * Carregá-la sempre evita a exceção do preventLazyLoading no login, em
     * /api/eu e no painel da coordenação.
     */
    protected $with = ['turma'];

    /** Mesmo padrão da coluna, para o model recém-criado já saber que está ativo. */
    protected $attributes = ['ativo' => true];

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'ativo' => 'boolean',
            'criado_em' => 'datetime',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $valor) => mb_strtolower(trim($valor)));
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function ehAdmin(): bool
    {
        return $this->papel === 'admin';
    }
}

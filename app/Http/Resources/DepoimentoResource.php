<?php

namespace App\Http\Resources;

use App\Models\Depoimento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dados públicos de um depoimento. Nunca expõe usuario_id, e-mail ou status.
 *
 * @mixin Depoimento
 */
class DepoimentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao_autor' => $this->descricao_autor,
            'texto' => $this->texto,
            'nota' => $this->nota,
            'criado_em' => $this->criado_em?->utc()->toIso8601String(),
        ];
    }
}

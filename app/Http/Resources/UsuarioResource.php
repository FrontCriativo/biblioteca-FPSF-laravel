<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $naListagemAdmin = array_key_exists('reservas_ativas', $this->resource->getAttributes());

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'papel' => $this->papel,
            'turma' => $this->turma?->nome,
            $this->mergeWhen($naListagemAdmin, fn () => [
                'criado_em' => $this->criado_em?->utc()->format('Y-m-d H:i:s'),
                'reservas_ativas' => (int) $this->reservas_ativas,
            ]),
        ];
    }
}

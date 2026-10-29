<?php

namespace App\Http\Resources;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matéria no formato do aplicativo Expo (GET /api/v1/categoria).
 *
 * As matérias não têm id numérico: o id_categoria é o slug, o mesmo valor
 * que aparece em categoria_produto.id_categoria de cada livro.
 *
 * @mixin Materia
 */
class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_categoria' => $this->slug,
            'nome_categoria' => $this->nome,
            'status_categoria' => 'ATIVO',
            'ordem_categoria' => $this->ordem,
            'icone' => $this->icone,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Materia
 */
class MateriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'nome' => $this->nome,
            'icone' => $this->icone,
            'total_livros' => (int) $this->total_livros,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Livro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Livro
 */
class LivroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'autor' => $this->autor,
            'materia_slug' => $this->materia_slug,
            'capa' => $this->capa,
            'sinopse' => $this->sinopse,
            'paginas' => $this->paginas,
            'idioma' => $this->idioma,
            'materia_nome' => $this->materia?->nome,
            'materia_icone' => $this->materia?->icone,
            'total_exemplares' => (int) $this->total_exemplares,
            'disponiveis' => (int) $this->disponiveis,
        ];
    }
}

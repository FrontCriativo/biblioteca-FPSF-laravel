<?php

namespace App\Http\Resources;

use App\Models\Livro;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Livro no formato do aplicativo Expo (GET /api/v1/produto).
 *
 * Espera o livro carregado com comDisponibilidade() e o atributo "destaque"
 * definido pelo ProdutoController.
 *
 * @mixin Livro
 */
class ProdutoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'autor' => $this->autor,
            'categoria_produto' => $this->materia ? [
                'id_categoria' => $this->materia->slug,
                'nome_categoria' => $this->materia->nome,
            ] : null,
            'genero' => $this->materia?->nome,
            // Só o nome do arquivo: as capas ficam em public/assets/.
            'imagem' => $this->capa,
            'status' => $this->disponiveis > 0 ? 'disponivel' : 'emprestado',
            // O acervo não tem livro inativo: quem sai do catálogo é excluído.
            'status_produto' => 'ATIVO',
            'livrosDestaque' => $this->destaque ? 'SIM' : 'NAO',
            'descricao' => $this->sinopse,
            'total_exemplares' => (int) $this->total_exemplares,
            'disponiveis' => (int) $this->disponiveis,
        ];
    }
}

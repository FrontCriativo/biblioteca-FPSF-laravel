<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProdutoResource;
use App\Models\Livro;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

/**
 * Livros no formato do aplicativo Expo. Os mesmos dados de GET /api/v1/livros,
 * com os nomes de campo que o app espera (ProdutoResource).
 */
class ProdutoController extends Controller
{
    /** Quantos livros, dos cadastrados mais recentemente, saem como destaque. */
    private const TOTAL_DESTAQUES = 6;

    /** GET /api/v1/produto */
    public function index(): JsonResponse
    {
        $livros = Livro::query()->comDisponibilidade()->orderBy('titulo')->get();

        return $this->sucesso(ProdutoResource::collection($this->marcarDestaques($livros)));
    }

    /** GET /api/v1/produto/{id} */
    public function show(Livro $livro): JsonResponse
    {
        $livro = $this->marcarDestaques(new Collection([$livro->comContagens()]))->first();

        return $this->sucesso(new ProdutoResource($livro));
    }

    /** Define o atributo "destaque" (lido pelo ProdutoResource) em cada livro. */
    private function marcarDestaques(Collection $livros): Collection
    {
        $destaques = Livro::query()
            ->orderByDesc('criado_em')
            ->orderByDesc('id')
            ->limit(self::TOTAL_DESTAQUES)
            ->pluck('id');

        return $livros->each(fn (Livro $livro) => $livro->setAttribute('destaque', $destaques->contains($livro->id)));
    }
}

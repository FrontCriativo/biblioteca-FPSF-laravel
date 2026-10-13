<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\LivroResource;
use App\Http\Resources\MateriaResource;
use App\Models\Livro;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;

class MateriaController extends Controller
{
    /** GET /api/v1/materias */
    public function index(): JsonResponse
    {
        $materias = Materia::query()
            ->withCount('livros as total_livros')
            ->orderBy('nome')
            ->get();

        return $this->sucesso(MateriaResource::collection($materias));
    }

    /** GET /api/v1/materias/{slug}/livros — 404 se a matéria não existir. */
    public function livros(Materia $materia): JsonResponse
    {
        $livros = Livro::query()
            ->comDisponibilidade()
            ->where('materia_slug', $materia->slug)
            ->orderBy('titulo')
            ->get();

        return $this->sucesso(LivroResource::collection($livros));
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\CategoriaResource;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;

/** Matérias no formato do aplicativo Expo (CategoriaResource). */
class CategoriaController extends Controller
{
    /** GET /api/v1/categoria — em ordem alfabética; ordem_categoria é a posição na lista. */
    public function index(): JsonResponse
    {
        $materias = Materia::query()->orderBy('nome')->get()
            ->each(fn (Materia $materia, int $i) => $materia->setAttribute('ordem', $i + 1));

        return $this->sucesso(CategoriaResource::collection($materias));
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\LivroResource;
use App\Models\Livro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LivroController extends Controller
{
    /** GET /api/v1/livros?materia=…&busca=… */
    public function index(Request $request): JsonResponse
    {
        $materia = $request->query('materia');
        $busca = trim((string) $request->query('busca'));

        $livros = Livro::query()
            ->comDisponibilidade()
            ->when($materia && $materia !== 'todos', fn (Builder $q) => $q->where('materia_slug', $materia))
            ->when($busca !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('titulo', 'like', "%{$busca}%")
                ->orWhere('autor', 'like', "%{$busca}%")))
            ->orderBy('titulo')
            ->get();

        return $this->sucesso(LivroResource::collection($livros));
    }

    /** GET /api/v1/livros/{slug} */
    public function show(Livro $livro): JsonResponse
    {
        return $this->sucesso(new LivroResource($livro->comContagens()));
    }
}

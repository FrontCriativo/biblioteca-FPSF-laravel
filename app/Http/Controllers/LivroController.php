<?php

namespace App\Http\Controllers;

use App\Http\Requests\LivroRequest;
use App\Http\Resources\LivroResource;
use App\Models\Livro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class LivroController extends Controller
{
    /** GET /api/livros?materia=…&busca=… */
    public function index(Request $request): AnonymousResourceCollection
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

        return LivroResource::collection($livros);
    }

    /** GET /api/livros/{slug} */
    public function show(Livro $livro): LivroResource
    {
        return new LivroResource($livro->comContagens());
    }

    /** POST /api/livros */
    public function store(LivroRequest $request): JsonResponse
    {
        $dados = $request->dadosDoLivro();
        $dados['slug'] = Livro::gerarSlug($dados['titulo']);

        if ($dados['slug'] === '' || Livro::where('slug', $dados['slug'])->exists()) {
            abort(409, 'Já existe um livro com esse título.');
        }

        $livro = DB::transaction(function () use ($dados, $request) {
            $livro = Livro::create($dados);
            $livro->adicionarExemplares((int) ($request->validated('exemplares') ?? 1));

            return $livro;
        });

        return (new LivroResource($livro->comContagens()))->response()->setStatusCode(201);
    }

    /** PUT /api/livros/{id} */
    public function update(LivroRequest $request, Livro $livro): LivroResource
    {
        $livro->update($request->dadosDoLivro());

        return new LivroResource($livro->comContagens());
    }

    /** DELETE /api/livros/{id} */
    public function destroy(Livro $livro): JsonResponse
    {
        if ($livro->temReservasAbertas()) {
            abort(409, 'Este livro tem reservas ativas e não pode ser removido.');
        }

        $livro->delete();

        return response()->json(['ok' => true]);
    }

    /** POST /api/livros/{id}/exemplares */
    public function adicionarExemplares(Request $request, Livro $livro): JsonResponse
    {
        $quantidade = (int) ($request->validate([
            'quantidade' => ['nullable', 'integer', 'min:1', 'max:200'],
        ])['quantidade'] ?? 1);

        $livro->adicionarExemplares($quantidade);

        return response()->json(['ok' => true, 'adicionados' => $quantidade]);
    }
}

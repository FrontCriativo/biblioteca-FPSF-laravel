<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NovoUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Turma;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UsuarioController extends Controller
{
    /** GET /api/admin/usuarios */
    public function index(): AnonymousResourceCollection
    {
        $usuarios = Usuario::query()
            ->withCount(['reservas as reservas_ativas' => fn (Builder $q) => $q->abertas()])
            ->orderBy('nome')
            ->get();

        return UsuarioResource::collection($usuarios);
    }

    /**
     * POST /api/admin/usuarios
     *
     * O painel (admin.js) manda a turma como texto ("7º ano C"). Ela é
     * procurada pelo nome e criada no ano letivo atual se ainda não existir.
     */
    public function store(NovoUsuarioRequest $request): JsonResponse
    {
        $nomeTurma = $request->validated('turma');
        $turma = $nomeTurma === null
            ? null
            : Turma::firstOrCreate(['nome' => $nomeTurma], ['ano_letivo' => now()->year]);

        $usuario = Usuario::create([...$request->safe()->except('turma'), 'turma_id' => $turma?->id]);
        $usuario->setRelation('turma', $turma);

        return (new UsuarioResource($usuario))->response()->setStatusCode(201);
    }
}

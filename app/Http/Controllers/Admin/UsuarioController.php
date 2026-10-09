<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NovoUsuarioRequest;
use App\Http\Resources\UsuarioResource;
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

    /** POST /api/admin/usuarios */
    public function store(NovoUsuarioRequest $request): JsonResponse
    {
        $usuario = Usuario::create($request->validated());

        return (new UsuarioResource($usuario))->response()->setStatusCode(201);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\AlterarSenhaRequest;
use App\Http\Requests\PerfilRequest;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Perfil do usuário dono do token (equivalente ao ClienteController da aula). */
class UsuarioController extends Controller
{
    /** GET /api/v1/usuario */
    public function show(Request $request): JsonResponse
    {
        return $this->sucesso(new UsuarioResource($request->user()));
    }

    /** PUT e PATCH /api/v1/usuario — grava só nome e e-mail (o que passou na validação). */
    public function update(PerfilRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $usuario->update($request->validated());

        return $this->sucessoComMensagem('Perfil atualizado com sucesso.', new UsuarioResource($usuario));
    }

    /** PUT /api/v1/usuario/senha */
    public function updateSenha(AlterarSenhaRequest $request): JsonResponse
    {
        $usuario = $request->user();

        if (! Hash::check($request->validated('senha_atual'), $usuario->senha)) {
            abort(422, 'Senha atual incorreta.');
        }

        // Texto puro: o cast "hashed" do model gera o bcrypt.
        $usuario->update(['senha' => $request->validated('nova_senha')]);

        // Os outros aparelhos precisam entrar de novo; o token atual continua valendo.
        $usuario->tokens()->whereKeyNot($usuario->currentAccessToken()->getKey())->delete();

        return $this->sucessoComMensagem('Senha alterada com sucesso.');
    }
}

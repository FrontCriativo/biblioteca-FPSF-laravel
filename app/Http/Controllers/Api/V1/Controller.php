<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base dos controllers da API v1.
 *
 * O projeto usa JsonResource::withoutWrapping() (o front-end espera respostas
 * "cruas"), então o envelope { "success": true, "data": ... } da v1 é montado
 * aqui. Os erros da v1 saem como { "success": false, "erro": ... } pelo
 * tratamento de exceções em bootstrap/app.php.
 */
abstract class Controller extends BaseController
{
    /** { "success": true, "data": ... } */
    protected function sucesso(mixed $dados, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->resolver($dados)], $status);
    }

    /** { "success": true, "mensagem": "...", "data": ... } (sem "data" quando não há dados). */
    protected function sucessoComMensagem(string $mensagem, mixed $dados = null, int $status = 200): JsonResponse
    {
        $corpo = ['success' => true, 'mensagem' => $mensagem];

        if ($dados !== null) {
            $corpo['data'] = $this->resolver($dados);
        }

        return response()->json($corpo, $status);
    }

    private function resolver(mixed $dados): mixed
    {
        if ($dados instanceof JsonResource) {
            return $dados->resolve(request());
        }

        if (is_array($dados)) {
            return array_map(fn ($valor) => $this->resolver($valor), $dados);
        }

        return $dados;
    }
}
